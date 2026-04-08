<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    public function filtered(Tenant $tenant, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->buildQuery($tenant, $filters)
            ->with(['creator', 'journalEntries.account'])
            ->get();
    }

    public function paginate(Tenant $tenant, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildQuery($tenant, $filters)
            ->with(['creator', 'journalEntries.account'])
            ->paginate($perPage);
    }

    private function buildQuery(Tenant $tenant, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Transaction::where('tenant_id', $tenant->id)
            ->orderBy('transaction_date', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reference', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }
}
