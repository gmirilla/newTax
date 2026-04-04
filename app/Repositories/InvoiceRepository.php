<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository
{
    public function paginate(Tenant $tenant, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::where('tenant_id', $tenant->id)
            ->with(['customer', 'creator'])
            ->orderBy('invoice_date', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        return $query->paginate($perPage);
    }

    public function findByNumber(Tenant $tenant, string $invoiceNumber): ?Invoice
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->where('invoice_number', $invoiceNumber)
            ->with(['customer', 'items', 'payments'])
            ->first();
    }

    public function getOverdue(Tenant $tenant): Collection
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->with('customer')
            ->orderBy('due_date')
            ->get();
    }
}
