<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create(['slug' => 'prev-' . uniqid(), 'name' => 'Prev', 'price_monthly' => 0, 'is_active' => true, 'limits' => []]);
        $tenant = Tenant::create([
            'name' => 'Prev Co', 'slug' => 'prev-co-' . uniqid(), 'email' => 'prev@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);
        app(BookkeepingService::class)->provisionDefaultAccounts($tenant);

        $this->admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'a' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $this->customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cust', 'is_company' => true, 'is_active' => true]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'customer_id'    => $this->customer->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'vat_applicable' => '1',
            'items'          => [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000]],
        ], $extra);
    }

    public function test_preview_renders_without_a_discount(): void
    {
        $this->actingAs($this->admin)->postJson(route('invoices.preview'), $this->payload())
            ->assertOk()->assertJsonStructure(['html']);
    }

    public function test_preview_renders_with_a_percentage_discount(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('invoices.preview'), $this->payload(['discount_type' => 'percent', 'discount_value' => 10]))
            ->assertOk();

        $this->assertStringContainsString('Discount (10%)', $res->json('html'));
        $this->assertStringContainsString('96,750.00', $res->json('html'));
    }

    public function test_preview_tolerates_blank_discount_fields(): void
    {
        // A cleared number input is submitted as an empty string
        $this->actingAs($this->admin)
            ->postJson(route('invoices.preview'), $this->payload(['discount_type' => '', 'discount_value' => '']))
            ->assertOk();
    }

    public function test_quote_preview_renders_with_a_discount(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('quotes.preview'), [
                'customer_id'    => $this->customer->id,
                'quote_date'     => now()->toDateString(),
                'expiry_date'    => now()->addDays(30)->toDateString(),
                'vat_applicable' => '1',
                'discount_type'  => 'percent',
                'discount_value' => 10,
                'items'          => [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000]],
            ])
            ->assertOk();

        $this->assertStringContainsString('Discount (10%)', $res->json('html'));
    }
}
