@extends('layouts.app')

@section('page-title', 'Clients')

@section('content')
<div x-data="{ inviteModal: false }" class="max-w-4xl mx-auto space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-900">Client Companies</h1>
        <p class="text-sm text-gray-500 mt-0.5">For accountants and bookkeepers who manage the books of other businesses.</p>
    </div>

    {{-- What is this page? --}}
    <div class="bg-green-50 border border-green-200 rounded-lg p-5 space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-green-900">What is this page for?</h2>
            <p class="text-sm text-green-900/80 mt-1 leading-relaxed">
                This is your <strong>accounting firm's workspace</strong>. If you look after the books, invoices, payroll or tax filings of
                <strong>other companies</strong>, add them here as <em>clients</em>. Each client keeps a completely separate set of records.
                You switch between them in one click, without logging out or juggling passwords.
            </p>
            <p class="text-sm text-green-900/80 mt-2 leading-relaxed">
                If you are a business owner managing only your own company, you don't need this page.
            </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-3 text-sm">
            <div class="bg-white/70 rounded-md p-3">
                <p class="font-semibold text-gray-900">1. Add a client</p>
                <p class="text-gray-600 mt-1 text-xs leading-relaxed">Create a new company for them, or invite a company that already uses AccountTaxNG.</p>
            </div>
            <div class="bg-white/70 rounded-md p-3">
                <p class="font-semibold text-gray-900">2. Get access</p>
                <p class="text-gray-600 mt-1 text-xs leading-relaxed">Companies you create are linked instantly. Existing companies must approve your request first.</p>
            </div>
            <div class="bg-white/70 rounded-md p-3">
                <p class="font-semibold text-gray-900">3. Enter their workspace</p>
                <p class="text-gray-600 mt-1 text-xs leading-relaxed">Click <em>Enter Workspace</em> to work inside that client's books. Revoke access any time.</p>
            </div>
        </div>

        <div class="bg-white rounded-md border border-green-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Example</p>
            <p class="text-sm text-gray-700 mt-1 leading-relaxed">
                <strong>Adaeze Okoye &amp; Co.</strong> is a small accounting firm in Lagos. It looks after three businesses:
                <strong>Bola's Bakery Ltd</strong>, <strong>Sunrise Logistics</strong> and <strong>Eko Fashion House</strong>.
                Each Monday Adaeze enters Bola's Bakery to record expenses and send invoices. Then she switches to Sunrise Logistics to run
                payroll, and to Eko Fashion House to prepare its VAT return. Every company's records stay separate, and each owner can still
                log in to see their own books.
            </p>
        </div>
    </div>

    {{-- Ways to add a client --}}
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow p-5 flex flex-col">
            <h2 class="text-sm font-semibold text-gray-900">Create a new client</h2>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed flex-1">
                For a company that isn't on AccountTaxNG yet. You set it up with an admin login for the owner, and it is linked to your firm
                immediately, with no approval needed.
            </p>
            <a href="{{ route('partners.clients.create') }}"
               class="mt-4 text-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + Create a New Client
            </a>
        </div>
        <div class="bg-white rounded-lg shadow p-5 flex flex-col">
            <h2 class="text-sm font-semibold text-gray-900">Connect an existing company</h2>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                For a company that already has an AccountTaxNG account. Invite its admin by email, or give them your partner code so they can
                invite you. They must approve before you get access.
            </p>
            <p class="text-xs text-gray-500 mt-2 flex-1">
                Your partner code:
                <span class="font-mono font-semibold text-gray-800 bg-gray-100 rounded px-1.5 py-0.5">{{ $firm->partner_code }}</span>
            </p>
            <button @click="inviteModal = true"
                    class="mt-4 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">
                Invite a Client by Email
            </button>
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
            <h2 class="text-sm font-semibold text-gray-700">Your Clients ({{ $activeLinks->count() }})</h2>
        </div>
        @if($activeLinks->isEmpty())
        <p class="px-6 py-8 text-sm text-gray-400 text-center">
            You aren't managing any companies yet. Create a new client or invite an existing one above to get started.
        </p>
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
