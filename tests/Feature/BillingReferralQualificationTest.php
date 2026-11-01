<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingReferralQualificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_callback_qualifies_a_pending_referral_on_first_payment(): void
    {
        Mail::fake();

        $plan = Plan::create([
            'name' => 'Business', 'slug' => 'business', 'price_monthly' => 5000,
            'price_yearly' => 50000, 'limits' => [], 'is_active' => true, 'is_public' => true,
        ]);

        $referrerTenant = Tenant::create([
            'name' => 'Referrer Co', 'slug' => 'referrer-co', 'email' => 'referrer@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);

        $refereeTenant = Tenant::create([
            'name' => 'Referee Co', 'slug' => 'referee-co', 'email' => 'referee@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
            'plan_id' => $plan->id, 'subscription_status' => 'trialing', 'referred_by_code' => 'REFCODE1',
        ]);

        $referral = Referral::create([
            'referrer_tenant_id' => $referrerTenant->id,
            'referee_tenant_id'  => $refereeTenant->id,
            'status'             => Referral::STATUS_PENDING,
        ]);

        $admin = User::forceCreate([
            'tenant_id' => $refereeTenant->id, 'name' => 'Referee Admin', 'email' => 'admin@referee.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data'   => [
                    'status'   => 'success',
                    'amount'   => 5000000, // kobo
                    'currency' => 'NGN',
                    'customer' => ['customer_code' => 'CUS_test123'],
                    'plan_object' => ['subscriptions' => []],
                    'metadata' => [
                        'tenant_id'      => $refereeTenant->id,
                        'plan_id'        => $plan->id,
                        'billing_cycle'  => 'yearly',
                        'is_upgrade'     => false,
                        'credit_applied' => 0,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('billing.callback', ['reference' => 'NB-TESTREF123']));

        $response->assertRedirect(route('billing'));

        $referral->refresh();
        $refereeTenant->refresh();
        $referrerTenant->refresh();

        $this->assertSame(Referral::STATUS_REWARDED, $referral->status);
        $this->assertEquals(Referral::REWARD_NGN, (float) $referral->reward_ngn);
        $this->assertEquals(Referral::REWARD_NGN, (float) $referrerTenant->referral_credit_ngn);
    }
}
