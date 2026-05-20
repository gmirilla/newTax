<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EnterpriseAgreement;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnterpriseAgreementController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $agreements = EnterpriseAgreement::where('tenant_id', $tenant->id)
            ->with('plan', 'createdBy')
            ->orderByDesc('created_at')
            ->get();

        return view('superadmin.enterprise.agreements.index', compact('tenant', 'agreements'));
    }

    public function create(Tenant $tenant): View
    {
        $plans = Plan::where('is_active', true)->where('is_enterprise', true)->orderBy('name')->get();
        return view('superadmin.enterprise.agreements.create', compact('tenant', 'plans'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id'             => 'required|exists:plans,id',
            'negotiated_price'    => 'required|numeric|min:0',
            'billing_cycle'       => 'required|in:monthly,quarterly,annually',
            'payment_terms_days'  => 'required|integer|min:7|max:90',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after:start_date',
            'notes'               => 'nullable|string|max:2000',
        ]);

        // Terminate any existing active agreement before creating a new one
        EnterpriseAgreement::where('tenant_id', $tenant->id)
            ->where('status', EnterpriseAgreement::STATUS_ACTIVE)
            ->update(['status' => EnterpriseAgreement::STATUS_TERMINATED]);

        $agreement = EnterpriseAgreement::create([
            ...$validated,
            'tenant_id'  => $tenant->id,
            'status'     => EnterpriseAgreement::STATUS_ACTIVE,
            'created_by' => auth()->id(),
        ]);

        // Move tenant to the enterprise plan
        $plan = Plan::find($validated['plan_id']);
        $tenant->assignPlan($plan, 'active', \Carbon\Carbon::parse($validated['end_date'] ?? now()->addYear()));

        AuditLog::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.enterprise_agreement_created',
            'auditable_type' => EnterpriseAgreement::class,
            'auditable_id'   => $agreement->id,
            'new_values'     => ['negotiated_price' => $agreement->negotiated_price, 'plan_id' => $agreement->plan_id],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,enterprise',
        ]);

        return redirect()->route('superadmin.enterprises.agreements.index', $tenant)
            ->with('success', "Enterprise agreement created for {$tenant->name}.");
    }

    public function edit(Tenant $tenant, EnterpriseAgreement $agreement): View
    {
        abort_unless($agreement->tenant_id == $tenant->id, 404);
        $plans = Plan::where('is_active', true)->where('is_enterprise', true)->orderBy('name')->get();
        return view('superadmin.enterprise.agreements.edit', compact('tenant', 'agreement', 'plans'));
    }

    public function update(Request $request, Tenant $tenant, EnterpriseAgreement $agreement): RedirectResponse
    {
        abort_unless($agreement->tenant_id == $tenant->id, 404);

        $validated = $request->validate([
            'negotiated_price'   => 'required|numeric|min:0',
            'billing_cycle'      => 'required|in:monthly,quarterly,annually',
            'payment_terms_days' => 'required|integer|min:7|max:90',
            'start_date'         => 'required|date',
            'end_date'           => 'nullable|date|after:start_date',
            'status'             => 'required|in:active,expired,terminated',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $agreement->update($validated);

        AuditLog::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.enterprise_agreement_updated',
            'auditable_type' => EnterpriseAgreement::class,
            'auditable_id'   => $agreement->id,
            'new_values'     => $validated,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,enterprise',
        ]);

        return redirect()->route('superadmin.enterprises.agreements.index', $tenant)
            ->with('success', 'Agreement updated.');
    }
}
