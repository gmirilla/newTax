@extends('layouts.app')
@section('page-title', 'Inventory Categories')

@section('content')
<div x-data="{ showForm: false, editId: null, editName: '', editDesc: '', editActive: true }" class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.items.index') }}" class="text-sm text-green-600 hover:underline">← Back to Items</a>
        @can('create', App\Models\InventoryCategory::class)
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + New Category
        </button>
        @endcan
    </div>

    {{-- New Category Form --}}
    @can('create', App\Models\InventoryCategory::class)
    <div x-show="showForm" x-cloak class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Add Category</h2>
        <form method="POST" action="{{ route('inventory.categories.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Building Materials">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="500"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="Optional description">
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Create Category
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endcan

    {{-- Categories Table --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">All Categories</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($categories as $category)
            <div x-data="{ editing: false }" class="px-6 py-4">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $category->name }}</p>
                        @if($category->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $category->description }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">{{ $category->items_count }} item(s)</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold
                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @can('update', $category)
                        <button @click="editing = true"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                        @endcan
                        @can('delete', $category)
                        <form method="POST" action="{{ route('inventory.categories.destroy', $category) }}"
                              class="inline"
                              onsubmit="return confirm('Delete category {{ addslashes($category->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                        @endcan
                    </div>
                </div>

                {{-- Inline edit form --}}
                @can('update', $category)
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('inventory.categories.update', $category) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Name</label>
                                <input type="text" name="name" value="{{ $category->name }}" required maxlength="100"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Description</label>
                                <input type="text" name="description" value="{{ $category->description }}" maxlength="500"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ $category->is_active ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-green-600">
                            Active
                        </label>
                        <div class="flex gap-2">
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
                <p class="text-sm text-gray-400">No categories yet.</p>
                @can('create', App\Models\InventoryCategory::class)
                    <button @click="showForm = true"
                            class="text-sm text-green-600 hover:underline mt-1">Create the first category →</button>
                @endcan
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
