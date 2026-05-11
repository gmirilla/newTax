@extends('layouts.app')
@section('page-title', 'Units of Measure')

@section('content')
<div x-data="{ showForm: false }" class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.items.index') }}" class="text-sm text-green-600 hover:underline">← Back to Items</a>
        @can('create', App\Models\InventoryUnit::class)
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + New Unit
        </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- New Unit Form --}}
    @can('create', App\Models\InventoryUnit::class)
    <div x-show="showForm" x-cloak class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Add Unit of Measure</h2>
        <form method="POST" action="{{ route('inventory.units.store') }}" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="50"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                       placeholder="e.g. kg, carton, litre, set">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2 pb-0.5">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Create
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endcan

    {{-- Units Table --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">All Units</h2>
            <p class="text-xs text-gray-400 mt-0.5">These units appear in the item create/edit form. Deleting a unit that is in use is blocked.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($units as $unit)
            <div x-data="{ editing: false }" class="px-6 py-4">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $unit->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $unit->items_count }} item(s)</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold
                            {{ $unit->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $unit->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @can('update', $unit)
                        <button @click="editing = true"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                        @endcan
                        @can('delete', $unit)
                        <form method="POST" action="{{ route('inventory.units.destroy', $unit) }}"
                              class="inline"
                              onsubmit="return confirm('Delete unit \'{{ addslashes($unit->name) }}\'?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                        @endcan
                    </div>
                </div>

                {{-- Inline edit form --}}
                @can('update', $unit)
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('inventory.units.update', $unit) }}" class="flex items-end gap-3">
                        @csrf @method('PUT')
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ $unit->name }}" required maxlength="50"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <label class="flex items-center gap-1.5 text-sm pb-1">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ $unit->is_active ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-green-600">
                            Active
                        </label>
                        <div class="flex gap-2 pb-0.5">
                            <button type="submit"
                                    class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                Save
                            </button>
                            <button type="button" @click="editing = false"
                                    class="px-4 py-1.5 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
                @endcan

            </div>
            @empty
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-gray-400">No units of measure yet.</p>
                @can('create', App\Models\InventoryUnit::class)
                    <button @click="showForm = true"
                            class="text-sm text-green-600 hover:underline mt-1">Create the first unit →</button>
                @endcan
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
