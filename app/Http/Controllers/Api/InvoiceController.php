<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService    $invoiceService,
        private readonly InvoiceRepository $invoiceRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant   = $request->user()->tenant;
        $invoices = $this->invoiceRepository->paginate($tenant, $request->only([
            'status', 'customer_id', 'date_from', 'date_to', 'search',
        ]), 20);

        return response()->json($invoices);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $tenant  = $request->user()->tenant;
        $invoice = $this->invoiceService->create(
            $tenant,
            $request->validated(),
            $request->input('items', [])
        );

        return response()->json($invoice->load(['customer', 'items']), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json(
            $invoice->load(['customer', 'items', 'payments', 'creator'])
        );
    }

    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'payment_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'method'       => 'required|in:cash,bank_transfer,cheque,pos,online',
        ]);

        $payment = $this->invoiceService->recordPayment($invoice, $request->all());

        return response()->json($payment, 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $tenant  = $request->user()->tenant;
        $summary = $this->invoiceService->getDashboardSummary($tenant);

        return response()->json($summary);
    }
}
