@extends('layouts.app')

@section('page-title', 'Balance Sheet')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <label class="text-sm font-medium text-gray-700">As of:</label>
            <input type="date" name="as_of" value="{{ $report['as_of'] }}"
                   class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Apply</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold">Balance Sheet</h2>
            <p class="text-sm text-gray-500">As of {{ $report['as_of'] }}</p>
            @if(!$report['is_balanced'])
                <p class="text-xs text-red-600 mt-1">⚠️ Balance sheet is out of balance. Please review journal entries.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x">

            {{-- Assets --}}
            <div class="p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Assets</h3>
                <table class="w-full text-sm">
                    @foreach($report['assets'] as $row)
                    <tr>
                        <td class="py-1.5 text-gray-600">{{ $row['code'] }} – {{ $row['name'] }}</td>
                        <td class="py-1.5 text-right font-medium">₦{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t-2 border-gray-400 font-bold">
                        <td class="py-2">Total Assets</td>
                        <td class="py-2 text-right">₦{{ number_format($report['total_assets'], 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Liabilities + Equity --}}
            <div class="p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Liabilities</h3>
                <table class="w-full text-sm">
                    @foreach($report['liabilities'] as $row)
                    <tr>
                        <td class="py-1.5 text-gray-600">{{ $row['code'] }} – {{ $row['name'] }}</td>
                        <td class="py-1.5 text-right font-medium text-red-600">₦{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t font-semibold">
                        <td class="py-2">Total Liabilities</td>
                        <td class="py-2 text-right text-red-600">₦{{ number_format($report['total_liabilities'], 2) }}</td>
                    </tr>
                </table>

                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3 mt-4">Equity</h3>
                <table class="w-full text-sm">
                    @foreach($report['equity'] as $row)
                    <tr>
                        <td class="py-1.5 text-gray-600">{{ $row['code'] }} – {{ $row['name'] }}</td>
                        <td class="py-1.5 text-right font-medium text-blue-600">₦{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t font-semibold">
                        <td class="py-2">Total Equity</td>
                        <td class="py-2 text-right text-blue-600">₦{{ number_format($report['total_equity'], 2) }}</td>
                    </tr>
                    <tr class="border-t-2 border-gray-400 font-bold">
                        <td class="py-2">Total Liabilities + Equity</td>
                        <td class="py-2 text-right">₦{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
