@extends('layouts.app')
@section('page-title', 'Activity Log')

@section('content')
@php
    $isApproval = fn(string $event): bool => str_contains($event, '.approved')
        || str_contains($event, '.rejected')
        || str_contains($event, '.confirmed')
        || str_contains($event, '.received')
        || str_contains($event, '.started')
        || str_contains($event, '.completed')
        || str_contains($event, '.cancelled');

    $eventColor = function(string $event): string {
        if (str_contains($event, 'approved') || str_contains($event, 'confirmed') || str_contains($event, 'completed'))
            return 'bg-green-100 text-green-700';
        if (str_contains($event, 'rejected') || str_contains($event, 'cancelled'))
            return 'bg-red-100 text-red-700';
        if (str_contains($event, 'started') || str_contains($event, 'received'))
            return 'bg-blue-100 text-blue-700';
        if (str_contains($event, 'created'))
            return 'bg-teal-100 text-teal-700';
        if (str_contains($event, 'updated') || str_contains($event, 'adjusted'))
            return 'bg-indigo-100 text-indigo-700';
        if (str_contains($event, 'deleted'))
            return 'bg-red-100 text-red-700';
        return 'bg-gray-100 text-gray-600';
    };

    $humanEvent = function(string $event): string {
        $map = [
            'restock_request.approved'     => 'Restock Approved',
            'restock_request.rejected'     => 'Restock Rejected',
            'restock_request.received'     => 'Goods Received',
            'restock_request.cancelled'    => 'Restock Cancelled',
            'sales_order.confirmed'        => 'Sales Order Confirmed',
            'sales_order.cancelled'        => 'Sales Order Cancelled',
            'payroll.approved'             => 'Payroll Approved',
            'expense.approved'             => 'Expense Approved',
            'expense.rejected'             => 'Expense Rejected',
            'production_order.started'     => 'Production Started',
            'production_order.completed'   => 'Production Completed',
            'production_order.cancelled'   => 'Production Cancelled',
        ];
        return $map[$event] ?? ucwords(str_replace(['.', '_'], [': ', ' '], $event));
    };

    $moduleIcon = function(string $event): string {
        if (str_starts_with($event, 'restock'))      return '📦';
        if (str_starts_with($event, 'sales_order'))  return '🛒';
        if (str_starts_with($event, 'payroll'))      return '💰';
        if (str_starts_with($event, 'expense'))      return '🧾';
        if (str_starts_with($event, 'production'))   return '🏭';
        return '📋';
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Activity Log</h1>
            <p class="text-sm text-gray-500 mt-0.5">All approval actions and key events in your account</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('activity.index') }}" class="flex flex-wrap gap-3 items-end">

            <div class="flex-1 min-w-40">
                <label class="block text-xs font-medium text-gray-500 mb-1">Team Member</label>
                <select name="user_id" class="w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">All team members</option>
                    @foreach($teamMembers as $member)
                        <option value="{{ $member->id }}" @selected(request('user_id') == $member->id)>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-56">
                <label class="block text-xs font-medium text-gray-500 mb-1">Event Type</label>
                <select name="event" class="w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">All events</option>
                    <optgroup label="Restock">
                        <option value="restock_request.approved"  @selected(request('event') === 'restock_request.approved')>Restock Approved</option>
                        <option value="restock_request.rejected"  @selected(request('event') === 'restock_request.rejected')>Restock Rejected</option>
                        <option value="restock_request.received"  @selected(request('event') === 'restock_request.received')>Goods Received</option>
                        <option value="restock_request.cancelled" @selected(request('event') === 'restock_request.cancelled')>Restock Cancelled</option>
                    </optgroup>
                    <optgroup label="Sales Orders">
                        <option value="sales_order.confirmed"  @selected(request('event') === 'sales_order.confirmed')>Sales Order Confirmed</option>
                        <option value="sales_order.cancelled"  @selected(request('event') === 'sales_order.cancelled')>Sales Order Cancelled</option>
                    </optgroup>
                    <optgroup label="Payroll">
                        <option value="payroll.approved" @selected(request('event') === 'payroll.approved')>Payroll Approved</option>
                    </optgroup>
                    <optgroup label="Expenses">
                        <option value="expense.approved" @selected(request('event') === 'expense.approved')>Expense Approved</option>
                        <option value="expense.rejected" @selected(request('event') === 'expense.rejected')>Expense Rejected</option>
                    </optgroup>
                    <optgroup label="Manufacturing">
                        <option value="production_order.started"   @selected(request('event') === 'production_order.started')>Production Started</option>
                        <option value="production_order.completed" @selected(request('event') === 'production_order.completed')>Production Completed</option>
                        <option value="production_order.cancelled" @selected(request('event') === 'production_order.cancelled')>Production Cancelled</option>
                    </optgroup>
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                    Filter
                </button>
                @if(request()->hasAny(['user_id','event','from','to']))
                <a href="{{ route('activity.index') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded hover:bg-gray-50 text-gray-600">
                    Clear
                </a>
                @endif
            </div>

        </form>
    </div>

    {{-- Results --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
                {{ number_format($logs->total()) }} {{ Str::plural('event', $logs->total()) }}
            </h3>
            @if($logs->total() > 0)
            <span class="text-xs text-gray-400">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
            </span>
            @endif
        </div>

        @if($logs->isEmpty())
        <div class="px-5 py-16 text-center">
            <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm text-gray-400">No activity events match your filters.</p>
        </div>
        @else

        <div class="divide-y divide-gray-100">
            @foreach($logs as $log)
            @php
                $new    = $log->new_values ?? [];
                $old    = $log->old_values ?? [];
                $isAppr = $isApproval($log->event);
                $initiatorName = $new['initiator_name'] ?? null;
                $approverName  = $log->user?->name;
                $reference     = $new['reference'] ?? null;
            @endphp

            <div x-data="{ open: false }">

                {{-- Main row --}}
                <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 cursor-pointer" @click="open = !open">

                    {{-- Icon / event badge --}}
                    <div class="flex-shrink-0 pt-0.5">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $eventColor($log->event) }}">
                            {{ $humanEvent($log->event) }}
                        </span>
                    </div>

                    {{-- Core info --}}
                    <div class="flex-1 min-w-0">

                        {{-- Approval card: initiator → approver --}}
                        @if($isAppr && ($initiatorName || $approverName))
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            @if($initiatorName)
                            <span class="text-gray-500">
                                Submitted by
                                <span class="font-semibold text-gray-800">{{ $initiatorName }}</span>
                            </span>
                            @endif
                            @if($approverName)
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-gray-500">
                                @if(str_contains($log->event, 'approved') || str_contains($log->event, 'confirmed') || str_contains($log->event, 'received') || str_contains($log->event, 'started') || str_contains($log->event, 'completed'))
                                    Approved by
                                @elseif(str_contains($log->event, 'rejected'))
                                    Rejected by
                                @elseif(str_contains($log->event, 'cancelled'))
                                    Cancelled by
                                @else
                                    Action by
                                @endif
                                <span class="font-semibold text-gray-800">{{ $approverName }}</span>
                            </span>
                            @endif
                        </div>

                        {{-- Reference & details row --}}
                        <div class="flex flex-wrap items-center gap-x-3 mt-1 text-xs text-gray-500">
                            @if($reference)
                            <span class="font-mono font-medium text-gray-700">{{ $reference }}</span>
                            @endif
                            @if(isset($new['item']))
                            <span>{{ $new['item'] }}</span>
                            @endif
                            @if(isset($new['amount']))
                            <span>₦{{ number_format($new['amount'], 2) }}</span>
                            @endif
                            @if(isset($new['net_pay']))
                            <span>₦{{ number_format($new['net_pay'], 2) }}</span>
                            @endif
                            @if(isset($new['total_cost']))
                            <span>₦{{ number_format($new['total_cost'], 2) }}</span>
                            @endif
                            @if(isset($new['qty_produced']))
                            <span>Qty produced: {{ $new['qty_produced'] }}</span>
                            @endif
                        </div>

                        @else
                        {{-- Non-approval event --}}
                        <div class="text-sm text-gray-700">
                            @if($log->user)
                                <span class="font-semibold">{{ $log->user->name }}</span>
                                <span class="text-gray-500"> performed this action</span>
                            @else
                                <span class="text-gray-400">System action</span>
                            @endif
                        </div>
                        @if($reference)
                        <div class="mt-0.5 text-xs font-mono text-gray-500">{{ $reference }}</div>
                        @endif
                        @endif

                        {{-- Rejection reason --}}
                        @if(isset($new['rejection_reason']))
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs text-red-600 bg-red-50 rounded px-2 py-1 w-fit">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Reason: {{ $new['rejection_reason'] }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Timestamp + expand --}}
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs font-medium text-gray-700">{{ $log->created_at->format('d M Y, H:i') }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                        @if($new || $old)
                        <button class="mt-1.5 text-xs text-green-600 hover:text-green-800 flex items-center gap-1 ml-auto"
                                @click.stop="open = !open">
                            <span x-text="open ? 'Hide' : 'Detail'"></span>
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        @endif
                    </div>

                </div>

                {{-- Expandable detail panel --}}
                @if($new || $old)
                <div x-show="open" x-cloak style="display:none"
                     class="px-5 pb-4 bg-gray-50 border-b border-gray-200">

                    {{-- Approval summary table for approval events --}}
                    @if($isAppr)
                    <div class="mb-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        @if($initiatorName)
                        <div class="bg-white rounded border border-gray-200 px-3 py-2">
                            <p class="text-gray-400 uppercase tracking-wide mb-0.5">Submitted by</p>
                            <p class="font-semibold text-gray-800">{{ $initiatorName }}</p>
                        </div>
                        @endif
                        @if($approverName)
                        <div class="bg-white rounded border border-gray-200 px-3 py-2">
                            <p class="text-gray-400 uppercase tracking-wide mb-0.5">Action by</p>
                            <p class="font-semibold text-gray-800">{{ $approverName }}</p>
                            <p class="text-gray-400">{{ $log->user?->role }}</p>
                        </div>
                        @endif
                        <div class="bg-white rounded border border-gray-200 px-3 py-2">
                            <p class="text-gray-400 uppercase tracking-wide mb-0.5">Time</p>
                            <p class="font-semibold text-gray-800">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-gray-500">{{ $log->created_at->format('H:i:s') }}</p>
                        </div>
                        @if($reference)
                        <div class="bg-white rounded border border-gray-200 px-3 py-2">
                            <p class="text-gray-400 uppercase tracking-wide mb-0.5">Reference</p>
                            <p class="font-mono font-semibold text-gray-800">{{ $reference }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Raw before/after values --}}
                    <div class="grid gap-3" style="grid-template-columns: {{ ($old && $new) ? '1fr 1fr' : '1fr' }}">
                        @if($old)
                        <div>
                            <p class="text-xs font-semibold text-red-600 mb-1">Before</p>
                            <pre class="text-xs bg-red-50 border border-red-100 rounded p-2 overflow-x-auto text-gray-700 whitespace-pre-wrap">{{ json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        @endif
                        @if($new)
                        <div>
                            <p class="text-xs font-semibold text-green-600 mb-1">Details</p>
                            <pre class="text-xs bg-green-50 border border-green-100 rounded p-2 overflow-x-auto text-gray-700 whitespace-pre-wrap">{{ json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-5 py-3 border-t bg-gray-50">
            {{ $logs->links() }}
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
