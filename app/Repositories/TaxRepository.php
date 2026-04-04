<?php

namespace App\Repositories;

use App\Models\CitRecord;
use App\Models\Tenant;
use App\Models\VatReturn;
use App\Models\WhtRecord;
use Illuminate\Database\Eloquent\Collection;

class TaxRepository
{
    public function getVatReturnHistory(Tenant $tenant, int $year): Collection
    {
        return VatReturn::where('tenant_id', $tenant->id)
            ->where('tax_year', $year)
            ->orderBy('tax_month')
            ->get();
    }

    public function getWhtSchedule(Tenant $tenant, int $year, int $month): Collection
    {
        return WhtRecord::where('tenant_id', $tenant->id)
            ->where('tax_year', $year)
            ->where('tax_month', $month)
            ->with('vendor')
            ->orderBy('deduction_date')
            ->get();
    }

    public function getCitHistory(Tenant $tenant): Collection
    {
        return CitRecord::where('tenant_id', $tenant->id)
            ->orderBy('tax_year', 'desc')
            ->get();
    }

    public function getPendingObligations(Tenant $tenant): array
    {
        $overdueVat = VatReturn::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        $pendingWht = (float)WhtRecord::where('tenant_id', $tenant->id)
            ->where('filing_status', 'pending')
            ->sum('wht_amount');

        $unfiledCit = CitRecord::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        return [
            'overdue_vat_returns' => $overdueVat,
            'pending_wht_amount'  => $pendingWht,
            'unfiled_cit'         => $unfiledCit,
        ];
    }
}
