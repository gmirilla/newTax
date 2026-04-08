<?php

namespace App\Http\Controllers;

use App\Exports\BalanceSheetExport;
use App\Exports\LedgerExport;
use App\Exports\ProfitLossExport;
use App\Models\Account;
use App\Services\BookkeepingService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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
        [$tenant, $start, $end, $year, $month, $basis] = $this->plParams($request);

        $report = $this->bookkeepingService->getProfitAndLoss($tenant, $start, $end, $basis);

        return view('reports.profit-loss', compact('report', 'year', 'month', 'basis'));
    }

    public function profitAndLossPdf(Request $request)
    {
        [$tenant, $start, $end, $year, $month, $basis] = $this->plParams($request);

        $report = $this->bookkeepingService->getProfitAndLoss($tenant, $start, $end, $basis);

        $pdf = Pdf::loadView('reports.profit-loss-pdf', compact('report', 'tenant'))
            ->setPaper('a4', 'portrait');

        $label = $month ? date('M_Y', mktime(0,0,0,$month,1,$year)) : $year;
        return $pdf->download("PL_{$label}_{$basis}.pdf");
    }

    public function profitAndLossExcel(Request $request)
    {
        [$tenant, $start, $end, $year, $month, $basis] = $this->plParams($request);

        $report = $this->bookkeepingService->getProfitAndLoss($tenant, $start, $end, $basis);

        $label    = $month ? date('M_Y', mktime(0,0,0,$month,1,$year)) : $year;
        $filename = "PL_{$label}_{$basis}.xlsx";

        return Excel::download(new ProfitLossExport($report, $tenant), $filename);
    }

    /** Shared parameter extraction for P&L routes. */
    private function plParams(Request $request): array
    {
        $tenant = $request->user()->tenant;
        $year   = (int) $request->input('year', now()->year);
        $month  = $request->input('month') ? (int) $request->input('month') : null;
        $basis  = $request->input('basis', 'accrual') === 'cash' ? 'cash' : 'accrual';

        if ($month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end   = Carbon::create($year, $month, 1)->endOfMonth();
        } else {
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end   = Carbon::create($year, 12, 31)->endOfYear();
        }

        return [$tenant, $start, $end, $year, $month, $basis];
    }

    public function balanceSheet(Request $request): View
    {
        [$tenant, $asOf] = $this->bsParams($request);
        $report = $this->bookkeepingService->getBalanceSheet($tenant, $asOf);
        return view('reports.balance-sheet', compact('report'));
    }

    public function balanceSheetPdf(Request $request)
    {
        [$tenant, $asOf] = $this->bsParams($request);
        $report = $this->bookkeepingService->getBalanceSheet($tenant, $asOf);

        $pdf = Pdf::loadView('reports.balance-sheet-pdf', compact('report', 'tenant'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('BalanceSheet_' . $asOf->format('Y-m-d') . '.pdf');
    }

    public function balanceSheetExcel(Request $request)
    {
        [$tenant, $asOf] = $this->bsParams($request);
        $report = $this->bookkeepingService->getBalanceSheet($tenant, $asOf);

        return Excel::download(
            new BalanceSheetExport($report, $tenant),
            'BalanceSheet_' . $asOf->format('Y-m-d') . '.xlsx'
        );
    }

    private function bsParams(Request $request): array
    {
        $tenant = $request->user()->tenant;
        $asOf   = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))
            : now();
        return [$tenant, $asOf];
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

    public function ledger(Request $request): View
    {
        [$tenant, $start, $end, $accountCode, $accountType, $search] = $this->ledgerParams($request);

        $report   = $this->bookkeepingService->getLedger($tenant, $start, $end, $accountCode, $accountType, $search);
        $accounts = Account::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'type']);

        return view('reports.ledger', compact('report', 'accounts'));
    }

    public function ledgerPdf(Request $request)
    {
        [$tenant, $start, $end, $accountCode, $accountType, $search] = $this->ledgerParams($request);

        $report = $this->bookkeepingService->getLedger($tenant, $start, $end, $accountCode, $accountType, $search);

        $pdf = Pdf::loadView('reports.ledger-pdf', compact('report', 'tenant'))
            ->setPaper('a4', 'landscape');

        $filename = 'Ledger_' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function ledgerExcel(Request $request)
    {
        [$tenant, $start, $end, $accountCode, $accountType, $search] = $this->ledgerParams($request);

        $report   = $this->bookkeepingService->getLedger($tenant, $start, $end, $accountCode, $accountType, $search);
        $filename = 'Ledger_' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.xlsx';

        return Excel::download(new LedgerExport($report, $tenant), $filename);
    }

    private function ledgerParams(Request $request): array
    {
        $tenant      = $request->user()->tenant;
        $start       = Carbon::parse($request->input('date_from', now()->startOfYear()->toDateString()))->startOfDay();
        $end         = Carbon::parse($request->input('date_to',   now()->toDateString()))->endOfDay();
        $accountCode = $request->input('account_code') ?: null;
        $accountType = in_array($request->input('account_type'), ['asset','liability','equity','revenue','expense'])
            ? $request->input('account_type') : null;
        $search      = $request->input('search') ?: null;

        return [$tenant, $start, $end, $accountCode, $accountType, $search];
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
