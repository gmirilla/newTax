<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionActivated;
use App\Mail\SubscriptionCancelled;
use App\Models\Plan;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly PaystackService $paystackService) {}

    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $tenant->loadMissing('plan');

        $plans = Plan::where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        // Usage meters for current month
        $usage = [
            'invoices_this_month' => $tenant->invoices()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'users'         => $tenant->users()->count(),
            'payroll_staff' => \App\Models\Employee::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->count(),
        ];

        $upgradeFeature = session('upgrade_feature') ?? $request->query('upgrade_feature');

        $tenant->loadMissing('nextPlan');

        $payments = $tenant->subscriptionPayments()
            ->with('plan')
            ->latest('paid_at')
            ->limit(12)
            ->get();

        return view('billing.index', compact('tenant', 'plans', 'usage', 'upgradeFeature', 'payments'));
    }

    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        if (!$plan->is_active || !$plan->is_public || $plan->price_monthly <= 0) {
            return redirect()->route('billing')->with('error', 'This plan is not available for checkout.');
        }

        $cycle = in_array($request->query('cycle'), ['monthly', 'yearly']) ? $request->query('cycle') : 'monthly';

        if ($cycle === 'yearly' && !$plan->price_yearly) {
            return redirect()->route('billing')->with('error', 'Annual billing is not available for this plan.');
        }

        $tenant = $request->user()->tenant;
        $tenant->loadMissing('plan');

        $samePlanSameCycle = $tenant->plan_id === $plan->id
            && $tenant->billing_cycle === $cycle
            && $tenant->subscriptionActive()
            && !$tenant->isOnTrial();

        if ($samePlanSameCycle) {
            return redirect()->route('billing')->with('error', 'You are already subscribed to this plan.');
        }

        $reference   = 'NB-' . Str::upper(Str::random(16));
        $currentPlan = $tenant->plan;

        // Proration applies only when upgrading within the same billing cycle.
        // Switching cycles (monthly ↔ yearly) is treated as a fresh subscription.
        $sameCycle = $tenant->billing_cycle === $cycle;
        $isUpgrade = $sameCycle
            && $currentPlan
            && $currentPlan->price_monthly > 0
            && $plan->price_monthly > $currentPlan->price_monthly
            && $tenant->subscriptionActive()
            && !$tenant->isOnTrial()
            && $tenant->subscription_expires_at?->isFuture();

        if ($isUpgrade) {
            $daysLeft  = max(0, (int) now()->diffInDays($tenant->subscription_expires_at));
            $divisor   = $cycle === 'yearly' ? 365 : 31;
            $priceDiff = $plan->priceForCycle($cycle) - $currentPlan->priceForCycle($cycle);
            $amount    = max(100, round($priceDiff / $divisor * $daysLeft, 2));
        } else {
            $amount = $plan->priceForCycle($cycle);
        }

        $payload = [
            'email'        => $tenant->email,
            'amount'       => (int) ($amount * 100), // NGN → kobo
            'reference'    => $reference,
            'callback_url' => route('billing.callback'),
            'channels'     => ['card', 'bank', 'ussd', 'bank_transfer'],
            'metadata'     => [
                'tenant_id'     => $tenant->id,
                'plan_id'       => $plan->id,
                'plan_name'     => $plan->name,
                'billing_cycle' => $cycle,
                'is_upgrade'    => $isUpgrade,
                'keep_expiry'   => $isUpgrade ? $tenant->subscription_expires_at?->toISOString() : null,
            ],
        ];

        // Attach Paystack plan code for recurring billing (monthly or yearly)
        $planCode = $plan->planCodeForCycle($cycle);
        if ($planCode) {
            $payload['plan'] = $planCode;
        }

        $response = $this->paystackService->initializeTransaction($payload);

        if (!($response['status'] ?? false)) {
            return redirect()->route('billing')
                ->with('error', $response['message'] ?? 'Could not initialise payment. Please try again.');
        }

        return redirect($response['data']['authorization_url']);
    }

    public function callback(Request $request): RedirectResponse
    {
        // Paystack sends both 'trxref' and 'reference' on redirect
        $reference = $request->query('trxref') ?? $request->query('reference');

        if (!$reference) {
            return redirect()->route('billing')
                ->with('error', 'Payment reference missing. Contact support if you were charged.');
        }

        $response = $this->paystackService->verifyTransaction($reference);

        if (!($response['status'] ?? false) || ($response['data']['status'] ?? '') !== 'success') {
            $reason = $response['data']['gateway_response'] ?? ($response['message'] ?? 'Payment was not successful.');
            return redirect()->route('billing')->with('error', "Payment failed: {$reason}");
        }

        $data   = $response['data'];
        $tenant = $request->user()->tenant;

        // Confirm this payment was initiated by the authenticated tenant
        if ((string) ($data['metadata']['tenant_id'] ?? '') !== (string) $tenant->id) {
            return redirect()->route('billing')
                ->with('error', 'Payment mismatch. Contact support with reference: ' . $reference);
        }

        $plan = Plan::find($data['metadata']['plan_id'] ?? null);

        if (!$plan) {
            return redirect()->route('billing')
                ->with('error', 'Plan not found. Contact support with reference: ' . $reference);
        }

        $billingCycle = in_array($data['metadata']['billing_cycle'] ?? '', ['monthly', 'yearly'])
            ? $data['metadata']['billing_cycle']
            : 'monthly';

        // Upgrades keep the current expiry (proration covers the gap to upgrade immediately).
        // Fresh subscriptions get 31 days (monthly) or 365 days (annual) from now.
        $isUpgrade  = (bool) ($data['metadata']['is_upgrade'] ?? false);
        $keepExpiry = $data['metadata']['keep_expiry'] ?? null;
        $expiresAt  = ($isUpgrade && $keepExpiry)
            ? \Carbon\Carbon::parse($keepExpiry)
            : ($billingCycle === 'yearly' ? now()->addDays(365) : now()->addDays(31));

        $tenant->assignPlan($plan, 'active', $expiresAt);

        // Persist Paystack identifiers and billing cycle
        $updates = ['billing_cycle' => $billingCycle];
        if ($code = $data['customer']['customer_code'] ?? null) {
            $updates['paystack_customer_id'] = $code;
        }
        if ($sub = $data['plan_object']['subscriptions'][0]['subscription_code'] ?? null) {
            $updates['paystack_subscription_code'] = $sub;
        }
        $tenant->update($updates);

        // Log the payment locally
        $tenant->subscriptionPayments()->create([
            'plan_id'            => $plan->id,
            'paystack_reference' => $reference,
            'amount'             => ($data['amount'] ?? 0) / 100,
            'currency'           => $data['currency'] ?? 'NGN',
            'type'               => $isUpgrade ? 'upgrade_proration' : 'subscription',
            'billing_cycle'      => $billingCycle,
            'status'             => 'success',
            'paid_at'            => now(),
        ]);

        try {
            Mail::to($tenant->email)->send(new SubscriptionActivated($tenant, $plan, $isUpgrade ? 'upgrade_proration' : 'subscription'));
        } catch (\Throwable) {}

        $verb = $isUpgrade ? 'upgraded to' : 'subscribed to';
        return redirect()->route('billing')
            ->with('success', "You have {$verb} the {$plan->name} plan. Thank you!");
    }

    /**
     * Schedule a downgrade to a lower-priced plan at end of the current billing cycle.
     * The Paystack subscription is cancelled now; access continues until subscription_expires_at.
     */
    public function downgrade(Request $request, Plan $plan): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $tenant->loadMissing('plan');

        $currentPlan = $tenant->plan;

        if (!$currentPlan || $plan->price_monthly >= $currentPlan->price_monthly) {
            return redirect()->route('billing')->with('error', 'Use the Upgrade button for higher-tier plans.');
        }

        // Cancel the Paystack recurring subscription so the user is not billed at next cycle
        $this->cancelPaystackSubscription($tenant);

        $tenant->update([
            'next_plan_id'        => $plan->id,
            'subscription_status' => 'cancelled',
        ]);

        $expiryDate = $tenant->subscription_expires_at?->format('d M Y') ?? 'end of period';

        try {
            Mail::to($tenant->email)->send(new SubscriptionCancelled($tenant, $expiryDate, $plan->name));
        } catch (\Throwable) {}

        return redirect()->route('billing')
            ->with('success', "Your plan will switch to {$plan->name} on {$expiryDate}. You keep full access until then.");
    }

    /**
     * Cancel subscription entirely — schedules a downgrade to Free at cycle end.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant->subscriptionActive() || $tenant->isOnTrial()) {
            return redirect()->route('billing')->with('error', 'No active paid subscription to cancel.');
        }

        $this->cancelPaystackSubscription($tenant);

        $freePlan = Plan::where('slug', 'free')->where('is_active', true)->first();

        $tenant->update([
            'next_plan_id'        => $freePlan?->id,
            'subscription_status' => 'cancelled',
        ]);

        $expiryDate = $tenant->subscription_expires_at?->format('d M Y') ?? 'end of period';

        try {
            Mail::to($tenant->email)->send(new SubscriptionCancelled($tenant, $expiryDate));
        } catch (\Throwable) {}

        return redirect()->route('billing')
            ->with('success', "Subscription cancelled. You will keep access to your current plan until {$expiryDate}.");
    }

    /** Disable the Paystack recurring subscription. Failures are non-fatal — logged only. */
    private function cancelPaystackSubscription(\App\Models\Tenant $tenant): void
    {
        if (!$tenant->paystack_subscription_code) return;

        try {
            $sub = $this->paystackService->getSubscription($tenant->paystack_subscription_code);
            $emailToken = $sub['data']['email_token'] ?? null;

            if ($emailToken) {
                $this->paystackService->disableSubscription(
                    $tenant->paystack_subscription_code,
                    $emailToken
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not cancel Paystack subscription', [
                'tenant_id' => $tenant->id,
                'code'      => $tenant->paystack_subscription_code,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
