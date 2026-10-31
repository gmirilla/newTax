<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $tenantAdmin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::forceCreate([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@platform.test',
            'password'          => bcrypt('password'),
            'role'              => User::ROLE_ADMIN,
            'is_superadmin'     => true,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Report Test Co', 'slug' => 'report-test-co', 'email' => 'report@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true,
        ]);

        $this->tenantAdmin = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Report Tenant Admin',
            'email'             => 'admin@reporttest.ng',
            'password'          => bcrypt('password'),
            'role'              => User::ROLE_ADMIN,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_superadmin_can_view_the_referral_report_with_correct_stats(): void
    {
        $referee = Tenant::create([
            'name' => 'Referee Co', 'slug' => 'referee-co', 'email' => 'referee@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true,
        ]);

        Referral::create([
            'referrer_tenant_id' => $this->tenant->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => Referral::STATUS_REWARDED,
            'reward_ngn'         => Referral::REWARD_NGN,
            'rewarded_at'        => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.referrals.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['total'] === 1
                && $stats['pending'] === 0
                && $stats['rewarded'] === 1
                && (float) $stats['credit_ngn'] === (float) Referral::REWARD_NGN;
        });
    }

    public function test_non_superadmin_is_blocked_from_the_referral_report(): void
    {
        $response = $this->actingAs($this->tenantAdmin)->get(route('superadmin.referrals.index'));

        $response->assertForbidden();
    }
}
