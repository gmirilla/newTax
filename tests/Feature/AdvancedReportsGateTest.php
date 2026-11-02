<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedReportsGateTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantAndAdmin(bool $advancedReports): array
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['advanced_reports' => $advancedReports],
        ]);

        $tenant = Tenant::create([
            'name' => 'Report Co', 'slug' => 'report-co-' . uniqid(), 'email' => 'report@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($tenant);

        $admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        return [$tenant, $admin];
    }

    public function test_ledger_balance_sheet_and_trial_balance_are_blocked_without_the_plan_flag(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(false);

        $this->actingAs($admin)->get(route('reports.ledger'))->assertRedirect(route('billing'));
        $this->actingAs($admin)->get(route('reports.bs'))->assertRedirect(route('billing'));
        $this->actingAs($admin)->get(route('reports.tb'))->assertRedirect(route('billing'));
    }

    public function test_ledger_balance_sheet_and_trial_balance_are_allowed_with_the_plan_flag(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(true);

        $this->actingAs($admin)->get(route('reports.ledger'))->assertOk();
        $this->actingAs($admin)->get(route('reports.bs'))->assertOk();
        $this->actingAs($admin)->get(route('reports.tb'))->assertOk();
    }

    public function test_profit_and_loss_and_vat_report_remain_ungated(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(false);

        $this->actingAs($admin)->get(route('reports.pl'))->assertOk();
        $this->actingAs($admin)->get(route('reports.vat'))->assertOk();
    }
}
