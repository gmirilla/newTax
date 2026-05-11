<?php

namespace App\Policies;

use App\Models\InventoryUnit;
use App\Models\User;

class InventoryUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccountant();
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, InventoryUnit $unit): bool
    {
        return $user->tenant_id === $unit->tenant_id && $user->isAccountant();
    }

    public function delete(User $user, InventoryUnit $unit): bool
    {
        return $user->tenant_id === $unit->tenant_id && $user->isAdmin();
    }
}
