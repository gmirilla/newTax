@extends('layouts.app')
@section('page-title', 'Storefront Categories')

@section('content')
<div x-data="{ showForm: false }" class="max-w-3xl mx-auto space-y-5">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-500">Group your storefront products and services into categories so customers can browse by section.</p>
    </div>

    <div class="flex items-center justify-end">
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
            + New Category
        </button>
    </div>

    {{-- New Category Form --}}
    <div x-show="showForm" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Add Category</h2>
        <form method="POST" action="{{ route('storefront.categories.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Groceries">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="500"
                           class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="Optional description">
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Create Category
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-200 text-sm rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    {{-- Categories Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">All Categories</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($categories as $category)
            <div x-data="{ editing: false }" class="px-6 py-4 hover:bg-gray-50/60 transition-colors">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $category->name }}</p>
                        @if($category->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $category->description }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">{{ $category->products_count }} product(s) &middot; {{ $category->services_count }} service(s)</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold
                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button @click="editing = true"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                        <form method="POST" action="{{ route('storefront.categories.destroy', $category) }}"
                              class="inline"
                              onsubmit="return confirm('Delete category {{ addslashes($category->name) }}? Its products and services will become uncategorized.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                    </div>
                </div>

                {{-- Inline edit form --}}
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('storefront.categories.update', $category) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Name</label>
                                <input type="text" name="name" value="{{ $category->name }}" required maxlength="100"
                                       class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Description</label>
                                <input type="text" name="description" value="{{ $category->description }}" maxlength="500"
                                       class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
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
                                    class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                Save
                            </button>
                            <button type="button" @click="editing = false"
                                    class="px-4 py-1.5 border border-gray-200 text-sm rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

            </div>
            @empty
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-gray-400">No categories yet.</p>
                <button @click="showForm = true"
                        class="text-sm text-green-600 hover:underline mt-1">Create the first category →</button>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
