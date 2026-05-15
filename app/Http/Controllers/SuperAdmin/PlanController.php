<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount('tenants')
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('superadmin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);
        $validated['limits'] = $this->buildLimits($request);

        $plan = Plan::create($validated);

        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.plan_created',
            'auditable_type' => Plan::class,
            'auditable_id'   => $plan->id,
            'new_values'     => ['name' => $plan->name, 'slug' => $plan->slug, 'limits' => $plan->limits],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,plan',
        ]);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan);
        $validated['limits'] = $this->buildLimits($request);

        $oldLimits = $plan->limits;
        $plan->update($validated);

        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.plan_updated',
            'auditable_type' => Plan::class,
            'auditable_id'   => $plan->id,
            'old_values'     => ['limits' => $oldLimits],
            'new_values'     => ['name' => $plan->name, 'slug' => $plan->slug, 'limits' => $plan->limits],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,plan',
        ]);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', "Cannot delete \"{$plan->name}\" — {$plan->tenants_count} tenants are on this plan. Deactivate it instead.");
        }

        $planName = $plan->name;
        $planId   = $plan->id;

        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.plan_deleted',
            'auditable_type' => Plan::class,
            'auditable_id'   => $planId,
            'old_values'     => ['name' => $planName, 'slug' => $plan->slug],
            'new_values'     => [],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,plan',
        ]);

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$planName}\" deleted.");
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name'           => 'required|string|max:100',
            'slug'           => 'required|string|max:50|alpha_dash|unique:plans,slug' . ($plan ? ",{$plan->id}" : ''),
            'description'    => 'nullable|string|max:500',
            'price_monthly'  => 'required|numeric|min:0',
            'price_yearly'   => 'nullable|numeric|min:0',
            'trial_days'          => 'required|integer|min:0|max:365',
            'paystack_plan_code'  => 'nullable|string|max:50',
            'is_active'           => 'boolean',
            'is_public'      => 'boolean',
            'sort_order'     => 'required|integer|min:0',
        ]);
    }

    private function buildLimits(Request $request): array
    {
        // filled() returns false for both null and '' — blank number inputs arrive as null or ''
        // depending on browser; (int) null = 0 which would wrongly disable the limit
        return [
            'invoices_per_month' => $request->filled('limit_invoices')      ? (int) $request->input('limit_invoices')      : null,
            'users'              => $request->filled('limit_users')          ? (int) $request->input('limit_users')          : null,
            'payroll_staff'      => $request->filled('limit_payroll_staff')  ? (int) $request->input('limit_payroll_staff')  : null,
            'customers'          => $request->filled('limit_customers')      ? (int) $request->input('limit_customers')      : null,
            'payroll'            => $request->boolean('feature_payroll'),
            'firs'               => $request->boolean('feature_firs'),
            'advanced_reports'   => $request->boolean('feature_advanced_reports'),
            'inventory'          => $request->boolean('feature_inventory'),
            'inventory_reports'  => $request->boolean('feature_inventory_reports'),
            'manufacturing'      => $request->boolean('feature_manufacturing'),
            'api_access'         => $request->boolean('feature_api_access'),
        ];
    }
}
