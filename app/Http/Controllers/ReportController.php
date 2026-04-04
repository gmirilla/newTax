<?php

namespace App\Http\Controllers;

use App\Services\BookkeepingService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService      $reportService,
        private readonly BookkeepingService $bookkeepingService
    ) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function profitAndLoss(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $year   = $request->input('year', now()->year);
        $month  = $request->input('month'); // null = full year

        if ($month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end   = Carbon::create($year, $month, 1)->endOfMonth();
        } else {
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end   = Carbon::create($year, 12, 31)->endOfYear();
        }

        $report = $this->bookkeepingService->getProfitAndLoss($tenant, $start, $end);

        return view('reports.profit-loss', compact('report', 'year', 'month'));
    }

    public function balanceSheet(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $asOf   = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))
            : now();

        $report = $this->bookkeepingService->getBalanceSheet($tenant, $asOf);

        return view('reports.balance-sheet', compact('report'));
    }

    public function trialBalance(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $asOf   = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))
            : now();

        $report = $this->bookkeepingService->getTrialBalance($tenant, $asOf);

        return view('reports.trial-balance', compact('report'));
    }

    public function vatReport(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $year   = $request->input('year', now()->year);
        $month  = $request->input('month', now()->month);

        $report = $this->reportService->getVatReport($tenant, $year, $month);

        return view('reports.vat', compact('report', 'year', 'month'));
    }

    public function citReport(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $year   = $request->input('year', now()->year - 1);

        $report = $this->reportService->getCitReport($tenant, $year);

        return view('reports.cit', compact('report', 'year'));
    }

    public function taxSummary(Request $request): View
    {
        $tenant  = $request->user()->tenant;
        $year    = (int) $request->input('year', now()->year);
        $summary = $this->reportService->getComplianceDashboard($tenant, $year);

        return view('reports.tax-summary', compact('summary', 'year'));
    }
}
