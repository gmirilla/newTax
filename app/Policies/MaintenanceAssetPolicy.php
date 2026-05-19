<?php

namespace App\Policies;

use App\Models\MaintenanceAsset;
use App\Models\User;

class MaintenanceAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('maintenance');
    }

    public function view(User $user, MaintenanceAsset $asset): bool
    {
        return $user->tenant_id == $asset->tenant_id
            && $user->canAccess('maintenance');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    public function update(User $user, MaintenanceAsset $asset): bool
    {
        return $user->tenant_id == $asset->tenant_id
            && ($user->isAdmin() || $user->isAccountant());
    }

    public function delete(User $user, MaintenanceAsset $asset): bool
    {
        return $user->tenant_id == $asset->tenant_id
            && $user->isAdmin();
    }
}
