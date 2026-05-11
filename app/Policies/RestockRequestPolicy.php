<?php

namespace App\Policies;

use App\Models\RestockRequest;
use App\Models\User;

class RestockRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id === $request->tenant_id;
    }

    public function create(User $user): bool
    {
        return true; // all roles can request a restock
    }

    public function approve(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id === $request->tenant_id
            && $user->isAccountant()
            && $request->canBeApproved();
    }

    public function reject(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id === $request->tenant_id
            && $user->isAccountant()
            && $request->canBeApproved();
    }

    public function receive(User $user, RestockRequest $request): bool
    {
        return $user->tenant_id === $request->tenant_id
            && $user->isAccountant()
            && $request->canBeReceived();
    }

    public function cancel(User $user, RestockRequest $request): bool
    {
        if ($user->tenant_id !== $request->tenant_id) {
            return false;
        }

        if ($user->isAccountant()) {
            return $request->canBeCancelled();
        }

        // Staff can only cancel their own pending requests
        return $request->status === RestockRequest::STATUS_PENDING
            && $request->requested_by === $user->id;
    }
}
