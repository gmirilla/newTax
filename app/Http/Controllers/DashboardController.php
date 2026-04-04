<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService  $reportService,
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $dashboard = $this->reportService->getComplianceDashboard($tenant, (int) $request->input('year', now()->year));

        return view('dashboard.index', compact('dashboard'));
    }
}
