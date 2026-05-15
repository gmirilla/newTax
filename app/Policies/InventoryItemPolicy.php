<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('inventory');
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id == $item->tenant_id && $user->canAccess('inventory');
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id == $item->tenant_id && $user->isAccountant();
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id == $item->tenant_id && $user->isAdmin();
    }

    public function adjust(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id == $item->tenant_id
            && ($user->isAccountant() || $user->canAccess('inventory'));
    }
}
