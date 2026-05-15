<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('bank_accounts');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->isAdmin() && $user->tenant_id === $bankAccount->tenant_id;
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->isAdmin()
            && $user->tenant_id === $bankAccount->tenant_id
            && ! $bankAccount->is_default;
    }
}
