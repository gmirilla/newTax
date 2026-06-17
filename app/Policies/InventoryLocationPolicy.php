<?php

namespace App\Policies;

use App\Models\InventoryLocation;
use App\Models\User;

class InventoryLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('inventory');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, InventoryLocation $location): bool
    {
        return $user->tenant_id == $location->tenant_id && $user->isAdmin();
    }

    public function delete(User $user, InventoryLocation $location): bool
    {
        return $user->tenant_id == $location->tenant_id && $user->isAdmin();
    }
}
