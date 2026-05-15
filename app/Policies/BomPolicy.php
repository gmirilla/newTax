<?php

namespace App\Policies;

use App\Models\Bom;
use App\Models\User;

class BomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('manufacturing');
    }

    public function view(User $user, Bom $bom): bool
    {
        return $user->tenant_id == $bom->tenant_id && $user->canAccess('manufacturing');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Bom $bom): bool
    {
        return $user->tenant_id == $bom->tenant_id && $user->isAdmin();
    }

    public function delete(User $user, Bom $bom): bool
    {
        return $user->tenant_id == $bom->tenant_id
            && $user->isAdmin()
            && $bom->productionOrders()->whereIn('status', ['in_production', 'completed'])->doesntExist();
    }
}
