<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFirsCredentialsRequest;
use App\Models\TenantFirsCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirsOnboardingController extends Controller
{
    /**
     * Show the FIRS credential setup form.
     * If credentials already exist, the form is pre-populated (fields stay hidden
     * via $hidden on the model, so raw values are never exposed in the view).
     */
    public function showForm(Request $request): View
    {
        $tenant      = $request->user()->tenant;
        $credential  = TenantFirsCredential::where('tenant_id', $tenant->id)->first();
        $hasActive   = $credential?->is_active ?? false;

        return view('settings.firs', compact('credential', 'hasActive'));
    }

    /**
     * Persist (upsert) FIRS credentials for the authenticated tenant.
     * Credentials are encrypted at rest via Laravel's 'encrypted' cast.
     */
    public function store(StoreFirsCredentialsRequest $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        TenantFirsCredential::updateOrCreate(
            ['tenant_id' => $tenant->id],
            array_merge($request->validated(), [
                'is_active'          => true,
                'credentials_set_at' => now(),
            ])
        );

        return redirect()->route('settings.firs')
            ->with('success', 'FIRS credentials saved and activated.');
    }

    /**
     * Deactivate (not delete) the tenant's FIRS credentials.
     * Useful when migrating from sandbox to production keys.
     */
    public function deactivate(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        TenantFirsCredential::where('tenant_id', $tenant->id)
            ->update(['is_active' => false]);

        return redirect()->route('settings.firs')
            ->with('success', 'FIRS credentials deactivated.');
    }
}
