<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('chart_of_accounts');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isAdmin() && $user->tenant_id == $account->tenant_id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->isAdmin()
            && $user->tenant_id == $account->tenant_id
            && ! $account->is_system;
    }
}
