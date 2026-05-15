<?php

namespace App\Policies;

use App\Models\InventoryCategory;
use App\Models\User;

class InventoryCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('inventory');
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, InventoryCategory $category): bool
    {
        return $user->tenant_id === $category->tenant_id && $user->isAccountant();
    }

    public function delete(User $user, InventoryCategory $category): bool
    {
        return $user->tenant_id === $category->tenant_id && $user->isAdmin();
    }
}
