@extends('layouts.app')

@section('page-title', 'Team Members')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ inviteModal: false }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Team Members</h1>
            @php
                $userCount  = $tenant->users()->count();
                $userLimit  = $tenant->plan?->limit('users');
            @endphp
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $userCount }} {{ $userCount === 1 ? 'member' : 'members' }}
                @if($userLimit !== null)
                    of {{ $userLimit }} on your plan
                @else
                    (unlimited)
                @endif
            </p>
        </div>
        @if($tenant->withinLimit('users'))
        <button @click="inviteModal = true"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Invite Member
        </button>
        @else
        <a href="{{ route('billing') }}?upgrade_feature=users"
           class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-md hover:bg-amber-600">
            🔒 Upgrade to Add More
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Members table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Name</th>
                    <th class="px-6 py-3 text-left font-semibold">Email</th>
                    <th class="px-6 py-3 text-center font-semibold">Role</th>
                    <th class="px-6 py-3 text-center font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $member)
                @php
                    $isSelf    = $member->id === auth()->id();
                    $isAdmin   = $member->role === 'admin';
                    $showMods  = !$isSelf && !$isAdmin;
                    $modAccess = $member->module_access ?? \App\Models\User::moduleDefaults($member->role);
                @endphp
                <tr x-data="{ modulesOpen: false }" class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">
                        {{ $member->name }}
                        @if($isSelf)
                        <span class="ml-1 text-xs text-gray-400">(you)</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $member->email }}</td>

                    {{-- Role selector --}}
                    <td class="px-6 py-3 text-center">
                        @if($isSelf)
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold
                            {{ $member->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : ($member->role === 'accountant' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst($member->role) }}
                        </span>
                        @else
                        <form method="POST" action="{{ route('team.role', $member) }}">
                            @csrf @method('PATCH')
                            <select name="role"
                                    data-current="{{ $member->role }}"
                                    onchange="
                                        var sel = this;
                                        var newRole = sel.options[sel.selectedIndex].text;
                                        var msg = 'Change {{ addslashes($member->name) }}\'s role to ' + newRole + '?';
                                        if (sel.value === 'admin') msg += '\n\nAdmins have full access including billing and user management.';
                                        if (confirm(msg)) { sel.form.submit(); } else { sel.value = sel.dataset.current; }
                                    "
                                    class="text-xs border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                                @foreach(['admin', 'accountant', 'staff'] as $r)
                                <option value="{{ $r }}" {{ $member->role === $r ? 'selected' : '' }}>
                                    {{ ucfirst($r) }}
                                </option>
                                @endforeach
                            </select>
                        </form>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-6 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $member->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-3 text-right">
                        @if(!$isSelf)
                        <div class="flex items-center justify-end gap-3">
                            @if($showMods)
                            <button @click="modulesOpen = !modulesOpen"
                                    :class="modulesOpen ? 'text-indigo-700' : 'text-indigo-500 hover:text-indigo-700'"
                                    class="text-xs font-medium">
                                <span x-text="modulesOpen ? 'Hide Access' : 'Module Access'"></span>
                            </button>
                            @endif
                            <form method="POST" action="{{ route('team.toggle', $member) }}">
                                @csrf
                                <button type="submit"
                                        class="text-xs {{ $member->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('team.destroy', $member) }}"
                                  onsubmit="return confirm('Remove {{ addslashes($member->name) }} from the team? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </form>
                        </div>
                        @else
                        <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>
                </tr>

                {{-- Module access panel (inline below the row) --}}
                @if($showMods)
                <tr x-show="modulesOpen" x-cloak>
                    <td colspan="5" class="px-6 pb-4 bg-indigo-50/40 border-t border-indigo-100">
                        <form method="POST" action="{{ route('team.modules', $member) }}">
                            @csrf
                            <p class="text-xs font-semibold text-indigo-700 mt-3 mb-2">
                                Module access for {{ $member->name }}
                                @if($isAdmin)
                                    <span class="font-normal text-gray-400 ml-1">— Admins always have full access</span>
                                @endif
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-3">
                                @foreach(\App\Models\User::MODULE_LIST as $modKey => $modLabel)
                                <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer select-none">
                                    <input type="checkbox"
                                           name="modules[]"
                                           value="{{ $modKey }}"
                                           {{ !empty($modAccess[$modKey]) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $modLabel }}
                                </label>
                                @endforeach
                            </div>
                            <button type="submit"
                                    class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700">
                                Save Access
                            </button>
                            <button type="button" @click="modulesOpen = false"
                                    class="ml-2 px-3 py-1.5 text-xs text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                Cancel
                            </button>
                        </form>
                    </td>
                </tr>
                @endif

                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pending + recently expired invites --}}
    @if($invites->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold text-gray-700">Invitations</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Email</th>
                    <th class="px-6 py-3 text-center font-semibold">Role</th>
                    <th class="px-6 py-3 text-left font-semibold">Invited by</th>
                    <th class="px-6 py-3 text-left font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invites as $invite)
                @php $expired = $invite->expires_at->isPast(); @endphp
                <tr class="hover:bg-gray-50 {{ $expired ? 'opacity-60' : '' }}">
                    <td class="px-6 py-3 text-gray-700">{{ $invite->email }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                            {{ ucfirst($invite->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $invite->inviter?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-xs">
                        @if($expired)
                            <span class="text-red-500 font-medium">Expired</span>
                            <span class="text-gray-400 ml-1">{{ $invite->expires_at->diffForHumans() }}</span>
                        @else
                            <span class="text-green-600 font-medium">Pending</span>
                            <span class="text-gray-400 ml-1">expires {{ $invite->expires_at->diffForHumans() }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <form method="POST" action="{{ route('team.invite.resend', $invite) }}">
                                @csrf
                                <button type="submit"
                                        class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Resend
                                </button>
                            </form>
                            <form method="POST" action="{{ route('team.invite.cancel', $invite) }}"
                                  onsubmit="return confirm('Cancel this invitation?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Cancel</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Role reference --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Role Permissions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-4">
            <div class="p-3 bg-indigo-50 rounded-lg">
                <p class="font-semibold text-indigo-800 mb-1">Admin</p>
                <p class="text-indigo-600 text-xs leading-relaxed">Full access to all modules — invoices, settings, billing, user management, all reports and tax. Module flags are ignored.</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
                <p class="font-semibold text-blue-800 mb-1">Accountant</p>
                <p class="text-blue-600 text-xs leading-relaxed">All modules on by default. Can be restricted to specific modules. Cannot access settings or billing.</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="font-semibold text-gray-700 mb-1">Staff</p>
                <p class="text-gray-500 text-xs leading-relaxed">All modules off by default. Enable specific modules (e.g. Inventory, Manufacturing) to grant access to those areas.</p>
            </div>
        </div>
        <div class="border-t pt-3">
            <p class="text-xs text-gray-500">
                <span class="font-medium text-gray-700">Module access</span> controls which sections a user can see.
                Write operations (creating invoices, approving orders, etc.) still require Accountant or Admin role.
                Staff with a module enabled get view access and limited operational actions (e.g. stock adjustments, starting/completing production orders).
            </p>
        </div>
    </div>

</div>

{{-- Invite Modal --}}
<div x-show="inviteModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @keydown.escape.window="inviteModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
         @click.outside="inviteModal = false">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Invite Team Member</h3>
        <form method="POST" action="{{ route('team.invite') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Email address</label>
                <input type="email" name="email" required
                       placeholder="colleague@example.com"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="accountant">Accountant — all modules, no billing/settings</option>
                    <option value="staff">Staff — module access configured after joining</option>
                    <option value="admin">Admin — full access</option>
                </select>
                <p class="mt-1 text-xs text-gray-400">The invitation link expires in 72 hours.</p>
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="inviteModal = false"
                        class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                    Send Invitation
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
