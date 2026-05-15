@extends('layouts.app')
@section('page-title', 'Bills of Materials')

@section('content')
<div x-data="{
    showForm: {{ $errors->any() && !old('_editing') ? 'true' : 'false' }},
    editId: null,
    lines: {{ old('lines') ? json_encode(old('lines')) : '[]' }},
    addLine() {
        this.lines.push({ raw_material_item_id: '', quantity_required: '' });
    },
    removeLine(i) {
        this.lines.splice(i, 1);
    },
    initEdit(bom) {
        this.editId = bom.id;
        this.lines  = bom.lines.map(l => ({
            raw_material_item_id: l.raw_material_item_id,
            quantity_required: l.quantity_required
        }));
    }
}" class="max-w-5xl space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Bills of Materials</h1>
            <p class="text-sm text-gray-500 mt-0.5">Define what raw materials go into each finished or semi-finished product.</p>
        </div>
        @can('create', App\Models\Bom::class)
        <button @click="showForm = !showForm; editId = null; lines = [{}]"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + New BOM
        </button>
        @endcan
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any() && !$errors->has('_editing'))
    <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- New BOM form --}}
    @can('create', App\Models\Bom::class)
    <div x-show="showForm && !editId" x-cloak class="bg-white rounded-lg shadow p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900">New Bill of Materials</h2>

        <form method="POST" action="{{ route('manufacturing.boms.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Finished / Semi-finished Product <span class="text-red-500">*</span></label>
                    <select name="finished_item_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">— Select Item —</option>
                        @foreach($finishedItems as $item)
                            <option value="{{ $item->id }}" {{ old('finished_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}{{ $item->sku ? ' ('.$item->sku.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('finished_item_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @if($finishedItems->isEmpty())
                        <p class="mt-1 text-xs text-yellow-600">No finished / semi-finished items found.
                            <a href="{{ route('inventory.items.create') }}" class="underline">Create one →</a>
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">BOM Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Standard Formula v1">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Version</label>
                        <input type="text" name="version" value="{{ old('version', '1.0') }}" maxlength="20"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Yield Qty <span class="text-red-500">*</span></label>
                        <input type="number" name="yield_qty" value="{{ old('yield_qty', 1) }}" min="0.001" step="0.001" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @error('yield_qty')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Raw material lines --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Raw Materials <span class="text-red-500">*</span></h3>
                    <button type="button" @click="addLine()"
                            class="text-xs text-green-600 hover:underline font-medium">+ Add Material</button>
                </div>

                @error('lines')<p class="mb-2 text-xs text-red-600">{{ $message }}</p>@enderror

                <div class="space-y-2">
                    <template x-for="(line, i) in lines" :key="i">
                        <div class="flex gap-2 items-start">
                            <div class="flex-1">
                                <select :name="`lines[${i}][raw_material_item_id]`" x-model="line.raw_material_item_id" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">— Raw Material —</option>
                                    @foreach($rawMaterials as $rm)
                                        <option value="{{ $rm->id }}">{{ $rm->name }}{{ $rm->sku ? ' ('.$rm->sku.')' : '' }} — {{ $rm->unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <input type="number" :name="`lines[${i}][quantity_required]`" x-model="line.quantity_required"
                                       min="0.001" step="0.001" required placeholder="Qty"
                                       class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <button type="button" @click="removeLine(i)"
                                    class="mt-1 text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                @if($rawMaterials->isEmpty())
                    <p class="mt-2 text-xs text-yellow-600">No raw material items found. Set an item's type to "Raw Material" to use it in a BOM.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" maxlength="1000" rows="2"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Create BOM
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endcan

    {{-- BOMs List --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">All Bills of Materials</h2>
            <span class="text-sm text-gray-500">{{ $boms->count() }} BOM(s)</span>
        </div>

        @forelse($boms as $bom)
        <div x-data="{
            editing: false,
            lines: {{ $bom->lines->map(fn($l) => ['raw_material_item_id' => $l->raw_material_item_id, 'quantity_required' => $l->quantity_required])->toJson() }},
            addLine() { this.lines.push({ raw_material_item_id: '', quantity_required: '' }); },
            removeLine(i) { this.lines.splice(i, 1); }
        }" class="border-b last:border-b-0">

            {{-- View row --}}
            <div x-show="!editing" class="px-6 py-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900">{{ $bom->name }}</p>
                            <span class="text-xs text-gray-400">v{{ $bom->version }}</span>
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                {{ $bom->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $bom->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Produces: <strong>{{ $bom->finishedItem->name }}</strong>
                            × {{ number_format($bom->yield_qty, 3) }}
                        </p>
                        <div class="mt-2 space-y-1">
                            @foreach($bom->lines as $line)
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-gray-300 mr-1"></span>
                                {{ $line->rawMaterial->name }} — {{ number_format($line->quantity_required, 3) }} {{ $line->rawMaterial->unit }}
                                <span class="text-gray-400">(avg cost: ₦{{ number_format($line->rawMaterial->avg_cost, 2) }})</span>
                            </p>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        @can('update', $bom)
                        <button @click="editing = true"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                        @endcan
                        @can('delete', $bom)
                        <form method="POST" action="{{ route('manufacturing.boms.destroy', $bom) }}"
                              onsubmit="return confirm('Delete this BOM? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Edit row --}}
            @can('update', $bom)
            <div x-show="editing" x-cloak class="px-6 py-4 bg-gray-50">
                <form method="POST" action="{{ route('manufacturing.boms.update', $bom) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_editing" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">BOM Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ $bom->name }}" required maxlength="100"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Version</label>
                                <input type="text" name="version" value="{{ $bom->version }}" maxlength="20"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Yield Qty</label>
                                <input type="number" name="yield_qty" value="{{ $bom->yield_qty }}" min="0.001" step="0.001" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1" {{ $bom->is_active ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-green-600">
                                Active
                            </label>
                        </div>
                    </div>

                    {{-- Lines --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-medium text-gray-700">Raw Materials</h4>
                            <button type="button" @click="addLine()"
                                    class="text-xs text-green-600 hover:underline">+ Add</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(line, i) in lines" :key="i">
                                <div class="flex gap-2 items-start">
                                    <div class="flex-1">
                                        <select :name="`lines[${i}][raw_material_item_id]`" x-model="line.raw_material_item_id" required
                                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">— Raw Material —</option>
                                            @foreach($rawMaterials as $rm)
                                                <option value="{{ $rm->id }}" :selected="line.raw_material_item_id == {{ $rm->id }}">
                                                    {{ $rm->name }}{{ $rm->sku ? ' ('.$rm->sku.')' : '' }} — {{ $rm->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <input type="number" :name="`lines[${i}][quantity_required]`" x-model="line.quantity_required"
                                               min="0.001" step="0.001" required placeholder="Qty"
                                               class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <button type="button" @click="removeLine(i)"
                                            class="mt-1 text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Notes</label>
                        <textarea name="notes" maxlength="1000" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ $bom->notes }}</textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                            Save Changes
                        </button>
                        <button type="button" @click="editing = false"
                                class="px-3 py-1.5 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
            @endcan
        </div>
        @empty
        <div class="px-6 py-12 text-center text-sm text-gray-500">
            No Bills of Materials defined yet.
            @can('create', App\Models\Bom::class)
            <button @click="showForm = true; editId = null; lines = [{}]"
                    class="ml-1 text-green-600 hover:underline font-medium">Create your first BOM →</button>
            @endcan
        </div>
        @endforelse
    </div>

</div>
@endsection
