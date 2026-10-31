<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Tenant;
use App\Services\TenancyService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function tenantData(array $overrides = []): array
    {
        static $i = 0;
        $i++;

        return array_merge([
            'name'  => "New Co {$i}",
            'email' => "newco{$i}@test.ng",
        ], $overrides);
    }

    private function adminData(array $overrides = []): array
    {
        static $i = 0;
        $i++;

        return array_merge([
            'name'     => "Founder {$i}",
            'email'    => "founder{$i}@test.ng",
            'password' => 'password123',
        ], $overrides);
    }

    public function test_registering_with_a_valid_referral_code_creates_a_pending_referral(): void
    {
        $referrer = Tenant::create([
            'name'          => 'Referrer Co',
            'slug'          => 'referrer-co',
            'email'         => 'referrer@test.ng',
            'tax_category'  => 'small',
            'annual_turnover' => 1_000_000,
            'currency'      => 'NGN',
            'is_active'     => true,
            'referral_code' => 'VALIDCODE',
        ]);

        $result = app(TenancyService::class)->registerTenant(
            $this->tenantData(['referred_by_code' => 'VALIDCODE']),
            $this->adminData()
        );

        $newTenant = $result['tenant'];

        $this->assertEquals('VALIDCODE', $newTenant->referred_by_code);

        $this->assertDatabaseHas('referrals', [
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $newTenant->id,
            'status'             => Referral::STATUS_PENDING,
        ]);
    }

    public function test_registering_with_an_invalid_referral_code_is_silently_dropped(): void
    {
        $result = app(TenancyService::class)->registerTenant(
            $this->tenantData(['referred_by_code' => 'NOT-A-REAL-CODE']),
            $this->adminData()
        );

        $newTenant = $result['tenant'];

        $this->assertNull($newTenant->referred_by_code);
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_registering_without_a_referral_code_creates_no_referral(): void
    {
        $result = app(TenancyService::class)->registerTenant(
            $this->tenantData(),
            $this->adminData()
        );

        $this->assertNull($result['tenant']->referred_by_code);
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_self_referral_is_rejected_by_the_database_constraint(): void
    {
        $tenant = Tenant::create([
            'name'          => 'Solo Co',
            'slug'          => 'solo-co',
            'email'         => 'solo@test.ng',
            'tax_category'  => 'small',
            'annual_turnover' => 1_000_000,
            'currency'      => 'NGN',
            'is_active'     => true,
            'referral_code' => 'SOLOCODE',
        ]);

        $this->expectException(QueryException::class);

        Referral::create([
            'referrer_tenant_id' => $tenant->id,
            'referee_tenant_id'  => $tenant->id,
            'status'             => Referral::STATUS_PENDING,
            'reward_ngn'         => Referral::REWARD_NGN,
        ]);
    }
}
