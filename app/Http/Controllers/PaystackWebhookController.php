<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Services\PaymentCredentialResolver;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaystackWebhookController extends Controller
{
    /** A failed event is re-run on redelivery up to this many times in total. */
    private const MAX_ATTEMPTS = 5;

    /** A row left in 'processing' longer than this is assumed to belong to a crashed request. */
    private const STUCK_AFTER_MINUTES = 5;

    public function handle(Request $request, PaymentCredentialResolver $credentials): JsonResponse
    {
        // ── 1. Verify HMAC-SHA512 signature ──────────────────────────────────
        $secretKey = $credentials->secretKey();
        if (empty($secretKey)) {
            Log::error('Paystack webhook: PAYSTACK_SECRET_KEY not configured');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        $signature = $request->header('x-paystack-signature', '');
        $computed  = hash_hmac('sha512', $request->getContent(), $secretKey);

        if (!hash_equals($computed, $signature)) {
            Log::warning('Paystack webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload   = $request->json()->all();
        $eventType = $payload['event'] ?? 'unknown';
        $eventId   = (string) ($payload['data']['id'] ?? '');

        // ── 2. Idempotency / retry check ──────────────────────────────────────
        $log = null;

        if ($eventId && ($existing = WebhookEvent::where('source', 'paystack')->where('event_id', $eventId)->first())) {
            if ($existing->status === 'processed') {
                return response()->json(['status' => 'already_processed']);
            }

            $stuck = $existing->status === 'processing'
                && $existing->updated_at->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES));

            // Another delivery is genuinely running right now.
            if ($existing->status === 'processing' && ! $stuck) {
                return response()->json(['status' => 'already_processing']);
            }

            // Retry budget exhausted — stop asking Paystack to resend; needs manual attention.
            if ($existing->attempts >= self::MAX_ATTEMPTS) {
                return response()->json(['status' => 'gave_up']);
            }

            // A previously failed (or crashed mid-flight) event: run it again.
            $existing->update([
                'status'        => 'processing',
                'attempts'      => $existing->attempts + 1,
                'error_message' => null,
            ]);
            $log = $existing;
        }

        // ── 3. Log the incoming event ─────────────────────────────────────────
        if (! $log) {
            try {
                $log = WebhookEvent::create([
                    'source'     => 'paystack',
                    'event_type' => $eventType,
                    'event_id'   => $eventId ?: null,
                    'payload'    => $payload,
                    'status'     => 'processing',
                    'attempts'   => 1,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // Lost a race with a concurrent delivery of the same event.
                return response()->json(['status' => 'already_processing']);
            }
        }

        // ── 4. Dispatch to handler ────────────────────────────────────────────
        try {
            $this->dispatch($eventType, $payload['data'] ?? []);
            $log->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('Paystack webhook processing failed', [
                'event'    => $eventType,
                'eventId'  => $eventId,
                'attempt'  => $log->attempts,
                'error'    => $e->getMessage(),
            ]);

            // Non-200 makes Paystack redeliver, but only while attempts remain; after that
            // answer 200 so it stops retrying (handlers are idempotent, see handleChargeSuccess).
            if ($log->attempts < self::MAX_ATTEMPTS) {
                return response()->json(['status' => 'failed_will_retry'], 500);
            }
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

        // Idempotent: if this charge was already applied (by the checkout callback or by an
        // earlier delivery of this event) do nothing — otherwise a redelivery would extend
        // the subscription a second time.
        $ref = $data['reference'] ?? null;
        if ($ref && SubscriptionPayment::where('paystack_reference', $ref)->exists()) {
            return;
        }

        $metaCycle = in_array($data['metadata']['billing_cycle'] ?? '', ['monthly', 'yearly'], true)
            ? $data['metadata']['billing_cycle']
            : null;
        // Renewals carry no metadata, so fall back to the cycle saved at checkout.
        $cycle = $metaCycle ?? ($tenant->billing_cycle === 'yearly' ? 'yearly' : 'monthly');
        $days  = $cycle === 'yearly' ? 365 : 31;

        $payment = null;
        $plan    = null;

        // Plan change + expiry + payment record commit together so a failure part-way
        // can be retried cleanly.
        DB::transaction(function () use ($tenant, $data, $cycle, $metaCycle, $days, &$payment, &$plan) {
            // If a downgrade is pending, apply it on this renewal instead of extending current plan
            if ($tenant->next_plan_id && ($pendingPlan = Plan::find($tenant->next_plan_id))) {
                $tenant->assignPlan($pendingPlan, 'active', now()->addDays($days));
                $tenant->update(['next_plan_id' => null]);
                $this->recordPayment($tenant, $pendingPlan, $data, 'subscription', $cycle);
                return;
            }

            $plan = Plan::find($data['metadata']['plan_id'] ?? null) ?? $tenant->plan;
            if (!$plan || $plan->price_monthly <= 0) {
                $plan = null;
                return;
            }

            // Upgrades keep the current expiry (proration covers the gap).
            $keepExpiry = ($data['metadata']['is_upgrade'] ?? false) ? ($data['metadata']['keep_expiry'] ?? null) : null;

            if ($keepExpiry) {
                $expiresAt = \Carbon\Carbon::parse($keepExpiry);
            } else {
                // Extend from whichever is later: today or current expiry (handles early renewals)
                $base      = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
                    ? $tenant->subscription_expires_at
                    : now();
                $expiresAt = $base->copy()->addDays($days);
            }

            $tenant->assignPlan($plan, 'active', $expiresAt);

            $updates = [];
            if ($code = $data['customer']['customer_code'] ?? null) {
                $updates['paystack_customer_id'] = $code;
            }
            if ($metaCycle) {
                $updates['billing_cycle'] = $metaCycle;
            }
            if ($updates) {
                $tenant->update($updates);
            }

            $payment = $this->recordPayment($tenant, $plan, $data, ($data['metadata']['is_upgrade'] ?? false) ? 'upgrade_proration' : 'subscription', $cycle);
        });

        if ($payment) {
            if ($ledgerId = (int) ($data['metadata']['credit_ledger_id'] ?? 0)) {
                app(ReferralService::class)->linkPaymentToDebitById($ledgerId, $payment);
            }
            app(ReferralService::class)->qualifyFirstPayment($tenant, $payment);
        }
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
     * Paystack invoice.update nests the actual charge inside data.transaction, so we
     * remap it into the shape handleChargeSuccess expects before delegating.
     */
    private function handleInvoiceUpdate(array $data): void
    {
        if (($data['status'] ?? '') !== 'success') return;

        // Merge transaction-level fields (reference, amount, currency) with the
        // invoice envelope so resolveTenant() and recordPayment() find the right keys.
        $transaction = $data['transaction'] ?? [];
        $normalized  = array_merge($data, $transaction);

        $this->handleChargeSuccess($normalized);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function recordPayment(Tenant $tenant, Plan $plan, array $data, string $type, ?string $cycle = null): ?SubscriptionPayment
    {
        $ref = $data['reference'] ?? null;

        // Skip if this reference was already logged (callback may have recorded it first)
        if ($ref && SubscriptionPayment::where('paystack_reference', $ref)->exists()) return null;

        return $tenant->subscriptionPayments()->create([
            'plan_id'            => $plan->id,
            'paystack_reference' => $ref,
            'amount'             => ($data['amount'] ?? 0) / 100,
            'currency'           => $data['currency'] ?? 'NGN',
            'type'               => $type,
            'billing_cycle'      => $cycle,
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
