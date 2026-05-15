<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id == $invoice->tenant_id && $user->canAccess('invoices');
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id == $invoice->tenant_id
            && $user->isAccountant()
            && !in_array($invoice->status, ['void']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id == $invoice->tenant_id
            && $user->isAdmin();
    }
}
