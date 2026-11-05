<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotePublicLinkTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuote(string $status = 'sent'): Quote
    {
        $plan = Plan::create(['slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0, 'is_active' => true, 'limits' => []]);

        $tenant = Tenant::create([
            'name' => 'Quote Co', 'slug' => 'quote-co-' . uniqid(), 'email' => 'quote-' . uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($tenant);

        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cust Co', 'is_company' => true, 'is_active' => true]);

        $admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        $quote = Quote::withoutGlobalScope('tenant')->create([
            'tenant_id'   => $tenant->id,
            'customer_id' => $customer->id,
            'quote_number' => 'QUO-TEST-' . uniqid(),
            'quote_date'  => now(),
            'expiry_date' => now()->addDays(30),
            'status'      => $status,
            'subtotal'    => 1000,
            'total_amount'=> 1000,
            'created_by'  => $admin->id,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id, 'description' => 'Widget', 'quantity' => 1,
            'unit_price' => 1000, 'subtotal' => 1000, 'total' => 1000,
        ]);

        return $quote;
    }

    public function test_public_token_is_generated_automatically_on_creation(): void
    {
        $quote = $this->makeQuote();

        $this->assertNotEmpty($quote->public_token);
    }

    public function test_public_quote_page_is_viewable_without_authentication(): void
    {
        $quote = $this->makeQuote();

        $response = $this->get(route('quote.public.show', $quote->public_token));

        $response->assertOk();
        $response->assertViewIs('quotes.public');
        $response->assertSee($quote->quote_number);
    }

    public function test_an_unknown_token_returns_404(): void
    {
        $response = $this->get(route('quote.public.show', 'not-a-real-token'));

        $response->assertNotFound();
    }

    public function test_public_pdf_download_works_without_authentication(): void
    {
        $quote = $this->makeQuote();

        $response = $this->get(route('quote.public.pdf', $quote->public_token));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_copy_link_button_is_hidden_for_draft_quotes(): void
    {
        $draft = $this->makeQuote('draft');

        $this->actingAs($draft->creator)
            ->get(route('quotes.show', $draft))
            ->assertOk()
            ->assertDontSee('Copy Link');
    }

    public function test_copy_link_button_is_shown_once_a_quote_is_sent(): void
    {
        $sent = $this->makeQuote('sent');

        $this->actingAs($sent->creator)
            ->get(route('quotes.show', $sent))
            ->assertOk()
            ->assertSee('Copy Link');
    }
}
