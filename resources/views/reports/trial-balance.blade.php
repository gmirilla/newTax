@extends('layouts.app')

@section('page-title', 'Trial Balance')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <label class="text-sm font-medium text-gray-700">As of:</label>
            <input type="date" name="as_of" value="{{ $report['as_of'] }}"
                   class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Apply</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold">Trial Balance — {{ $report['as_of'] }}</h2>
            </div>
            @if($report['is_balanced'])
                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-sm font-medium px-3 py-1 rounded-full">
                    ✅ Balanced
                </span>
            @else
                <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-sm font-medium px-3 py-1 rounded-full">
                    ⚠️ Out of Balance
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($report['rows'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $row['code'] }}</td>
                        <td class="px-4 py-2">{{ $row['name'] }}</td>
                        <td class="px-4 py-2 capitalize text-xs text-gray-500">{{ $row['type'] }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($row['debits'], 2) }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($row['credits'], 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $row['balance'] < 0 ? 'text-red-600' : '' }}">
                            ₦{{ number_format(abs($row['balance']), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td colspan="3" class="px-4 py-3">Totals</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($report['total_debits'], 2) }}</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($report['total_credits'], 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($report['is_balanced'])
                                <span class="text-green-600">0.00</span>
                            @else
                                <span class="text-red-600">₦{{ number_format(abs($report['total_debits'] - $report['total_credits']), 2) }}</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
