<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_SLUGS = [
        'getting-started', 'invoicing', 'quotations', 'customers', 'bookkeeping', 'bank-accounts',
        'reports', 'inventory', 'payroll', 'team', 'billing',
        'accounting-firms', 'expenses', 'tax-compliance', 'maintenance', 'manufacturing',
        'storefront', 'chart-of-accounts', 'e-invoicing', 'referrals', 'activity-log',
    ];

    private const ADMIN_ONLY = ['chart-of-accounts', 'e-invoicing', 'referrals'];
    private const ADMIN_ACCOUNTANT = ['accounting-firms', 'expenses', 'tax-compliance', 'activity-log'];

    private function userWithRole(string $role, array $limits = []): User
    {
        static $n = 0;
        $n++;

        $plan = Plan::create([
            'name' => "Plan $n", 'slug' => "plan-$n", 'price_monthly' => 0,
            'limits' => $limits, 'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => "Co $n", 'slug' => "co-$n", 'email' => "co$n@test.ng",
            'tax_category' => 'small', 'annual_turnover' => 0, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => ucfirst($role), 'email' => "$role$n@test.ng",
            'password' => bcrypt('password'), 'role' => $role, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_every_topic_renders_for_an_admin(): void
    {
        $admin = $this->userWithRole('admin');

        foreach (self::ALL_SLUGS as $slug) {
            $this->actingAs($admin)->get(route('help.show', $slug))
                ->assertOk()
                ->assertDontSee('NaijaBooks');
        }
    }

    public function test_unknown_topic_is_404(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/help/not-a-real-topic')
            ->assertNotFound();
    }

    public function test_admin_only_topics_are_hidden_from_accountants_and_staff(): void
    {
        foreach (['accountant', 'staff'] as $role) {
            $user = $this->userWithRole($role);

            foreach (self::ADMIN_ONLY as $slug) {
                $this->actingAs($user)->get(route('help.show', $slug))->assertNotFound();
            }
        }
    }

    public function test_firm_and_finance_topics_are_for_admins_and_accountants_only(): void
    {
        $accountant = $this->userWithRole('accountant');
        $staff = $this->userWithRole('staff');

        foreach (self::ADMIN_ACCOUNTANT as $slug) {
            $this->actingAs($accountant)->get(route('help.show', $slug))->assertOk();
            $this->actingAs($staff)->get(route('help.show', $slug))->assertNotFound();
        }
    }

    public function test_index_lists_only_topics_the_role_can_open(): void
    {
        $staff = $this->userWithRole('staff');
        $this->actingAs($staff)->get(route('help.index'))
            ->assertOk()
            ->assertSee('Getting Started')
            ->assertDontSee('Chart of Accounts')
            ->assertDontSee('Accounting Firms');

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get(route('help.index'))
            ->assertOk()
            ->assertSee('Chart of Accounts')
            ->assertSee('Accounting Firms');
    }

    public function test_plan_gated_topics_are_marked_upgrade_when_plan_lacks_the_feature(): void
    {
        $denied = $this->userWithRole('admin', ['storefront' => false]);
        $this->actingAs($denied)->get(route('help.index'))->assertSee('Upgrade');

        $allowed = $this->userWithRole('admin', [
            'storefront' => true, 'maintenance' => true, 'manufacturing' => true,
            'inventory' => true, 'payroll' => true, 'firs' => true,
        ]);
        $this->actingAs($allowed)->get(route('help.index'))->assertDontSee('Upgrade');
    }

    public function test_payroll_help_shows_the_current_paye_bands(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('help.show', 'payroll'))
            ->assertSee('First ₦800,000')
            ->assertDontSee('First ₦300,000')
            ->assertDontSee('Consolidated Relief Allowance (CRA) of the higher');
    }
}
