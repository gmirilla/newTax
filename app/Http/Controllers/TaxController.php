<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CitRecord;
use App\Models\VatReturn;
use App\Repositories\TaxRepository;
use App\Services\BookkeepingService;
use App\Services\CitService;
use App\Services\ReportService;
use App\Services\VatService;
use App\Services\WhtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function __construct(
        private readonly VatService          $vatService,
        private readonly WhtService          $whtService,
        private readonly CitService          $citService,
        private readonly ReportService       $reportService,
        private readonly TaxRepository       $taxRepository,
        private readonly BookkeepingService  $bookkeepingService
    ) {}

    public function dashboard(Request $request): View
    {
        $tenant     = $request->user()->tenant;
        $compliance = $this->reportService->getComplianceDashboard($tenant, (int) $request->input('year', now()->year));
        $pending    = $this->taxRepository->getPendingObligations($tenant);

        return view('tax.dashboard', compact('compliance', 'pending'));
    }

    // --- VAT ---

    public function vatIndex(Request $request): View
    {
        $tenant  = $request->user()->tenant;
        $year    = $request->input('year', now()->year);
        $returns = $this->taxRepository->getVatReturnHistory($tenant, $year);

        return view('tax.vat.index', compact('returns', 'year'));
    }

    public function vatCompute(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $year   = $request->input('year', now()->year);
        $month  = $request->input('month', now()->month);

        $vatReturn = $this->vatService->createOrUpdateReturn($tenant, $year, $month);
        $report    = $this->reportService->getVatReport($tenant, $year, $month);

        return view('tax.vat.compute', compact('vatReturn', 'report', 'year', 'month'));
    }

    public function vatFiled(Request $request, VatReturn $vatReturn)
    {
        $request->validate([
            'filing_reference' => 'required|string|max:100',
            'filed_date'       => 'required|date',
        ]);
        $vatReturn->update([
            'status'            => 'filed',
            'filed_date'        => $request->filed_date,
            'filing_reference'  => $request->filing_reference,
            'filed_by'          => auth()->id(),
        ]);

        return back()->with('success', 'VAT return marked as filed.');
    }

    /**
     * Mark a filed VAT return as paid and settle the liability:
     *   DR  VAT Payable [2100]     amount_paid
     *   CR  Bank / Cash account    amount_paid
     */
    public function vatPaid(Request $request, VatReturn $vatReturn): RedirectResponse
    {
        if ($vatReturn->status !== 'filed') {
            return back()->with('error', 'Only a filed return can be marked as paid.');
        }

        $request->validate([
            'paid_date'       => 'required|date',
            'amount_paid'     => 'required|numeric|min:0.01',
            'payment_account' => 'required|exists:accounts,id',
        ]);

        $tenant = auth()->user()->tenant;

        DB::transaction(function () use ($vatReturn, $request, $tenant) {
            $vatPayableAccount = Account::where('tenant_id', $tenant->id)
                ->where('code', '2100')->firstOrFail();

            $period = "{$vatReturn->getMonthName()} {$vatReturn->tax_year}";

            $transaction = $this->bookkeepingService->postJournalEntry($tenant, [
                'transaction_date' => $request->paid_date,
                'type'             => 'payment',
                'description'      => "VAT remittance: {$period}",
                'reference'        => 'VAT-' . $vatReturn->tax_year . '-' . str_pad((string) $vatReturn->tax_month, 2, '0', STR_PAD_LEFT),
            ], [
                [
                    'account_id'  => $vatPayableAccount->id,
                    'entry_type'  => 'debit',
                    'amount'      => (float) $request->amount_paid,
                    'description' => "Settle VAT payable: {$period}",
                ],
                [
                    'account_id'  => $request->payment_account,
                    'entry_type'  => 'credit',
                    'amount'      => (float) $request->amount_paid,
                    'description' => "VAT remittance: {$period}",
                ],
            ]);

            $vatReturn->update([
                'status'         => 'paid',
                'paid_date'      => $request->paid_date,
                'amount_paid'    => $request->amount_paid,
                'transaction_id' => $transaction->id,
            ]);
        });

        return back()->with('success', 'VAT payment recorded and posted to the ledger.');
    }

    public function vatAmend(Request $request, VatReturn $vatReturn): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($vatReturn->status === 'paid') {
            return back()->with('error', 'A paid VAT return cannot be amended here — contact support for a manual correction.');
        }
        if ($vatReturn->status !== 'filed') {
            return back()->with('error', 'Only filed returns can be amended.');
        }

        $this->vatService->amendReturn($vatReturn, $request->reason);

        return back()->with('success', "VAT return for {$vatReturn->getMonthName()} {$vatReturn->tax_year} amended — recompute has run; re-file it with a new NRS reference when ready.");
    }

    // --- WHT ---

    public function whtIndex(Request $request): View
    {
        $tenant  = $request->user()->tenant;
        $year    = $request->input('year', now()->year);
        $month   = $request->input('month', now()->month);
        $records = $this->taxRepository->getWhtSchedule($tenant, $year, $month);
        $summary = $this->whtService->generateMonthlySchedule($tenant, $year, $month);

        return view('tax.wht.index', compact('records', 'summary', 'year', 'month'));
    }

    public function whtRemit(Request $request, \App\Models\WhtRecord $whtRecord): RedirectResponse
    {
        $this->authorize('update', $whtRecord->tenant);

        $whtRecord->update([
            'filing_status'    => 'remitted',
            'remittance_date'  => now()->toDateString(),
        ]);

        return back()->with('success', 'WHT record marked as remitted.');
    }

    // --- CIT ---

    public function citIndex(Request $request): View
    {
        $tenant     = $request->user()->tenant;
        $citRecords = $this->taxRepository->getCitHistory($tenant);

        $nextDeadline   = \Carbon\Carbon::parse($this->citService->getFilingDueDate(now()->year - 1));
        $daysToDeadline = (int) now()->diffInDays($nextDeadline, false);

        $citStatus = [
            'company_size'    => $tenant->tax_category ?? 'small',
            'cit_rate'        => $tenant->getCitRate(),
            'annual_turnover' => (float) $tenant->annual_turnover,
            'next_deadline'   => $nextDeadline->format('d M Y'),
            'days_to_deadline'=> max(0, $daysToDeadline),
        ];

        return view('tax.cit.index', compact('citStatus', 'citRecords'));
    }

    public function citCompute(Request $request): View
    {
        $tenant  = $request->user()->tenant;
        $year    = $request->input('year', now()->year - 1);
        $record  = $this->citService->createOrUpdateRecord($tenant, $year);
        $report  = $this->reportService->getCitReport($tenant, $year);

        return view('tax.cit.compute', compact('record', 'report', 'year'));
    }

    public function citFiled(Request $request, CitRecord $citRecord)
    {
        $request->validate([
            'filing_reference' => 'required|string|max:100',
            'filed_date'       => 'required|date',
        ]);

        $citRecord->update([
            'status'           => 'filed',
            'filed_date'       => $request->filed_date,
            'filing_reference' => $request->filing_reference,
            'filed_by'         => auth()->id(),
        ]);

        return back()->with('success', 'CIT return marked as filed.');
    }
}
