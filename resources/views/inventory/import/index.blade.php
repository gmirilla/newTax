@extends('layouts.app')
@section('page-title', 'Import Inventory')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Pending import notice --}}
    @if($hasPending)
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="flex-1 text-sm">
            <p class="font-medium text-amber-800">You have an unconfirmed import pending.</p>
            <p class="text-amber-700 mt-0.5">
                Either go back to the preview to confirm, or upload a new file (this will replace the pending import).
            </p>
        </div>
    </div>
    @endif

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Upload card --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Upload CSV File</h2>
                <p class="text-xs text-gray-500 mt-0.5">Up to {{ number_format(500) }} items per file · 2 MB maximum</p>
            </div>
            <a href="{{ route('inventory.import.sample') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Sample
            </a>
        </div>

        <form method="POST" action="{{ route('inventory.import.preview') }}" enctype="multipart/form-data">
            @csrf

            <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-300
                          rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 hover:border-indigo-400 transition"
                   id="drop-zone">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-500" id="file-label">Click to select or drag and drop a CSV file</p>
                <p class="text-xs text-gray-400 mt-1">.csv only</p>
                <input type="file" name="file" accept=".csv,text/csv" class="hidden" id="file-input">
            </label>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Preview Import
                </button>
            </div>
        </form>
    </div>

    {{-- Format guide --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">CSV Column Reference</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-3 py-2 font-medium text-gray-600 rounded-l-md">Column</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Required</th>
                        <th class="px-3 py-2 font-medium text-gray-600 rounded-r-md">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['name',          'Yes', 'Item name, max 150 characters'],
                        ['sku',           'No',  'Unique identifier. Rows with an SKU already in the system will be skipped.'],
                        ['category',      'No',  'Must exactly match an existing category name (case-insensitive). Unmatched categories are left blank.'],
                        ['unit',          'No',  'Unit of measure (e.g. piece, kg, litre). Defaults to "piece" if blank.'],
                        ['selling_price', 'Yes', 'Number ≥ 0. Use 0 for items not sold directly.'],
                        ['cost_price',    'Yes', 'Purchase cost per unit. Used to calculate opening stock value.'],
                        ['opening_stock', 'No',  'Current quantity on hand. Defaults to 0. Posts a GL entry if > 0.'],
                        ['restock_level', 'No',  'Alert threshold. Defaults to 0 (no alerts).'],
                        ['description',   'No',  'Optional notes, max 1000 characters.'],
                    ] as [$col, $req, $note])
                    <tr>
                        <td class="px-3 py-2 font-mono text-indigo-700">{{ $col }}</td>
                        <td class="px-3 py-2">
                            @if($req === 'Yes')
                                <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">Required</span>
                            @else
                                <span class="text-gray-400">Optional</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-md p-3 text-xs text-blue-700">
            <strong>Accounting note:</strong> Items with opening stock > 0 will post a single journal entry:
            <span class="font-mono">Dr Inventory (1200) / Cr Owner's Equity (3001)</span>.
            Ensure both accounts exist in your Chart of Accounts before importing with opening stock.
        </div>
    </div>

</div>

<script>
const input  = document.getElementById('file-input');
const label  = document.getElementById('file-label');
const zone   = document.getElementById('drop-zone');

input.addEventListener('change', () => {
    label.textContent = input.files[0]?.name ?? 'Click to select or drag and drop a CSV file';
});

zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('border-indigo-400'); });
zone.addEventListener('dragleave', () => zone.classList.remove('border-indigo-400'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('border-indigo-400');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        label.textContent = file.name;
    }
});
</script>
@endsection
