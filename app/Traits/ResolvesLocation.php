<?php

namespace App\Traits;

use App\Models\InventoryLocation;

trait ResolvesLocation
{
    /**
     * Return the active inventory location for the current session.
     *
     * Reads `inventory_location_id` from the session and validates that it
     * belongs to the authenticated tenant. Falls back to the tenant's default
     * location, creating one if none exists.
     */
    protected function activeLocation(): InventoryLocation
    {
        $tenant     = auth()->user()->tenant;
        $locationId = session('inventory_location_id');

        if ($locationId) {
            $location = InventoryLocation::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('id', $locationId)
                ->where('is_active', true)
                ->first();

            if ($location) {
                return $location;
            }
        }

        return $this->defaultLocation($tenant);
    }

    protected function defaultLocation(\App\Models\Tenant $tenant): InventoryLocation
    {
        $location = InventoryLocation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($location) {
            return $location;
        }

        // Auto-provision the first active location as the default
        $any = InventoryLocation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if ($any) {
            $any->update(['is_default' => true]);
            return $any->fresh();
        }

        // Last resort: create a Main Store (should only happen for new tenants
        // whose backfill migration has not yet run)
        return InventoryLocation::withoutGlobalScope('tenant')->create([
            'tenant_id'  => $tenant->id,
            'name'       => 'Main Store',
            'code'       => 'MAIN',
            'is_default' => true,
            'is_active'  => true,
        ]);
    }
}
