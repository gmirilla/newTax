@extends('layouts.app')

@section('page-title', 'Manage Accounting Partners')

@section('content')
<div x-data="{ inviteModal: false }" class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Accounting Partners</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Firms with bookkeeping access to your account — invoices, expenses, and reports only.
                They never have access to billing, your team, or company settings.
            </p>
        </div>
        <button @click="inviteModal = true"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Invite a Firm by Code
        </button>
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Active firms --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Active ({{ $activeLinks->count() }})</h2>
        </div>
        @if($activeLinks->isEmpty())
        <p class="px-6 py-8 text-sm text-gray-400 text-center">No accounting partners linked yet.</p>
        @else
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($activeLinks as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $link->firmTenant->name }}</td>
                    <td class="px-6 py-3 text-gray-500">Linked {{ $link->approved_at?->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('partners.manage.revoke', $link) }}" class="inline"
                              onsubmit="return confirm('Revoke {{ addslashes($link->firmTenant->name) }}\'s access?')">
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

    {{-- Pending outgoing (we requested a firm) --}}
    @if($pendingOutgoing->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Awaiting Their Approval ({{ $pendingOutgoing->count() }})</h2>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($pendingOutgoing as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $link->firmTenant->name }}</td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('partners.manage.revoke', $link) }}" class="inline"
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

    {{-- Pending incoming (a firm invited us) --}}
    @if($pendingIncoming->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Incoming Requests ({{ $pendingIncoming->count() }})</h2>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($pendingIncoming as $link)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $link->firmTenant->name }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                        <form method="POST" action="{{ route('partners.manage.approve', $link) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('partners.manage.revoke', $link) }}" class="inline"
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
                    <h3 class="text-base font-semibold">Invite a Firm by Code</h3>
                    <button type="button" @click="inviteModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('partners.manage.request') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner code</label>
                        <input type="text" name="partner_code" required placeholder="e.g. ABCD1234"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-400 mt-1">Ask your accounting firm for their partner code.</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="inviteModal = false"
                                class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
