<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAlert;
use Illuminate\Http\RedirectResponse;

class InventoryAlertController extends Controller
{
    public function dismiss(InventoryAlert $alert): RedirectResponse
    {
        // Scope check — ensure the alert belongs to the current user's tenant
        abort_unless($alert->tenant_id === auth()->user()->tenant_id, 403);

        $alert->dismiss();

        return back()->with('success', 'Alert dismissed.');
    }

    public function dismissAll(): RedirectResponse
    {
        InventoryAlert::where('tenant_id', auth()->user()->tenant_id)
            ->withoutGlobalScope('tenant')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return back()->with('success', 'All alerts dismissed.');
    }
}
