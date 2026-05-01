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
                @php $isSelf = $member->id === auth()->id(); @endphp
                <tr class="hover:bg-gray-50">
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
                            <select name="role" onchange="this.form.submit()"
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
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pending invites --}}
    @if($invites->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold text-gray-700">Pending Invitations</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Email</th>
                    <th class="px-6 py-3 text-center font-semibold">Role</th>
                    <th class="px-6 py-3 text-left font-semibold">Invited by</th>
                    <th class="px-6 py-3 text-left font-semibold">Expires</th>
                    <th class="px-6 py-3 text-right font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invites as $invite)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-700">{{ $invite->email }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                            {{ ucfirst($invite->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $invite->inviter?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $invite->expires_at->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('team.invite.cancel', $invite) }}"
                              onsubmit="return confirm('Cancel this invitation?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Cancel</button>
                        </form>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="p-3 bg-indigo-50 rounded-lg">
                <p class="font-semibold text-indigo-800 mb-1">Admin</p>
                <p class="text-indigo-600 text-xs leading-relaxed">Full access — invoices, settings, billing, user management, all reports and tax.</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
                <p class="font-semibold text-blue-800 mb-1">Accountant</p>
                <p class="text-blue-600 text-xs leading-relaxed">Invoices, quotes, expenses, transactions, payroll, tax, and reports. Cannot access settings or billing.</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="font-semibold text-gray-700 mb-1">Staff</p>
                <p class="text-gray-500 text-xs leading-relaxed">View own payslips and submitted expenses only. No access to financial data.</p>
            </div>
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
                    <option value="accountant">Accountant — invoices, expenses, reports</option>
                    <option value="staff">Staff — payslips and expenses only</option>
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
