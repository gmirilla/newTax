<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SalesOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id;
    }

    public function create(User $user): bool
    {
        return true; // all roles can create sales orders
    }

    public function update(User $user, SalesOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && $order->status === SalesOrder::STATUS_DRAFT;
    }

    public function confirm(User $user, SalesOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && $order->status === SalesOrder::STATUS_DRAFT;
    }

    public function cancel(User $user, SalesOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        // Admins and accountants can cancel any order; staff can only cancel their own drafts
        if ($user->isAccountant()) {
            return $order->canBeCancelled();
        }

        return $order->status === SalesOrder::STATUS_DRAFT
            && $order->created_by === $user->id;
    }
}
