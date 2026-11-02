<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\NotificationLog;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ReferralRewardedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $tenantAdmin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::forceCreate([
            'name' => 'Super Admin', 'email' => 'superadmin@platform.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_superadmin' => true,
            'is_active' => true, 'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'limits' => [],
            'is_active' => true, 'is_public' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Co', 'slug' => 'test-co', 'email' => 'admin@testco.ng',
            'tax_category' => 'small', 'annual_turnover' => 5_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $this->tenantAdmin = User::forceCreate([
            'tenant_id' => $this->tenant->id, 'name' => 'Tenant Admin', 'email' => 'admin@testco.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_superadmin' => false,
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    public function test_superadmin_can_view_notification_logs_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('superadmin.notification-logs.index'));

        $response->assertOk();
        $response->assertViewIs('superadmin.notification-logs.index');
    }

    public function test_tenant_admin_cannot_view_notification_logs_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)->get(route('superadmin.notification-logs.index'));

        $response->assertForbidden();
    }

    public function test_resend_re_sends_the_notification_and_creates_a_new_log_row(): void
    {
        Mail::fake();

        $otherTenant = Tenant::create([
            'name' => 'Other Co', 'slug' => 'other-co', 'email' => 'other@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);

        $referral = Referral::create([
            'referrer_tenant_id' => $this->tenant->id,
            'referee_tenant_id'  => $otherTenant->id,
            'status'             => 'rewarded',
            'reward_ngn'         => 2000,
        ]);

        $this->tenantAdmin->notify(new ReferralRewardedNotification($referral));
        $this->assertSame(1, NotificationLog::count());

        $log = NotificationLog::first();

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.notification-logs.resend', $log));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(2, NotificationLog::count());
    }

    public function test_resend_fails_gracefully_when_referenced_model_was_deleted(): void
    {
        Mail::fake();

        $otherTenant = Tenant::create([
            'name' => 'Other Co', 'slug' => 'other-co', 'email' => 'other@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);

        $referral = Referral::create([
            'referrer_tenant_id' => $this->tenant->id,
            'referee_tenant_id'  => $otherTenant->id,
            'status'             => 'rewarded',
            'reward_ngn'         => 2000,
        ]);

        $this->tenantAdmin->notify(new ReferralRewardedNotification($referral));
        $log = NotificationLog::first();

        $referral->delete();

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.notification-logs.resend', $log));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(1, NotificationLog::count());
    }
}
