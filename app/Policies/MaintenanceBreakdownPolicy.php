<?php

namespace App\Policies;

use App\Models\MaintenanceBreakdown;
use App\Models\User;

class MaintenanceBreakdownPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('maintenance');
    }

    public function view(User $user, MaintenanceBreakdown $breakdown): bool
    {
        return $user->tenant_id == $breakdown->tenant_id
            && $user->canAccess('maintenance');
    }

    public function create(User $user): bool
    {
        // Any user with maintenance access can report a breakdown
        return $user->canAccess('maintenance');
    }

    public function update(User $user, MaintenanceBreakdown $breakdown): bool
    {
        return $user->tenant_id == $breakdown->tenant_id
            && ($user->isAdmin() || $user->isAccountant());
    }
}
