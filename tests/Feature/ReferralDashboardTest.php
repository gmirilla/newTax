<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Dashboard Test Co',
            'slug'                    => 'dashboard-test-co',
            'email'                   => 'dashboard@testco.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->admin = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Dashboard Admin',
            'email'             => 'admin@dashboardtest.ng',
            'password'          => bcrypt('password'),
            'role'              => User::ROLE_ADMIN,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->accountant = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Dashboard Accountant',
            'email'             => 'accountant@dashboardtest.ng',
            'password'          => bcrypt('password'),
            'role'              => User::ROLE_ACCOUNTANT,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_view_the_referral_dashboard_and_gets_a_referral_code(): void
    {
        $this->assertNull($this->tenant->referral_code);

        $response = $this->actingAs($this->admin)->get(route('referrals.index'));

        $response->assertOk();
        $response->assertSee('Your Referral Link');

        $this->assertNotNull($this->tenant->refresh()->referral_code);
    }

    public function test_dashboard_shows_correct_successful_referral_stats(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Referee Co', 'slug' => 'referee-co', 'email' => 'referee@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true,
        ]);
        $anotherTenant = Tenant::create([
            'name' => 'Referee Co 2', 'slug' => 'referee-co-2', 'email' => 'referee2@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true,
        ]);

        Referral::create([
            'referrer_tenant_id' => $this->tenant->id,
            'referee_tenant_id'  => $otherTenant->id,
            'status'             => Referral::STATUS_REWARDED,
            'reward_ngn'         => Referral::REWARD_NGN,
            'rewarded_at'        => now(),
        ]);
        Referral::create([
            'referrer_tenant_id' => $this->tenant->id,
            'referee_tenant_id'  => $anotherTenant->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('referrals.index'));

        $response->assertOk();
        $response->assertViewHas('stats', [
            'total'    => 2,
            'rewarded' => 1,
            'pending'  => 1,
        ]);
    }

    public function test_non_admin_role_cannot_view_the_referral_dashboard(): void
    {
        $response = $this->actingAs($this->accountant)->get(route('referrals.index'));

        $response->assertRedirect(route('dashboard'));
    }
}
