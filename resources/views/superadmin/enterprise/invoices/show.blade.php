@extends('superadmin.layout')

@section('page-title', $invoice->invoice_number)

@section('content')
<div class="space-y-6 max-w-3xl">

    {{-- Breadcrumb --}}
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
            <span>/</span>
            <a href="{{ route('superadmin.enterprises.invoices.index', $tenant) }}" class="hover:underline">Invoices</a>
            <span>/</span>
            <span class="font-mono">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 font-mono">{{ $invoice->invoice_number }}</h1>
                @php $color = \App\Models\PlatformInvoice::STATUS_COLORS[$invoice->status] ?? 'gray'; @endphp
                <span class="mt-1 inline-block px-2.5 py-0.5 rounded-full text-xs bg-{{ $color }}-100 text-{{ $color }}-800 font-semibold">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.enterprises.invoices.pdf', [$tenant, $invoice]) }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                    Download PDF
                </a>

                @if($invoice->status === 'draft')
                <form method="POST" action="{{ route('superadmin.enterprises.invoices.send', [$tenant, $invoice]) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Mark as Sent
                    </button>
                </form>
                @endif

                @if(in_array($invoice->status, ['sent', 'overdue']))
                <button onclick="document.getElementById('paid-modal').classList.remove('hidden')"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                    Mark as Paid
                </button>
                @endif

                @if($invoice->status !== 'paid' && $invoice->status !== 'void')
                <form method="POST" action="{{ route('superadmin.enterprises.invoices.void', [$tenant, $invoice]) }}"
                      onsubmit="return confirm('Void this invoice? This cannot be undone.')">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50">
                        Void
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- Invoice details --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Billed To</p>
                <p class="font-semibold text-gray-900">{{ $tenant->name }}</p>
                <p class="text-sm text-gray-500">{{ $tenant->email }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Plan</p>
                <p class="font-semibold text-gray-900">{{ $invoice->agreement->plan?->name ?? '—' }}</p>
                <p class="text-sm text-gray-500 capitalize">{{ $invoice->agreement->billing_cycle }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Billing Period</p>
                <p class="font-semibold text-gray-900">
                    {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}
                </p>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-700">Subscription ({{ $invoice->agreement->plan?->name }})</span>
                <span class="font-semibold text-gray-900">₦{{ number_format($invoice->amount, 2) }}</span>
            </div>
            @if($invoice->notes)
            <div class="mt-2 text-sm text-gray-500 bg-gray-50 rounded p-3">
                {!! nl2br(e($invoice->notes)) !!}
            </div>
            @endif
            <div class="flex justify-between items-center py-3 border-t border-gray-200 mt-2">
                <span class="font-bold text-gray-900">Total Due</span>
                <span class="text-xl font-bold text-gray-900">₦{{ number_format($invoice->amount, 2) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 text-sm">
            <div>
                <p class="text-gray-500">Due Date</p>
                <p class="font-medium {{ $invoice->due_date->isPast() && $invoice->status !== 'paid' ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $invoice->due_date->format('d M Y') }}
                </p>
            </div>
            <div>
                <p class="text-gray-500">Payment Terms</p>
                <p class="font-medium text-gray-900">{{ $invoice->agreement->payment_terms_days }} days</p>
            </div>
            @if($invoice->paid_at)
            <div>
                <p class="text-gray-500">Paid On</p>
                <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Payment Method</p>
                <p class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $invoice->payment_method ?? '—') }}</p>
            </div>
            @if($invoice->payment_reference)
            <div class="col-span-2">
                <p class="text-gray-500">Payment Reference</p>
                <p class="font-mono text-sm text-gray-900">{{ $invoice->payment_reference }}</p>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

{{-- Mark Paid Modal --}}
<div id="paid-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
        <h3 class="font-semibold text-gray-900 mb-4">Record Payment</h3>
        <form method="POST" action="{{ route('superadmin.enterprises.invoices.mark-paid', [$tenant, $invoice]) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date *</label>
                    <input type="date" name="paid_at" value="{{ today()->format('Y-m-d') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Reference / Transaction ID</label>
                    <input type="text" name="payment_reference" placeholder="e.g. bank trnx ID"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit"
                        class="flex-1 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                    Confirm Payment
                </button>
                <button type="button"
                        onclick="document.getElementById('paid-modal').classList.add('hidden')"
                        class="flex-1 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
