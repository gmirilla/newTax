<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $this->requireAdmin($request);

        $tenant = $request->user()->tenant;

        return view('settings.company', compact('tenant'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->requireAdmin($request);

        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'name'                => 'required|string|max:200',
            'email'               => 'nullable|email|max:200',
            'phone'               => 'nullable|string|max:30',
            'address'             => 'nullable|string|max:300',
            'city'                => 'nullable|string|max:100',
            'state'               => 'nullable|string|max:100',
            'tin'                 => 'nullable|string|max:20',
            'rc_number'           => 'nullable|string|max:20',
            'business_type'       => 'nullable|string|max:100',
            'annual_turnover'     => 'nullable|numeric|min:0',
            'vat_registered'      => 'boolean',
            'vat_number'          => 'nullable|string|max:30',
            'is_professional_firm'=> 'boolean',
        ]);

        $tenant->update($validated);

        // Recompute tax category from updated turnover / professional status
        $tenant->updateTaxCategory();

        return back()->with('success', 'Company details updated.');
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only tenant admins can manage company settings.');
    }
}
