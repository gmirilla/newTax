<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
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

        Plan::create($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$validated['name']}\" created.");
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan);
        $validated['limits'] = $this->buildLimits($request);

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', "Cannot delete \"{$plan->name}\" — {$plan->tenants_count} tenants are on this plan. Deactivate it instead.");
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" deleted.");
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
        return [
            'invoices_per_month' => $request->input('limit_invoices') === '' ? null : (int) $request->input('limit_invoices'),
            'users'              => $request->input('limit_users') === '' ? null : (int) $request->input('limit_users'),
            'payroll_staff'      => $request->input('limit_payroll_staff') === '' ? null : (int) $request->input('limit_payroll_staff'),
            'customers'          => $request->input('limit_customers') === '' ? null : (int) $request->input('limit_customers'),
            'payroll'            => $request->boolean('feature_payroll'),
            'firs'               => $request->boolean('feature_firs'),
            'advanced_reports'   => $request->boolean('feature_advanced_reports'),
            'api_access'         => $request->boolean('feature_api_access'),
        ];
    }
}
