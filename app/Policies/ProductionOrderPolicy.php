<?php

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Models\User;

class ProductionOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('manufacturing');
    }

    public function view(User $user, ProductionOrder $order): bool
    {
        return $user->tenant_id == $order->tenant_id && $user->canAccess('manufacturing');
    }

    public function create(User $user): bool
    {
        return $user->isAccountant() || $user->canAccess('manufacturing');
    }

    public function start(User $user, ProductionOrder $order): bool
    {
        return $user->tenant_id == $order->tenant_id
            && $user->canAccess('manufacturing')
            && $order->status === ProductionOrder::STATUS_DRAFT;
    }

    public function complete(User $user, ProductionOrder $order): bool
    {
        return $user->tenant_id == $order->tenant_id
            && $user->canAccess('manufacturing')
            && $order->status === ProductionOrder::STATUS_IN_PRODUCTION;
    }

    public function cancel(User $user, ProductionOrder $order): bool
    {
        return $user->tenant_id == $order->tenant_id
            && $user->canAccess('manufacturing')
            && in_array($order->status, [
                ProductionOrder::STATUS_DRAFT,
                ProductionOrder::STATUS_IN_PRODUCTION,
            ]);
    }
}
