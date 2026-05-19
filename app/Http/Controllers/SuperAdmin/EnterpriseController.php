<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseAgreement;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use Illuminate\View\View;

class EnterpriseController extends Controller
{
    public function index(): View
    {
        // All tenants on enterprise plans
        $enterpriseTenants = Tenant::whereHas('plan', fn($q) => $q->where('is_enterprise', true))
            ->with(['plan', 'enterpriseAgreements' => fn($q) => $q->where('status', EnterpriseAgreement::STATUS_ACTIVE)->with('plan')])
            ->withCount([
                'platformInvoices',
                'platformInvoices as overdue_invoices_count' => fn($q) => $q->whereIn('status', [PlatformInvoice::STATUS_OVERDUE])
                    ->orWhere(fn($q2) => $q2->where('status', PlatformInvoice::STATUS_SENT)->where('due_date', '<', now()->toDateString())),
            ])
            ->orderBy('name')
            ->get();

        $totalOverdue = $enterpriseTenants->sum('overdue_invoices_count');

        return view('superadmin.enterprise.index', compact('enterpriseTenants', 'totalOverdue'));
    }
}
