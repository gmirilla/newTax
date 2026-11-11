<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TrialDowngradeLimitWindowTest extends TestCase
{
    use RefreshDatabase;

    private function setup_tenant(): array
    {
        $free = Plan::create([
            'slug' => 'free', 'name' => 'Free', 'price_monthly' => 0, 'is_active' => true,
            'limits' => ['invoices_per_month' => 4],
        ]);
        $trial = Plan::create([
            'slug' => 'growth', 'name' => 'Growth', 'price_monthly' => 5000, 'is_active' => true,
            'trial_days' => 14, 'limits' => ['invoices_per_month' => null],
        ]);
        $tenant = Tenant::create([
            'name' => 'T', 'slug' => 't-' . uniqid(), 'email' => 't@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1, 'currency' => 'NGN', 'is_active' => true,
        ]);
        $tenant->assignPlan($trial, 'trialing', null, now()->addDays(14));

        return [$tenant->fresh(), $free];
    }

    private function makeInvoices(Tenant $tenant, int $n): void
    {
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'C', 'is_company' => true, 'is_active' => true]);
        $user = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'a-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
        for ($i = 0; $i < $n; $i++) {
            Invoice::withoutGlobalScope('tenant')->forceCreate([
                'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'created_by' => $user->id,
                'invoice_number' => 'INV-' . uniqid(), 'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(), 'status' => 'draft',
                'subtotal' => 0, 'vat_amount' => 0, 'total_amount' => 0, 'amount_paid' => 0, 'balance_due' => 0,
            ]);
        }
    }

    public function test_trial_invoices_do_not_count_against_free_limit_after_downgrade(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));
        [$tenant, $free] = $this->setup_tenant();
        $this->makeInvoices($tenant, 6); // heavy trial usage

        $this->travel(5)->days();
        $tenant->refresh()->assignPlan($free, 'active');
        $tenant->refresh();

        $this->assertTrue($tenant->withinLimit('invoices_per_month'));

        $this->makeInvoices($tenant, 3);
        Cache::flush();
        $this->assertTrue($tenant->withinLimit('invoices_per_month'));

        $this->makeInvoices($tenant, 1);
        Cache::flush();
        $this->assertFalse($tenant->withinLimit('invoices_per_month'));
    }

    public function test_window_resets_to_start_of_month_next_month(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));
        [$tenant, $free] = $this->setup_tenant();
        $tenant->assignPlan($free, 'active');

        $this->travelTo(now()->addMonth()->startOfMonth()->addDays(2));
        $this->assertTrue($tenant->refresh()->monthlyLimitWindowStart()->equalTo(now()->startOfMonth()));
    }

    public function test_free_tenant_who_never_trialed_counts_from_start_of_month(): void
    {
        [$tenant, $free] = $this->setup_tenant();
        $tenant->update(['subscription_status' => 'active', 'trial_ends_at' => null, 'plan_id' => $free->id]);

        $this->assertTrue($tenant->refresh()->monthlyLimitWindowStart()->equalTo(now()->startOfMonth()));
    }
}
