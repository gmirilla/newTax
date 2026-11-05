<?php

namespace App\Policies;

use App\Models\FirmClientLink;
use App\Models\User;

class FirmClientLinkPolicy
{
    public function enter(User $user, FirmClientLink $link): bool
    {
        return $user->tenant_id == $link->firm_tenant_id && $user->isAccountant() && $link->isActive();
    }

    public function approve(User $user, FirmClientLink $link): bool
    {
        return $user->tenant_id == $link->pendingApprovalTenantId() && $user->isAdmin();
    }

    public function revoke(User $user, FirmClientLink $link): bool
    {
        return ($user->tenant_id == $link->firm_tenant_id || $user->tenant_id == $link->client_tenant_id)
            && $user->isAdmin();
    }
}
