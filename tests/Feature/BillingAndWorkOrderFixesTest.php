<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceWorkOrder;
use App\Models\MaintenanceWorkOrderPart;
use App\Models\Plan;
use App\Models\ReferralCreditLedger;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\MaintenanceService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression tests for: annual renewal length, webhook retry, referral credit
 * restore on failed/abandoned checkout, and work-order part ownership.
 */
class BillingAndWorkOrderFixesTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_secret';

    private Plan $paid;
    private Plan $free;

    protected function setUp(): void
    {
        parent::setUp();

        config(['paystack.secret_key' => self::SECRET]);

        $this->free = Plan::create([
            'slug' => 'free', 'name' => 'Free', 'price_monthly' => 0, 'is_active' => true, 'is_public' => true,
            'limits' => ['maintenance' => true],
        ]);
        $this->paid = Plan::create([
            'slug' => 'business', 'name' => 'Business', 'price_monthly' => 5000, 'price_yearly' => 50000,
            'is_active' => true, 'is_public' => true, 'limits' => ['maintenance' => true],
        ]);
    }

    private function tenant(array $attrs = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Co ' . uniqid(), 'slug' => 'co-' . uniqid(), 'email' => uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
            'plan_id' => $this->paid->id, 'subscription_status' => 'active',
            'subscription_expires_at' => now()->addDays(5), 'billing_cycle' => 'monthly',
            'paystack_customer_id' => 'CUS_' . uniqid(),
        ], $attrs));
    }

    private function admin(Tenant $tenant): User
    {
        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'a-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function webhook(array $payload)
    {
        $body = json_encode($payload);

        return $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, self::SECRET),
        ], $body);
    }

    private function charge(Tenant $tenant, int $eventId, array $extra = []): array
    {
        return [
            'event' => 'charge.success',
            'data'  => array_merge([
                'id' => $eventId, 'reference' => 'REF-' . $eventId, 'amount' => 5000000, 'currency' => 'NGN',
                'customer' => ['customer_code' => $tenant->paystack_customer_id],
            ], $extra),
        ];
    }

    // ── 2. Renewal length follows the billing cycle ─────────────────────────────

    public function test_yearly_renewal_extends_by_a_year_not_31_days(): void
    {
        $tenant = $this->tenant(['billing_cycle' => 'yearly']);
        $before = $tenant->subscription_expires_at->copy();

        $this->webhook($this->charge($tenant, 1001))->assertOk();

        $this->assertEquals(
            $before->copy()->addDays(365)->toDateString(),
            $tenant->refresh()->subscription_expires_at->toDateString()
        );
    }

    public function test_monthly_renewal_still_extends_31_days(): void
    {
        $tenant = $this->tenant(['billing_cycle' => 'monthly']);
        $before = $tenant->subscription_expires_at->copy();

        $this->webhook($this->charge($tenant, 1002))->assertOk();

        $this->assertEquals(
            $before->copy()->addDays(31)->toDateString(),
            $tenant->refresh()->subscription_expires_at->toDateString()
        );
    }

    public function test_pending_downgrade_on_a_yearly_renewal_gets_a_year(): void
    {
        $tenant = $this->tenant(['billing_cycle' => 'yearly', 'next_plan_id' => $this->free->id]);

        $this->webhook($this->charge($tenant, 1003))->assertOk();

        $tenant->refresh();
        $this->assertSame($this->free->id, $tenant->plan_id);
        $this->assertEquals(now()->addDays(365)->toDateString(), $tenant->subscription_expires_at->toDateString());
    }

    public function test_charge_already_recorded_by_the_checkout_callback_is_not_extended_again(): void
    {
        $tenant = $this->tenant(['billing_cycle' => 'yearly']);
        $expiry = $tenant->subscription_expires_at->copy();

        SubscriptionPayment::create([
            'tenant_id' => $tenant->id, 'plan_id' => $this->paid->id, 'paystack_reference' => 'REF-1004',
            'amount' => 50000, 'currency' => 'NGN', 'type' => 'subscription', 'status' => 'success', 'paid_at' => now(),
        ]);

        $this->webhook($this->charge($tenant, 1004))->assertOk();

        $this->assertEquals($expiry->toDateString(), $tenant->refresh()->subscription_expires_at->toDateString());
    }

    public function test_redelivered_processed_event_does_not_extend_twice(): void
    {
        $tenant  = $this->tenant(['billing_cycle' => 'monthly']);
        $payload = $this->charge($tenant, 1005);

        $this->webhook($payload)->assertOk();
        $after = $tenant->refresh()->subscription_expires_at->copy();

        $this->webhook($payload)->assertJson(['status' => 'already_processed']);

        $this->assertEquals($after->toDateString(), $tenant->refresh()->subscription_expires_at->toDateString());
        $this->assertSame(1, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
    }

    // ── 4. Failed webhook events can be retried ─────────────────────────────────

    private function seedEvent(array $payload, string $status, int $attempts = 1, ?\DateTimeInterface $updatedAt = null): WebhookEvent
    {
        $event = WebhookEvent::create([
            'source' => 'paystack', 'event_type' => $payload['event'], 'event_id' => (string) $payload['data']['id'],
            'payload' => $payload, 'status' => $status, 'attempts' => $attempts, 'error_message' => 'boom',
        ]);

        if ($updatedAt) {
            WebhookEvent::whereKey($event->id)->update(['updated_at' => $updatedAt]);
        }

        return $event;
    }

    public function test_a_previously_failed_event_is_processed_on_redelivery(): void
    {
        $tenant  = $this->tenant();
        $payload = $this->charge($tenant, 2001);
        $event   = $this->seedEvent($payload, 'failed');

        $this->webhook($payload)->assertOk()->assertJson(['status' => 'ok']);

        $event->refresh();
        $this->assertSame('processed', $event->status);
        $this->assertSame(2, $event->attempts);
        $this->assertNull($event->error_message);
        $this->assertSame(1, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
    }

    public function test_an_event_currently_being_processed_is_not_run_twice(): void
    {
        $tenant  = $this->tenant();
        $payload = $this->charge($tenant, 2002);
        $this->seedEvent($payload, 'processing');

        $this->webhook($payload)->assertJson(['status' => 'already_processing']);

        $this->assertSame(0, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
    }

    public function test_an_event_stuck_in_processing_is_retried(): void
    {
        $tenant  = $this->tenant();
        $payload = $this->charge($tenant, 2003);
        $this->seedEvent($payload, 'processing', 1, now()->subMinutes(30));

        $this->webhook($payload)->assertJson(['status' => 'ok']);

        $this->assertSame(1, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
    }

    public function test_retries_stop_after_the_attempt_budget(): void
    {
        $tenant  = $this->tenant();
        $payload = $this->charge($tenant, 2004);
        $this->seedEvent($payload, 'failed', 5);

        $this->webhook($payload)->assertOk()->assertJson(['status' => 'gave_up']);

        $this->assertSame(0, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
    }

    public function test_a_failing_handler_returns_500_so_paystack_retries_then_200_once_exhausted(): void
    {
        $tenant  = $this->tenant();
        // Unparseable keep_expiry makes the handler throw.
        $payload = $this->charge($tenant, 2005, ['metadata' => [
            'is_upgrade' => true, 'keep_expiry' => 'not-a-date', 'plan_id' => $this->paid->id, 'tenant_id' => $tenant->id,
        ]]);

        $this->webhook($payload)->assertStatus(500);
        $this->assertSame('failed', WebhookEvent::where('event_id', '2005')->first()->status);

        WebhookEvent::where('event_id', '2005')->update(['attempts' => 4]);
        $this->webhook($payload)->assertOk(); // 5th attempt fails → 200 so Paystack stops
        $this->assertSame('failed', WebhookEvent::where('event_id', '2005')->first()->status);
    }

    // ── 3. Referral credit is restored when checkout doesn't complete ──────────

    public function test_credit_is_restored_when_paystack_initialisation_fails(): void
    {
        $tenant = $this->tenant(['plan_id' => $this->free->id, 'referral_credit_ngn' => 1000, 'subscription_expires_at' => null]);
        $admin  = $this->admin($tenant);
        Http::fake(['*/transaction/initialize' => Http::response(['status' => false, 'message' => 'Gateway down'])]);

        $this->actingAs($admin)->get(route('billing.checkout', $this->paid))->assertSessionHas('error');

        $this->assertEquals(1000.0, (float) $tenant->refresh()->referral_credit_ngn);
        $this->assertNotNull(ReferralCreditLedger::where('tenant_id', $tenant->id)->first()->reversed_at);
    }

    public function test_credit_is_restored_when_paystack_initialisation_throws(): void
    {
        $tenant = $this->tenant(['plan_id' => $this->free->id, 'referral_credit_ngn' => 1000, 'subscription_expires_at' => null]);
        $admin  = $this->admin($tenant);
        Http::fake(['*/transaction/initialize' => fn () => throw new \RuntimeException('network down')]);

        try {
            $this->withoutExceptionHandling()->actingAs($admin)->get(route('billing.checkout', $this->paid));
            $this->fail('Expected exception');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertEquals(1000.0, (float) $tenant->refresh()->referral_credit_ngn);
    }

    public function test_successful_initialisation_keeps_the_credit_reserved_and_records_the_ledger_id(): void
    {
        $tenant = $this->tenant(['plan_id' => $this->free->id, 'referral_credit_ngn' => 1000, 'subscription_expires_at' => null]);
        $admin  = $this->admin($tenant);
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.test/abc'],
        ])]);

        $this->actingAs($admin)->get(route('billing.checkout', $this->paid))->assertRedirect('https://checkout.paystack.test/abc');

        $this->assertEquals(0.0, (float) $tenant->refresh()->referral_credit_ngn);
        $ledger = ReferralCreditLedger::where('tenant_id', $tenant->id)->first();
        $this->assertNull($ledger->reversed_at);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'transaction/initialize')
            && ($r['metadata']['credit_ledger_id'] ?? null) === $ledger->id);
    }

    private function debit(Tenant $tenant, float $amount, $createdAt = null): ReferralCreditLedger
    {
        $entry = ReferralCreditLedger::create([
            'tenant_id' => $tenant->id, 'type' => ReferralCreditLedger::TYPE_DEBIT,
            'amount_ngn' => $amount, 'description' => 'Credit applied to subscription renewal',
        ]);
        if ($createdAt) {
            ReferralCreditLedger::whereKey($entry->id)->update(['created_at' => $createdAt]);
        }

        return $entry;
    }

    public function test_stale_unpaid_debits_are_refunded_once_and_recent_or_linked_ones_are_left_alone(): void
    {
        $tenant = $this->tenant(['referral_credit_ngn' => 0]);
        $stale  = $this->debit($tenant, 700, now()->subHours(30));
        $recent = $this->debit($tenant, 200, now()->subHours(2));
        $paid   = $this->debit($tenant, 300, now()->subHours(30));

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id, 'plan_id' => $this->paid->id, 'amount' => 1, 'currency' => 'NGN',
            'type' => 'subscription', 'status' => 'success', 'paid_at' => now(),
        ]);
        $paid->update(['subscription_payment_id' => $payment->id]);

        $this->artisan('referrals:release-stale-credit')->assertSuccessful();
        $this->assertEquals(700.0, (float) $tenant->refresh()->referral_credit_ngn);
        $this->assertNotNull($stale->refresh()->reversed_at);
        $this->assertNull($recent->refresh()->reversed_at);
        $this->assertNull($paid->refresh()->reversed_at);

        $this->artisan('referrals:release-stale-credit')->assertSuccessful(); // idempotent
        $this->assertEquals(700.0, (float) $tenant->refresh()->referral_credit_ngn);
    }

    public function test_paying_after_credit_was_refunded_re_applies_the_deduction(): void
    {
        $tenant = $this->tenant(['referral_credit_ngn' => 0]);
        $entry  = $this->debit($tenant, 400, now()->subHours(30));
        app(ReferralService::class)->restoreCredit($entry->id);
        $this->assertEquals(400.0, (float) $tenant->refresh()->referral_credit_ngn);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id, 'plan_id' => $this->paid->id, 'amount' => 4600, 'currency' => 'NGN',
            'type' => 'subscription', 'status' => 'success', 'paid_at' => now(),
        ]);
        app(ReferralService::class)->linkPaymentToDebitById($entry->id, $payment);

        $this->assertEquals(0.0, (float) $tenant->refresh()->referral_credit_ngn);
        $entry->refresh();
        $this->assertNull($entry->reversed_at);
        $this->assertSame($payment->id, $entry->subscription_payment_id);
    }

    public function test_a_linked_debit_cannot_be_restored(): void
    {
        $tenant  = $this->tenant(['referral_credit_ngn' => 0]);
        $entry   = $this->debit($tenant, 400);
        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id, 'plan_id' => $this->paid->id, 'amount' => 1, 'currency' => 'NGN',
            'type' => 'subscription', 'status' => 'success', 'paid_at' => now(),
        ]);
        $entry->update(['subscription_payment_id' => $payment->id]);

        $this->assertFalse(app(ReferralService::class)->restoreCredit($entry->id));
        $this->assertEquals(0.0, (float) $tenant->refresh()->referral_credit_ngn);
    }

    // ── 1. Work-order part removal checks ownership ─────────────────────────────

    private function workOrder(Tenant $tenant, User $user, string $status = MaintenanceWorkOrder::STATUS_OPEN): MaintenanceWorkOrder
    {
        $asset = MaintenanceAsset::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id, 'asset_code' => 'AST-' . uniqid(), 'asset_name' => 'Press',
            'status' => MaintenanceAsset::STATUS_ACTIVE, 'created_by' => $user->id,
        ]);

        $wo = app(MaintenanceService::class)->createWorkOrder($tenant, [
            'asset_id' => $asset->id, 'title' => 'Service', 'created_by' => $user->id,
        ]);
        $wo->forceFill(['status' => $status])->save();

        return $wo;
    }

    private function part(Tenant $tenant, User $user, MaintenanceWorkOrder $wo): MaintenanceWorkOrderPart
    {
        $item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id, 'name' => 'Oil', 'sku' => 'SKU-' . uniqid(), 'item_type' => 'product',
            'unit' => 'litre', 'current_stock' => 10, 'cost_price' => 100, 'avg_cost' => 100,
            'reorder_point' => 1, 'created_by' => $user->id,
        ]);

        return MaintenanceWorkOrderPart::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id, 'work_order_id' => $wo->id, 'inventory_item_id' => $item->id,
            'quantity_requested' => 1, 'unit_cost' => 100, 'subtotal' => 100, 'created_by' => $user->id,
        ]);
    }

    private function removeUrl(MaintenanceWorkOrder $wo, MaintenanceWorkOrderPart $part): string
    {
        return route('maintenance.work-orders.parts.remove', [$wo->id, $part->id]);
    }

    public function test_part_can_be_removed_from_its_own_open_work_order(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->admin($tenant);
        $wo     = $this->workOrder($tenant, $admin);
        $part   = $this->part($tenant, $admin, $wo);

        $this->actingAs($admin)->delete($this->removeUrl($wo, $part))->assertSessionHas('success');

        $this->assertNull(MaintenanceWorkOrderPart::withoutGlobalScope('tenant')->find($part->id));
    }

    public function test_part_of_another_work_order_in_the_same_tenant_cannot_be_removed(): void
    {
        $tenant = $this->tenant();
        $admin  = $this->admin($tenant);
        $open   = $this->workOrder($tenant, $admin);
        $closed = $this->workOrder($tenant, $admin, MaintenanceWorkOrder::STATUS_CLOSED);
        $part   = $this->part($tenant, $admin, $closed);

        // Pair the CLOSED order's part with an OPEN order's URL to dodge the closed check.
        $this->actingAs($admin)->delete($this->removeUrl($open, $part))->assertNotFound();

        $this->assertNotNull(MaintenanceWorkOrderPart::withoutGlobalScope('tenant')->find($part->id));
    }

    public function test_part_belonging_to_another_tenant_cannot_be_removed(): void
    {
        $tenantA = $this->tenant();
        $adminA  = $this->admin($tenantA);
        $woA     = $this->workOrder($tenantA, $adminA);

        $tenantB = $this->tenant();
        $adminB  = $this->admin($tenantB);
        $woB     = $this->workOrder($tenantB, $adminB);
        $partB   = $this->part($tenantB, $adminB, $woB);

        $this->actingAs($adminA)->delete($this->removeUrl($woA, $partB))->assertNotFound();

        $this->assertNotNull(MaintenanceWorkOrderPart::withoutGlobalScope('tenant')->find($partB->id));
    }
}
