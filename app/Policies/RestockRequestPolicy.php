<?php

namespace App\Policies;

use App\Models\RestockRequest;
use App\Models\User;

class RestockRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('inventory');
    }

    public function view(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id == $request->tenant_id && $user->canAccess('inventory');
    }

    public function create(User $user): bool
    {
        return $user->canAccess('inventory');
    }

    public function approve(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id == $request->tenant_id
            && $user->isAccountant()
            && $request->canBeApproved();
    }

    public function reject(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id == $request->tenant_id
            && $user->isAccountant()
            && $request->canBeApproved();
    }

    public function receive(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id == $request->tenant_id
            && $user->isAccountant()
            && $request->canBeReceived();
    }

    public function pay(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id == $request->tenant_id
            && $user->isAccountant()
            && $request->canBePaid();
    }

    public function cancel(User $user, RestockRequest $request): bool
    {
        if ($user->tenant_id != $request->tenant_id) {
            return false;
        }

        if ($user->isAccountant()) {
            return $request->canBeCancelled();
        }

        // Staff with inventory access can cancel their own pending requests
        return $user->canAccess('inventory')
            && $request->status === RestockRequest::STATUS_PENDING
            && $request->requested_by === $user->id;
    }
}
