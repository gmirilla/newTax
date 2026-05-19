<?php

namespace App\Policies;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;

class MaintenanceWorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('maintenance');
    }

    public function view(User $user, MaintenanceWorkOrder $wo): bool
    {
        return $user->tenant_id == $wo->tenant_id
            && $user->canAccess('maintenance');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    public function update(User $user, MaintenanceWorkOrder $wo): bool
    {
        if ($user->tenant_id != $wo->tenant_id) {
            return false;
        }
        // Technicians (staff with maintenance access) can update WOs assigned to them
        if ($user->isAdmin() || $user->isAccountant()) {
            return true;
        }
        return $wo->assigned_to === $user->id && $user->canAccess('maintenance');
    }

    public function close(User $user, MaintenanceWorkOrder $wo): bool
    {
        return $user->tenant_id == $wo->tenant_id
            && ($user->isAdmin() || $user->isAccountant());
    }
}
