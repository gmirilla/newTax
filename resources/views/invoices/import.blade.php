@extends('layouts.app')

@section('page-title', 'Import Invoices')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Invoices</a>
    </div>

    {{-- Upload form --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold">Batch Import Invoices</h2>
            <p class="text-sm text-gray-500 mt-1">
                Upload an Excel (.xlsx) or CSV file to create multiple invoices at once.
                Rows sharing the same <strong>invoice_number</strong> are combined into a single invoice with multiple line items.
            </p>
        </div>

        @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            {{ session('error') }}
        </div>
        @endif

        @if(session('import_errors'))
        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
            <p class="font-semibold mb-1">The following rows had errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('invoices.import.process') }}" enctype="multipart/form-data"
              x-data="{ filename: '', dragging: false }"
              class="space-y-4">
            @csrf

            {{-- Drop zone --}}
            <div
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                :class="dragging ? 'border-green-400 bg-green-50' : 'border-gray-300 hover:border-gray-400'"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; filename = $event.dataTransfer.files[0]?.name">
                <input type="file" name="file" accept=".csv,.xlsx,.xls" x-ref="fileInput" class="hidden"
                       @change="filename = $event.target.files[0]?.name">
                <div @click="$refs.fileInput.click()" class="cursor-pointer">
                    <p class="text-3xl mb-2">📂</p>
                    <p class="text-sm font-medium text-gray-700" x-text="filename || 'Click to browse or drag & drop'"></p>
                    <p class="text-xs text-gray-400 mt-1">Accepts .csv, .xlsx, .xls — max 2 MB</p>
                </div>
            </div>

            @error('file')
            <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('invoices.sample') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    ↓ Download sample file
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Import Invoices
                </button>
            </div>
        </form>
    </div>

    {{-- Column reference --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold mb-4">Column Reference</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Column</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Required</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Format / Default</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['invoice_number',   'No',  'Text',                     'Leave blank to auto-generate. Rows with the same number become one invoice.'],
                        ['customer_name',    'Yes', 'Text',                     'Matched case-insensitively. Created automatically if not found.'],
                        ['invoice_date',     'Yes', 'YYYY-MM-DD or DD/MM/YYYY','Date the invoice was issued.'],
                        ['due_date',         'Yes', 'YYYY-MM-DD or DD/MM/YYYY','Payment due date.'],
                        ['reference',        'No',  'Text',                     'Your PO or reference number.'],
                        ['vat_applicable',   'No',  'yes / no  (default: yes)', '7.5% VAT applied to all VAT-eligible line items.'],
                        ['wht_applicable',   'No',  'yes / no  (default: no)',  'Apply Withholding Tax to this invoice.'],
                        ['wht_rate',         'No',  'Number    (default: 0)',   'WHT rate %. E.g. 5'],
                        ['discount_amount',  'No',  'Number    (default: 0)',   'Fixed discount in ₦.'],
                        ['notes',            'No',  'Text',                     'Internal notes.'],
                        ['terms',            'No',  'Text',                     'Payment terms shown on invoice. E.g. Net 30'],
                        ['item_description', 'Yes', 'Text',                     'Description of this line item.'],
                        ['item_quantity',    'Yes', 'Number',                   'Quantity.'],
                        ['item_unit_price',  'Yes', 'Number',                   'Unit price in ₦ (no commas).'],
                        ['item_vat',         'No',  'yes / no  (default: vat_applicable)', 'Override VAT for this specific line item.'],
                    ] as [$col, $req, $fmt, $note])
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-green-700">{{ $col }}</td>
                        <td class="px-3 py-2">
                            @if($req === 'Yes')
                                <span class="text-red-600 font-semibold">Yes</span>
                            @else
                                <span class="text-gray-400">No</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ $fmt }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rules --}}
    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-xs text-blue-700 space-y-1">
        <p class="font-semibold text-blue-800">Import Rules</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Row 1 must be the header row with the exact column names shown above.</li>
            <li>Multiple rows with the <strong>same invoice_number</strong> are combined into a single invoice — one row per line item.</li>
            <li>Customers are matched by name (case-insensitive). If not found, a new customer record is created.</li>
            <li>Invoices with a <strong>duplicate invoice_number</strong> already in the system are skipped.</li>
            <li>Imported invoices are created with status <strong>Draft</strong>.</li>
            <li>VAT is computed at <strong>7.5%</strong> per Nigerian VAT law.</li>
        </ul>
    </div>

</div>
@endsection
