@extends('layouts.app')
@section('page-title', 'Inventory Locations')

@section('content')
<div class="max-w-4xl space-y-6" x-data="{ showAdd: false, editId: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Locations</h1>
            <p class="text-sm text-gray-500 mt-0.5">Stores, warehouses, and branches for your inventory.</p>
        </div>
        <button @click="showAdd = !showAdd"
                class="btn-primary text-sm px-4 py-2 rounded-lg flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Location
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Add form --}}
    <div x-show="showAdd" x-cloak x-transition
         class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">New Location</h2>
        <form method="POST" action="{{ route('inventory.locations.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="e.g. Lagos Warehouse">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" name="code" maxlength="20" value="{{ old('code') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="e.g. LOS-WH">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" maxlength="100" value="{{ old('city') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" maxlength="100" value="{{ old('state') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="e.g. Lagos">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="address" maxlength="500" value="{{ old('address') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Contact Name</label>
                    <input type="text" name="contact_name" maxlength="100" value="{{ old('contact_name') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="text" name="contact_phone" maxlength="50" value="{{ old('contact_phone') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_default" value="1" id="new_is_default"
                       class="w-4 h-4 text-green-600 border-gray-300 rounded">
                <label for="new_is_default" class="text-sm text-gray-700">Set as default location</label>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn-primary text-sm px-4 py-2 rounded-lg">Save Location</button>
                <button type="button" @click="showAdd = false" class="text-sm px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Locations table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        @if($locations->isEmpty())
            <div class="p-10 text-center text-sm text-gray-500">No locations yet. Add your first location above.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-700">Location</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700 hidden md:table-cell">City / State</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700 hidden lg:table-cell">Contact</th>
                    <th class="px-4 py-3 font-medium text-gray-700 text-center">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($locations as $loc)
                <tr class="hover:bg-gray-50 {{ $loc->id == $active->id ? 'bg-green-50/50' : '' }}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">
                            {{ $loc->name }}
                            @if($loc->is_default)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 uppercase tracking-wide">Default</span>
                            @endif
                            @if($loc->id == $active->id)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase tracking-wide">Active</span>
                            @endif
                        </div>
                        @if($loc->code)<div class="text-xs text-gray-400 mt-0.5">{{ $loc->code }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell">
                        {{ collect([$loc->city, $loc->state])->filter()->implode(', ') ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">
                        {{ $loc->contact_name ?: '—' }}
                        @if($loc->contact_phone)<div class="text-xs text-gray-400">{{ $loc->contact_phone }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($loc->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            {{-- Switch to this location --}}
                            @if($loc->id != $active->id && $loc->is_active)
                            <form method="POST" action="{{ route('inventory.locations.switch') }}">
                                @csrf
                                <input type="hidden" name="location_id" value="{{ $loc->id }}">
                                <button type="submit" class="text-xs text-green-600 hover:text-green-700 font-medium px-2 py-1 rounded hover:bg-green-50">Switch</button>
                            </form>
                            @endif

                            {{-- Edit (inline) --}}
                            <button @click="editId = (editId === {{ $loc->id }} ? null : {{ $loc->id }})"
                                    class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100">Edit</button>

                            {{-- Deactivate --}}
                            @if(!$loc->is_default && $loc->is_active)
                            <form method="POST" action="{{ route('inventory.locations.destroy', $loc) }}"
                                  onsubmit="return confirm('Deactivate {{ addslashes($loc->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50">Deactivate</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Inline edit row --}}
                <tr x-show="editId === {{ $loc->id }}" x-cloak>
                    <td colspan="5" class="px-4 pb-4 bg-gray-50 border-b border-gray-200">
                        <form method="POST" action="{{ route('inventory.locations.update', $loc) }}" class="pt-3 space-y-3">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                                    <input type="text" name="name" required maxlength="100" value="{{ $loc->name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                                    <input type="text" name="code" maxlength="20" value="{{ $loc->code }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">City</label>
                                    <input type="text" name="city" maxlength="100" value="{{ $loc->city }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">State</label>
                                    <input type="text" name="state" maxlength="100" value="{{ $loc->state }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Contact Name</label>
                                    <input type="text" name="contact_name" maxlength="100" value="{{ $loc->contact_name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Contact Phone</label>
                                    <input type="text" name="contact_phone" maxlength="50" value="{{ $loc->contact_phone }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>
                            <div class="flex items-center gap-6 flex-wrap">
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_default" value="1" {{ $loc->is_default ? 'checked' : '' }}
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded">
                                    Set as default
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_active" value="1" {{ $loc->is_active ? 'checked' : '' }}
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded">
                                    Active
                                </label>
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="btn-primary text-sm px-3 py-1.5 rounded-lg">Save</button>
                                <button type="button" @click="editId = null" class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
