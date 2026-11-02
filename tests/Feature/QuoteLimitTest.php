<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(?int $quoteLimit): Tenant
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['quotes_per_month' => $quoteLimit],
        ]);

        $tenant = Tenant::create([
            'name' => 'Quote Co', 'slug' => 'quote-co-' . uniqid(), 'email' => 'quote@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($tenant);

        return $tenant;
    }

    private function makeAdmin(Tenant $tenant): User
    {
        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function quotePayload(Customer $customer): array
    {
        return [
            'customer_id' => $customer->id,
            'quote_date'  => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ];
    }

    public function test_quote_creation_is_blocked_once_the_monthly_limit_is_reached(): void
    {
        $tenant = $this->makeTenant(2);
        $admin  = $this->makeAdmin($tenant);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cust', 'is_company' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($customer));
        $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($customer));
        $this->assertSame(2, Quote::where('tenant_id', $tenant->id)->count());

        $response = $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($customer));

        $response->assertSessionHas('error');
        $this->assertSame(2, Quote::where('tenant_id', $tenant->id)->count());
    }

    public function test_quote_creation_succeeds_under_the_limit(): void
    {
        $tenant = $this->makeTenant(5);
        $admin  = $this->makeAdmin($tenant);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cust', 'is_company' => true, 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($customer));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(1, Quote::where('tenant_id', $tenant->id)->count());
    }

    public function test_unlimited_plan_is_never_blocked(): void
    {
        $tenant = $this->makeTenant(null);
        $admin  = $this->makeAdmin($tenant);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cust', 'is_company' => true, 'is_active' => true]);

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($customer));
        }

        $this->assertSame(6, Quote::where('tenant_id', $tenant->id)->count());
    }
}
