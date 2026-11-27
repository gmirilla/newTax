<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppTourStepsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $limits = []): User
    {
        static $n = 0;
        $n++;

        $plan = Plan::create([
            'name' => "Plan $n", 'slug' => "tour-plan-$n", 'price_monthly' => 0,
            'limits' => $limits, 'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => "Tour Co $n", 'slug' => "tour-co-$n", 'email' => "tour$n@test.ng",
            'tax_category' => 'small', 'annual_turnover' => 0, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => ucfirst($role), 'email' => "$role-tour$n@test.ng",
            'password' => bcrypt('password'), 'role' => $role, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_tour_includes_clients_step_and_new_storage_key(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-tour="nav-clients"', false);
        $response->assertSee('Clients (for accounting firms)', false);
        $response->assertSee('nb_tour_app_v2', false);
        $response->assertDontSee('nb_tour_app_v1', false);
    }

    public function test_staff_tour_has_no_firm_or_finance_steps(): void
    {
        $response = $this->actingAs($this->user('staff'))->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-tour="nav-clients"', false);
        $response->assertDontSee('Clients (for accounting firms)', false);
        $response->assertDontSee('Tax & Compliance', false);
    }

    public function test_module_steps_only_appear_when_the_plan_allows_them(): void
    {
        $without = $this->actingAs($this->user('admin', ['storefront' => false, 'maintenance' => false, 'manufacturing' => false]))
            ->get(route('dashboard'));
        $without->assertDontSee("title: 'Online Storefront'", false);
        $without->assertDontSee("title: 'Maintenance'", false);
        $without->assertDontSee("title: 'Manufacturing'", false);

        $with = $this->actingAs($this->user('admin', ['storefront' => true, 'maintenance' => true, 'manufacturing' => true]))
            ->get(route('dashboard'));
        $with->assertSee("title: 'Online Storefront'", false);
        $with->assertSee("title: 'Maintenance'", false);
        $with->assertSee("title: 'Manufacturing'", false);
    }

    public function test_tour_steps_link_to_help_topics_that_exist(): void
    {
        $admin = $this->user('admin', [
            'payroll' => true, 'inventory' => true, 'maintenance' => true, 'storefront' => true, 'manufacturing' => true,
        ]);
        $html = $this->actingAs($admin)->get(route('dashboard'))->getContent();

        preg_match_all("/help\('([a-z-]+)'\)/", $html, $m);
        $this->assertNotEmpty($m[1]);

        foreach (array_unique($m[1]) as $slug) {
            $this->actingAs($admin)->get(route('help.show', $slug))->assertOk();
        }
    }

    public function test_tour_script_picks_visible_targets_and_supports_links(): void
    {
        $js = file_get_contents(public_path('js/tour.js'));

        $this->assertStringContainsString('findTarget', $js);
        $this->assertStringContainsString('step.link', $js);
    }
}
