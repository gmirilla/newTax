@extends('layouts.app')
@section('page-title', 'Import Preview')

@section('content')
<div class="space-y-5">

    {{-- Validation errors from commit (e.g. missing GL accounts) --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm space-y-1">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Summary bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Will Import</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($validCount) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Skipped (Duplicate SKU)</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($dupeCount) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Errors</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($errorCount) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-indigo-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Opening Stock Value</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($totalValue, 2) }}</p>
        </div>
    </div>

    {{-- GL accounting notice --}}
    @if($totalValue > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
        Items with opening stock will post journal entries on confirm (total <span class="font-mono font-medium">₦{{ number_format($totalValue, 2) }}</span>):
        products → Dr 1200, raw materials → Dr 1201, finished goods → Dr 1202, all crediting Owner's Equity (3001).
        Ensure the relevant accounts exist in your Chart of Accounts.
    </div>
    @endif

    @if($errorCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
        {{ number_format($errorCount) }} row(s) have errors and will <strong>not</strong> be imported.
        Fix the errors in your CSV and re-upload to include those items.
    </div>
    @endif

    {{-- Row table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Row Review</h2>
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-green-100 border border-green-300 inline-block"></span> Import</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-yellow-50 border border-yellow-300 inline-block"></span> Skip (duplicate)</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-50 border border-red-300 inline-block"></span> Error</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-left border-b">
                        <th class="px-3 py-2 font-medium text-gray-600 w-10">#</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Name</th>
                        <th class="px-3 py-2 font-medium text-gray-600">SKU</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Category</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Type</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Unit</th>
                        <th class="px-3 py-2 font-medium text-gray-600 text-right">Selling Price</th>
                        <th class="px-3 py-2 font-medium text-gray-600 text-right">Cost Price</th>
                        <th class="px-3 py-2 font-medium text-gray-600 text-right">Opening Qty</th>
                        <th class="px-3 py-2 font-medium text-gray-600 text-right">Stock Value</th>
                        <th class="px-3 py-2 font-medium text-gray-600 text-right">Restock At</th>
                        <th class="px-3 py-2 font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rows as $row)
                        @php
                            $isError = ! empty($row['errors']);
                            $isDupe  = $row['is_duplicate'];
                            $rowBg   = $isError ? 'bg-red-50' : ($isDupe ? 'bg-yellow-50' : 'bg-green-50');
                        @endphp
                        <tr class="{{ $rowBg }}">
                            <td class="px-3 py-2 text-gray-400">{{ $row['row'] }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800 max-w-[180px] truncate" title="{{ $row['name'] }}">
                                {{ $row['name'] ?: '—' }}
                            </td>
                            <td class="px-3 py-2 font-mono text-gray-600">{{ $row['sku'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600">
                                @if($row['category_name'] !== '')
                                    {{ $row['category_name'] }}
                                    @if(! $row['category_found'])
                                        <span class="text-amber-600" title="Category not found — item will be uncategorised">*</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            @php
                                $typeLabels = [
                                    'product'       => ['label' => 'Product',       'class' => 'bg-gray-100 text-gray-600'],
                                    'raw_material'  => ['label' => 'Raw Material',  'class' => 'bg-orange-100 text-orange-700'],
                                    'finished_good' => ['label' => 'Finished Good', 'class' => 'bg-purple-100 text-purple-700'],
                                    'semi_finished' => ['label' => 'Semi-finished', 'class' => 'bg-indigo-100 text-indigo-700'],
                                ];
                                $typeMeta = $typeLabels[$row['item_type']] ?? $typeLabels['product'];
                            @endphp
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $typeMeta['class'] }}">
                                    {{ $typeMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $row['unit'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($row['selling_price'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($row['cost_price'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($row['opening_stock'], 3) }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">
                                @if($row['opening_stock'] > 0)
                                    ₦{{ number_format($row['opening_stock'] * $row['cost_price'], 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700">
                                {{ $row['restock_level'] > 0 ? number_format($row['restock_level'], 3) : '—' }}
                            </td>
                            <td class="px-3 py-2">
                                @if($isError)
                                    <div class="text-red-700 space-y-0.5">
                                        @foreach($row['errors'] as $err)
                                            <p>{{ $err }}</p>
                                        @endforeach
                                    </div>
                                @elseif($isDupe)
                                    <span class="text-yellow-700 font-medium">Duplicate SKU — skipping</span>
                                @else
                                    <span class="text-green-700 font-medium">Ready</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(collect($rows)->contains(fn($r) => $r['category_name'] !== '' && ! $r['category_found']))
        <div class="px-4 py-2 bg-amber-50 border-t border-amber-100 text-xs text-amber-700">
            * Category name not found — these items will be created without a category.
            Create the category first if needed, then re-upload.
        </div>
        @endif
    </div>

    {{-- Action buttons --}}
    <div class="flex items-center gap-4">
        @if($validCount > 0)
        <form method="POST" action="{{ route('inventory.import.commit') }}">
            @csrf
            <button type="submit"
                    class="px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Confirm Import — {{ number_format($validCount) }} item{{ $validCount !== 1 ? 's' : '' }}
            </button>
        </form>
        @else
        <button disabled class="px-6 py-2.5 bg-gray-300 text-gray-500 text-sm font-medium rounded-md cursor-not-allowed">
            Nothing to Import
        </button>
        @endif

        <a href="{{ route('inventory.import.index') }}"
           class="px-4 py-2.5 border border-gray-300 text-sm text-gray-700 rounded-md hover:bg-gray-50">
            Upload a Different File
        </a>

        <form method="POST" action="{{ route('inventory.import.cancel') }}" class="ml-auto">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 underline">
                Cancel &amp; Discard
            </button>
        </form>
    </div>

</div>
@endsection
