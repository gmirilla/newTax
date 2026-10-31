<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralCreditLedger;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ReferralRewardedNotification;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Called after a tenant's first successful Paystack payment.
     * Finds their pending referral (if any), marks it qualified, and credits the referrer.
     */
    public function qualifyFirstPayment(Tenant $tenant, SubscriptionPayment $payment): void
    {
        // Only runs when this is the tenant's very first payment
        $isFirstPayment = $tenant->subscriptionPayments()
            ->where('status', 'success')
            ->count() === 1; // the one we just recorded

        if (!$isFirstPayment) return;

        if (!$tenant->referred_by_code) return;

        $referral = Referral::where('referee_tenant_id', $tenant->id)
            ->where('status', Referral::STATUS_PENDING)
            ->first();

        if (!$referral) return;

        $rewardNgn = 0.0;

        DB::transaction(function () use ($referral, $payment, &$rewardNgn) {
            $referrer = $referral->referrer;

            $reward = Referral::REWARD_NGN;

            // Clamp reward so referrer's balance doesn't exceed the cap
            $headroom = max(0, Referral::MAX_CREDIT_NGN - (float) $referrer->referral_credit_ngn);
            $reward   = min($reward, $headroom);

            $referral->update([
                'status'       => Referral::STATUS_REWARDED,
                'reward_ngn'   => $reward,
                'qualified_at' => now(),
                'rewarded_at'  => now(),
            ]);

            if ($reward > 0) {
                $referrer->increment('referral_credit_ngn', $reward);

                ReferralCreditLedger::create([
                    'tenant_id'               => $referrer->id,
                    'referral_id'             => $referral->id,
                    'subscription_payment_id' => $payment->id,
                    'type'                    => ReferralCreditLedger::TYPE_CREDIT,
                    'amount_ngn'              => $reward,
                    'description'             => "Referral reward — {$referral->referee->name} subscribed",
                ]);
            }

            $rewardNgn = $reward;
        });

        if ($rewardNgn > 0) {
            $referral->refresh();

            $admins = User::where('tenant_id', $referral->referrer_tenant_id)
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new ReferralRewardedNotification($referral));
            }
        }
    }

    /**
     * Deduct available credit from a checkout amount.
     * Returns the adjusted amount and the credit applied.
     * Records a debit ledger entry against the tenant.
     */
    public function applyCredit(Tenant $tenant, float $amount): array
    {
        $balance = (float) $tenant->referral_credit_ngn;

        if ($balance <= 0) {
            return ['amount' => $amount, 'credit_applied' => 0.0];
        }

        $creditApplied = min($balance, $amount);
        $adjustedAmount = round($amount - $creditApplied, 2);

        $tenant->decrement('referral_credit_ngn', $creditApplied);

        ReferralCreditLedger::create([
            'tenant_id'   => $tenant->id,
            'referral_id' => null,
            'type'        => ReferralCreditLedger::TYPE_DEBIT,
            'amount_ngn'  => $creditApplied,
            'description' => 'Credit applied to subscription renewal',
        ]);

        return ['amount' => $adjustedAmount, 'credit_applied' => $creditApplied];
    }

    /**
     * Link the subscription_payment_id onto the debit ledger entry created during checkout,
     * called after the payment record is persisted.
     */
    public function linkPaymentToDebit(Tenant $tenant, SubscriptionPayment $payment): void
    {
        ReferralCreditLedger::where('tenant_id', $tenant->id)
            ->where('type', ReferralCreditLedger::TYPE_DEBIT)
            ->whereNull('subscription_payment_id')
            ->latest()
            ->first()
            ?->update(['subscription_payment_id' => $payment->id]);
    }
}
