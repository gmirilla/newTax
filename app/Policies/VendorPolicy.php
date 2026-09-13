<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->tenant_id == $vendor->tenant_id && $user->isAccountant();
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->tenant_id == $vendor->tenant_id && $user->isAdmin();
    }
}
