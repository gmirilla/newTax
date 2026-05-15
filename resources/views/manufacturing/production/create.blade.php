@extends('layouts.app')
@section('page-title', 'New Production Order')

@section('content')
<div class="max-w-3xl" x-data="{
    selectedBom: null,
    boms: {{ $boms->map(fn($b) => [
        'id'         => $b->id,
        'name'       => $b->name,
        'version'    => $b->version,
        'yield_qty'  => (float) $b->yield_qty,
        'item_name'  => $b->finishedItem->name,
        'lines'      => $b->lines->map(fn($l) => [
            'material' => $l->rawMaterial->name . ($l->rawMaterial->sku ? ' ('.$l->rawMaterial->sku.')' : '') . ' — ' . $l->rawMaterial->unit,
            'qty_req'  => (float) $l->quantity_required,
            'avg_cost' => (float) $l->rawMaterial->avg_cost,
            'stock'    => (float) $l->rawMaterial->current_stock,
        ])->values()->toArray(),
    ])->values()->toJson() }},
    qtyPlanned: '',
    get preview() {
        if (!this.selectedBom || !this.qtyPlanned) return [];
        let factor = parseFloat(this.qtyPlanned) / this.selectedBom.yield_qty;
        return this.selectedBom.lines.map(l => ({
            material: l.material,
            needed:   (l.qty_req * factor).toFixed(3),
            stock:    l.stock.toFixed(3),
            ok:       l.stock >= l.qty_req * factor,
            cost:     (l.qty_req * factor * l.avg_cost).toFixed(2),
        }));
    },
    selectBom(id) {
        this.selectedBom = this.boms.find(b => b.id == id) || null;
    }
}">

    <div class="mb-4">
        <a href="{{ route('manufacturing.production.index') }}" class="text-sm text-green-600 hover:underline">← Back to Production Orders</a>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('manufacturing.production.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-900">Production Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bill of Materials <span class="text-red-500">*</span></label>
                <select name="bom_id" required @change="selectBom($event.target.value)"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">— Select BOM —</option>
                    @foreach($boms as $bom)
                        <option value="{{ $bom->id }}" {{ old('bom_id') == $bom->id ? 'selected' : '' }}>
                            {{ $bom->name }} v{{ $bom->version }} → {{ $bom->finishedItem->name }}
                        </option>
                    @endforeach
                </select>
                @error('bom_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                @if($boms->isEmpty())
                    <p class="mt-1 text-xs text-yellow-600">
                        No active BOMs found. <a href="{{ route('manufacturing.boms.index') }}" class="underline">Create one →</a>
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity to Produce <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity_planned" value="{{ old('quantity_planned') }}"
                           x-model="qtyPlanned" min="0.001" step="0.001" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. 100">
                    <p x-show="selectedBom" class="mt-1 text-xs text-gray-400">
                        BOM yields <span x-text="selectedBom?.yield_qty"></span> units per batch
                    </p>
                    @error('quantity_planned')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Additional Cost (₦)</label>
                    <input type="number" name="additional_cost" value="{{ old('additional_cost', 0) }}"
                           min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="Labour, energy, overheads…">
                    @error('additional_cost')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" maxlength="1000" rows="2"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Materials preview --}}
        <div x-show="selectedBom && qtyPlanned > 0" x-cloak class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Materials Required</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 border-b">
                            <th class="pb-2">Material</th>
                            <th class="pb-2 text-right">Needed</th>
                            <th class="pb-2 text-right">In Stock</th>
                            <th class="pb-2 text-right">Est. Cost (₦)</th>
                            <th class="pb-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, i) in preview" :key="i">
                            <tr>
                                <td class="py-2 text-gray-700" x-text="row.material"></td>
                                <td class="py-2 text-right font-medium" x-text="row.needed"></td>
                                <td class="py-2 text-right text-gray-500" x-text="row.stock"></td>
                                <td class="py-2 text-right text-gray-500" x-text="Number(row.cost).toLocaleString()"></td>
                                <td class="py-2 text-center">
                                    <span :class="row.ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                          class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                          x-text="row.ok ? 'OK' : 'Insufficient'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-400">
                Stock availability is checked again at completion time. Insufficient stock will block the order from completing.
            </p>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Create Production Order
            </button>
            <a href="{{ route('manufacturing.production.index') }}"
               class="px-5 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>

</div>
@endsection
