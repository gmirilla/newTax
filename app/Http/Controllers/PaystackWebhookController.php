<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // ── 1. Verify HMAC-SHA512 signature ──────────────────────────────────
        $signature = $request->header('x-paystack-signature', '');
        $computed  = hash_hmac('sha512', $request->getContent(), config('paystack.secret_key'));

        if (!hash_equals($computed, $signature)) {
            Log::warning('Paystack webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload   = $request->json()->all();
        $eventType = $payload['event'] ?? 'unknown';
        $eventId   = (string) ($payload['data']['id'] ?? '');

        // ── 2. Idempotency check ──────────────────────────────────────────────
        if ($eventId && WebhookEvent::where('source', 'paystack')
                ->where('event_id', $eventId)
                ->where('status', 'processed')
                ->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        // ── 3. Log the incoming event ─────────────────────────────────────────
        $log = WebhookEvent::create([
            'source'     => 'paystack',
            'event_type' => $eventType,
            'event_id'   => $eventId ?: null,
            'payload'    => $payload,
            'status'     => 'processing',
        ]);

        // ── 4. Dispatch to handler ────────────────────────────────────────────
        try {
            $this->dispatch($eventType, $payload['data'] ?? []);
            $log->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('Paystack webhook processing failed', [
                'event'   => $eventType,
                'eventId' => $eventId,
                'error'   => $e->getMessage(),
            ]);
            // Always return 200 — a non-200 causes Paystack to retry indefinitely
        }

        return response()->json(['status' => 'ok']);
    }

    // ── Event dispatcher ──────────────────────────────────────────────────────

    private function dispatch(string $event, array $data): void
    {
        match ($event) {
            'charge.success'         => $this->handleChargeSuccess($data),
            'subscription.create'    => $this->handleSubscriptionCreate($data),
            'subscription.disable'   => $this->handleSubscriptionDisable($data),
            'subscription.not_renew' => $this->handleSubscriptionDisable($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            'invoice.update'         => $this->handleInvoiceUpdate($data),
            default                  => null,
        };
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    /**
     * A successful charge — covers both initial payments and recurring renewals.
     * For renewals there is no callback, so this is the only activation path.
     */
    private function handleChargeSuccess(array $data): void
    {
        $tenant = $this->resolveTenant($data);
        if (!$tenant) return;

        // If a downgrade is pending, apply it on this renewal instead of extending current plan
        if ($tenant->next_plan_id) {
            $pendingPlan = Plan::find($tenant->next_plan_id);
            if ($pendingPlan) {
                $tenant->assignPlan($pendingPlan, 'active', now()->addDays(31));
                $tenant->update(['next_plan_id' => null]);
                $this->recordPayment($tenant, $pendingPlan, $data, 'subscription');
                return;
            }
        }

        $plan = Plan::find($data['metadata']['plan_id'] ?? null) ?? $tenant->plan;
        if (!$plan || $plan->price_monthly <= 0) return;

        // Extend from whichever is later: today or current expiry (handles early renewals)
        $base      = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
            ? $tenant->subscription_expires_at
            : now();
        $expiresAt = $base->copy()->addDays(31);

        $tenant->assignPlan($plan, 'active', $expiresAt);

        if ($code = $data['customer']['customer_code'] ?? null) {
            $tenant->update(['paystack_customer_id' => $code]);
        }

        $this->recordPayment($tenant, $plan, $data, 'subscription');
    }

    /**
     * Paystack confirmed a new subscription was created — store the subscription code
     * so we can manage it later (cancel, query status, etc.).
     */
    private function handleSubscriptionCreate(array $data): void
    {
        $tenant = $this->resolveTenant($data);
        if (!$tenant) return;

        if ($sub = $data['subscription_code'] ?? null) {
            $tenant->update(['paystack_subscription_code' => $sub]);
        }
    }

    /**
     * Subscription disabled. If the user intentionally cancelled (next_plan_id is set),
     * the nightly job handles the plan switch at expiry — don't act now.
     * Otherwise this is Paystack-initiated (payment failures) — downgrade immediately.
     */
    private function handleSubscriptionDisable(array $data): void
    {
        $tenant = $this->resolveTenant($data);
        if (!$tenant) return;

        // Intentional cancellation already handled via BillingController::cancel()
        if ($tenant->next_plan_id) return;

        $freePlan = Plan::where('slug', 'free')->where('is_active', true)->first();
        if ($freePlan) {
            $tenant->assignPlan($freePlan, 'cancelled');
        }
    }

    /**
     * Recurring invoice payment failed — mark as suspended to trigger the banner
     * and give the user a grace window before full cancellation.
     */
    private function handlePaymentFailed(array $data): void
    {
        $tenant = $this->resolveTenant($data);
        if (!$tenant) return;

        $tenant->update(['subscription_status' => 'suspended']);

        try {
            Mail::to($tenant->email)->send(new PaymentFailed($tenant));
        } catch (\Throwable) {}
    }

    /**
     * Invoice updated — only act when the invoice has been paid (subscription renewal).
     */
    private function handleInvoiceUpdate(array $data): void
    {
        if (($data['status'] ?? '') !== 'success') return;
        $this->handleChargeSuccess($data);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function recordPayment(Tenant $tenant, Plan $plan, array $data, string $type): void
    {
        $ref = $data['reference'] ?? null;

        // Skip if this reference was already logged (callback may have recorded it first)
        if ($ref && SubscriptionPayment::where('paystack_reference', $ref)->exists()) return;

        $tenant->subscriptionPayments()->create([
            'plan_id'            => $plan->id,
            'paystack_reference' => $ref,
            'amount'             => ($data['amount'] ?? 0) / 100,
            'currency'           => $data['currency'] ?? 'NGN',
            'type'               => $type,
            'status'             => 'success',
            'paid_at'            => now(),
        ]);
    }

    /**
     * Resolve a Tenant from webhook data.
     * Prefers explicit metadata.tenant_id (set during checkout); falls back to
     * paystack_customer_id for renewal events that carry no metadata.
     */
    private function resolveTenant(array $data): ?Tenant
    {
        if ($id = $data['metadata']['tenant_id'] ?? null) {
            return Tenant::find($id);
        }

        if ($code = $data['customer']['customer_code'] ?? null) {
            return Tenant::where('paystack_customer_id', $code)->first();
        }

        return null;
    }
}
