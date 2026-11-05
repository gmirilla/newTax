@extends('layouts.app')

@section('page-title', 'Clients')

@section('content')
<div x-data="{ inviteModal: false }" class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Clients</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Companies you manage bookkeeping for. Your partner code:
                <span class="font-mono font-semibold text-gray-700">{{ $firm->partner_code }}</span>
                — share this with a client who wants to invite you.
            </p>
        </div>
        <div class="flex gap-2">
            <button @click="inviteModal = true"
                    class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">
                Invite a Client by Email
            </button>
            <a href="{{ route('partners.clients.create') }}"
               class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + Create a New Client
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Active clients --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Active ({{ $activeLinks->count() }})</h2>
        </div>
        @if($activeLinks->isEmpty())
        <p class="px-6 py-8 text-sm text-gray-400 text-center">No active clients yet.</p>
        @else
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($activeLinks as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $link->clientTenant->name }}</td>
                    <td class="px-6 py-3 text-gray-500">Linked {{ $link->approved_at?->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                        <form method="POST" action="{{ route('partners.clients.enter', $link) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">Enter Workspace</button>
                        </form>
                        <form method="POST" action="{{ route('partners.clients.revoke', $link) }}" class="inline"
                              onsubmit="return confirm('Stop managing {{ addslashes($link->clientTenant->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Revoke</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Pending outgoing (we invited them) --}}
    @if($pendingOutgoing->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Awaiting Their Approval ({{ $pendingOutgoing->count() }})</h2>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($pendingOutgoing as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-600">Invite sent {{ $link->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('partners.clients.revoke', $link) }}" class="inline"
                              onsubmit="return confirm('Cancel this request?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Cancel</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Pending incoming (a client requested us) --}}
    @if($pendingIncoming->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Incoming Requests ({{ $pendingIncoming->count() }})</h2>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($pendingIncoming as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $link->clientTenant->name }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                        <form method="POST" action="{{ route('partners.clients.approve', $link) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('partners.clients.revoke', $link) }}" class="inline"
                              onsubmit="return confirm('Decline this request?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Decline</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Invite modal --}}
    <template x-teleport="body">
        <div x-show="inviteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             @click.self="inviteModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-semibold">Invite a Client by Email</h3>
                    <button type="button" @click="inviteModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('partners.clients.request') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Client's admin email</label>
                        <input type="email" name="email" required placeholder="admin@theirclientcompany.com"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-400 mt-1">They'll receive an email asking them to approve bookkeeping access.</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="inviteModal = false"
                                class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">Send Invite</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
