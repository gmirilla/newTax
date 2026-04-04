@extends('layouts.app')

@section('page-title', 'Withholding Tax')

@section('content')
<div class="space-y-6">

    {{-- Period selector --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">View</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Gross Amount</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($summary['total_gross'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $summary['period'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">WHT Deducted</p>
            <p class="text-2xl font-bold text-purple-700">₦{{ number_format($summary['total_wht'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Net Payment</p>
            <p class="text-2xl font-bold text-green-700">₦{{ number_format($summary['total_net'], 2) }}</p>
        </div>
    </div>

    {{-- Breakdown by type --}}
    @if($summary['by_type']->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold mb-3">Breakdown by Transaction Type</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Type</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Count</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Gross</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">WHT</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($summary['by_type'] as $type => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $type) }}</td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ $data['count'] }}</td>
                    <td class="px-4 py-2 text-right">₦{{ number_format($data['gross'], 2) }}</td>
                    <td class="px-4 py-2 text-right text-purple-700 font-medium">₦{{ number_format($data['wht_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Rate Reference --}}
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold mb-3">Nigerian WHT Rates Reference</h3>
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Transaction Type</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Company Rate</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Individual Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach([
                    ['type' => 'Contracts (Construction/Supply)', 'company' => '5%', 'individual' => '5%'],
                    ['type' => 'Professional Services',           'company' => '5%', 'individual' => '10%'],
                    ['type' => 'Management / Technical Services', 'company' => '5%', 'individual' => '10%'],
                    ['type' => 'Rent / Lease',                   'company' => '10%', 'individual' => '10%'],
                    ['type' => 'Dividends',                      'company' => '10%', 'individual' => '10%'],
                    ['type' => 'Interest / Royalties',           'company' => '10%', 'individual' => '10%'],
                ] as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-1.5 text-gray-700">{{ $r['type'] }}</td>
                    <td class="px-4 py-1.5 text-right font-medium text-purple-700">{{ $r['company'] }}</td>
                    <td class="px-4 py-1.5 text-right font-medium text-blue-700">{{ $r['individual'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- WHT Records --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">WHT Records — {{ $summary['period'] }}</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Vendor</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Type</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Gross</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Rate</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">WHT</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Net</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($record->deduction_date)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-2">
                        {{ $record->vendor?->name ?? '—' }}
                        @if($record->vendor_tin)
                            <span class="text-xs text-gray-400 block">TIN: {{ $record->vendor_tin }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500 capitalize">
                        {{ str_replace('_', ' ', $record->transaction_type) }}
                        <span class="block text-gray-400">{{ $record->is_company ? 'Company' : 'Individual' }}</span>
                    </td>
                    <td class="px-4 py-2 text-right">₦{{ number_format($record->gross_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ $record->wht_rate }}%</td>
                    <td class="px-4 py-2 text-right font-medium text-purple-700">₦{{ number_format($record->wht_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right text-green-700">₦{{ number_format($record->net_payment, 2) }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $record->filing_status === 'remitted' ? 'bg-green-100 text-green-700' :
                               ($record->filing_status === 'filed'   ? 'bg-blue-100 text-blue-700' :
                                                                       'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($record->filing_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        @if($record->filing_status === 'pending')
                        <form method="POST" action="{{ route('tax.wht.remit', $record) }}">
                            @csrf
                            <button type="submit" class="text-xs text-green-600 hover:underline">
                                Mark Remitted
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-400 text-sm">
                        No WHT records for {{ $summary['period'] }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($records->isNotEmpty())
            <tfoot class="bg-gray-50 text-sm font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-2">Totals</td>
                    <td class="px-4 py-2 text-right">₦{{ number_format($records->sum('gross_amount'), 2) }}</td>
                    <td></td>
                    <td class="px-4 py-2 text-right text-purple-700">₦{{ number_format($records->sum('wht_amount'), 2) }}</td>
                    <td class="px-4 py-2 text-right text-green-700">₦{{ number_format($records->sum('net_payment'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <div class="text-xs text-gray-400 text-center">
        WHT rates per CITA Cap C21, PITA Cap P8 LFN 2004 as amended. Remittance due by 21st of the month following deduction.
    </div>
</div>
@endsection
