<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralCreditLedger;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ReferralRewardedNotification;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $service;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReferralService::class);

        $this->plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        static $i = 0;
        $i++;

        return Tenant::create(array_merge([
            'name'                    => "Referral Test Co {$i}",
            'slug'                    => "referral-test-co-{$i}",
            'email'                   => "referral{$i}@testco.ng",
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $this->plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
            'referral_credit_ngn'     => 0,
        ], $overrides));
    }

    private function makeAdmin(Tenant $tenant, array $overrides = []): User
    {
        static $i = 0;
        $i++;

        return User::forceCreate(array_merge([
            'tenant_id'         => $tenant->id,
            'name'              => "Admin {$i}",
            'email'             => "admin{$i}@referraltest.ng",
            'password'          => bcrypt('password'),
            'role'              => User::ROLE_ADMIN,
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function makeFirstPayment(Tenant $tenant): SubscriptionPayment
    {
        return SubscriptionPayment::create([
            'tenant_id'           => $tenant->id,
            'plan_id'             => $this->plan->id,
            'paystack_reference'  => 'PSK-' . uniqid(),
            'amount'              => 25000,
            'currency'            => 'NGN',
            'type'                => 'subscription',
            'billing_cycle'       => 'monthly',
            'status'              => 'success',
            'paid_at'             => now(),
        ]);
    }

    // ── qualifyFirstPayment ──────────────────────────────────────────────────

    public function test_first_payment_rewards_the_referrer_and_notifies_their_admin(): void
    {
        Notification::fake();

        $referrer = $this->makeTenant();
        $referrerAdmin = $this->makeAdmin($referrer);

        $referee = $this->makeTenant(['referred_by_code' => 'REFCODE1']);

        $referral = Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);

        $payment = $this->makeFirstPayment($referee);

        $this->service->qualifyFirstPayment($referee, $payment);

        $referral->refresh();
        $referrer->refresh();

        $this->assertEquals(Referral::STATUS_REWARDED, $referral->status);
        $this->assertEquals(Referral::REWARD_NGN, (float) $referral->reward_ngn);
        $this->assertNotNull($referral->qualified_at);
        $this->assertNotNull($referral->rewarded_at);
        $this->assertEquals(Referral::REWARD_NGN, (float) $referrer->referral_credit_ngn);

        $this->assertDatabaseHas('referral_credit_ledger', [
            'tenant_id'               => $referrer->id,
            'referral_id'             => $referral->id,
            'subscription_payment_id' => $payment->id,
            'type'                    => ReferralCreditLedger::TYPE_CREDIT,
            'amount_ngn'              => Referral::REWARD_NGN,
        ]);

        Notification::assertSentTo($referrerAdmin, ReferralRewardedNotification::class);
    }

    public function test_reward_is_clamped_to_remaining_headroom_under_the_cap(): void
    {
        Notification::fake();

        $referrer = $this->makeTenant(['referral_credit_ngn' => Referral::MAX_CREDIT_NGN - 500]);
        $this->makeAdmin($referrer);

        $referee = $this->makeTenant(['referred_by_code' => 'REFCODE2']);
        $referral = Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);

        $payment = $this->makeFirstPayment($referee);

        $this->service->qualifyFirstPayment($referee, $payment);

        $referral->refresh();
        $referrer->refresh();

        $this->assertEquals(500.0, (float) $referral->reward_ngn);
        $this->assertEquals((float) Referral::MAX_CREDIT_NGN, (float) $referrer->referral_credit_ngn);
    }

    public function test_no_reward_or_notification_when_referrer_is_already_at_the_cap(): void
    {
        Notification::fake();

        $referrer = $this->makeTenant(['referral_credit_ngn' => Referral::MAX_CREDIT_NGN]);
        $referrerAdmin = $this->makeAdmin($referrer);

        $referee = $this->makeTenant(['referred_by_code' => 'REFCODE3']);
        $referral = Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);

        $payment = $this->makeFirstPayment($referee);

        $this->service->qualifyFirstPayment($referee, $payment);

        $referral->refresh();
        $referrer->refresh();

        $this->assertEquals(Referral::STATUS_REWARDED, $referral->status);
        $this->assertEquals(0.0, (float) $referral->reward_ngn);
        $this->assertEquals((float) Referral::MAX_CREDIT_NGN, (float) $referrer->referral_credit_ngn);

        $this->assertDatabaseMissing('referral_credit_ledger', ['referral_id' => $referral->id]);
        Notification::assertNotSentTo($referrerAdmin, ReferralRewardedNotification::class);
    }

    public function test_qualify_is_a_no_op_when_this_is_not_the_tenants_first_payment(): void
    {
        $referrer = $this->makeTenant();
        $referee  = $this->makeTenant();

        Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);

        // Two successful payments already exist — this is not their first.
        $this->makeFirstPayment($referee);
        $second = $this->makeFirstPayment($referee);

        $this->service->qualifyFirstPayment($referee, $second);

        $this->assertDatabaseHas('referrals', [
            'referee_tenant_id' => $referee->id,
            'status'            => Referral::STATUS_PENDING,
        ]);
    }

    public function test_qualify_is_a_no_op_when_tenant_was_not_referred(): void
    {
        $referee = $this->makeTenant();
        $payment = $this->makeFirstPayment($referee);

        // Should not throw even though no Referral row exists at all.
        $this->service->qualifyFirstPayment($referee, $payment);

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_qualify_is_a_no_op_when_no_pending_referral_row_exists(): void
    {
        $referrer = $this->makeTenant();
        $referee  = $this->makeTenant(['referred_by_code' => 'REFCODE4']);

        // Referral already rewarded previously — should not be touched again.
        $referral = Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_REWARDED,
            'reward_ngn'         => Referral::REWARD_NGN,
            'rewarded_at'        => now()->subDay(),
        ]);

        $payment = $this->makeFirstPayment($referee);

        $this->service->qualifyFirstPayment($referee, $payment);

        $referral->refresh();
        $this->assertEqualsWithDelta(
            $referral->rewarded_at->timestamp,
            now()->subDay()->timestamp,
            2
        );
    }

    // ── applyCredit ──────────────────────────────────────────────────────────

    public function test_apply_credit_partially_covers_a_larger_amount(): void
    {
        $tenant = $this->makeTenant(['referral_credit_ngn' => 1_000]);

        $result = $this->service->applyCredit($tenant, 25_000);

        $this->assertEquals(1_000.0, $result['credit_applied']);
        $this->assertEquals(24_000.0, $result['amount']);
        $this->assertEquals(0.0, (float) $tenant->refresh()->referral_credit_ngn);

        $this->assertDatabaseHas('referral_credit_ledger', [
            'tenant_id'  => $tenant->id,
            'type'       => ReferralCreditLedger::TYPE_DEBIT,
            'amount_ngn' => 1_000,
        ]);
    }

    public function test_apply_credit_fully_covers_a_smaller_amount(): void
    {
        $tenant = $this->makeTenant(['referral_credit_ngn' => 20_000]);

        $result = $this->service->applyCredit($tenant, 5_000);

        $this->assertEquals(5_000.0, $result['credit_applied']);
        $this->assertEquals(0.0, $result['amount']);
        $this->assertEquals(15_000.0, (float) $tenant->refresh()->referral_credit_ngn);
    }

    public function test_apply_credit_is_a_no_op_with_zero_balance(): void
    {
        $tenant = $this->makeTenant(['referral_credit_ngn' => 0]);

        $result = $this->service->applyCredit($tenant, 5_000);

        $this->assertEquals(0.0, $result['credit_applied']);
        $this->assertEquals(5_000.0, $result['amount']);
        $this->assertDatabaseCount('referral_credit_ledger', 0);
    }

    // ── linkPaymentToDebit ───────────────────────────────────────────────────

    public function test_link_payment_to_debit_links_the_latest_unlinked_entry(): void
    {
        $tenant = $this->makeTenant(['referral_credit_ngn' => 0]);

        $older = ReferralCreditLedger::create([
            'tenant_id'   => $tenant->id,
            'type'        => ReferralCreditLedger::TYPE_DEBIT,
            'amount_ngn'  => 500,
            'description' => 'Older debit',
        ]);
        $older->created_at = now()->subMinute();
        $older->save();

        $latest = ReferralCreditLedger::create([
            'tenant_id'   => $tenant->id,
            'type'        => ReferralCreditLedger::TYPE_DEBIT,
            'amount_ngn'  => 800,
            'description' => 'Latest debit',
        ]);

        $payment = $this->makeFirstPayment($tenant);

        $this->service->linkPaymentToDebit($tenant, $payment);

        $this->assertEquals($payment->id, $latest->refresh()->subscription_payment_id);
        $this->assertNull($older->refresh()->subscription_payment_id);
    }
}
