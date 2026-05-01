<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService  $reportService,
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === User::ROLE_STAFF) {
            return redirect()->route('staff.dashboard');
        }

        $tenant    = $user->tenant;
        $dashboard = $this->reportService->getComplianceDashboard($tenant, (int) $request->input('year', now()->year));

        return view('dashboard.index', compact('dashboard'));
    }
}
