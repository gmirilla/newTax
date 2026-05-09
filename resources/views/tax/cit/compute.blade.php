@extends('layouts.app')

@section('page-title', 'Compute CIT')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('tax.cit.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to CIT</a>
    </div>

    {{-- Year selector --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <label class="text-sm font-medium text-gray-700">Tax Year:</label>
            <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year - 1; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Compute</button>
        </form>
    </div>

    {{-- Computation Result --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-sm font-semibold">CIT Computation — {{ $year }}</h3>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">
                    {{ ucfirst($report['company_size']) }} Company — {{ $report['cit_rate'] }}%
                </span>
                @if($report['is_exempt'])
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">CIT Exempt</span>
                @endif
                @if(!empty($report['is_professional_firm']))
                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">Professional Firm</span>
                @endif
            </div>
        </div>
        <div class="p-6">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Gross Turnover (Revenue)</td>
                        <td class="py-2 text-right">₦{{ number_format($report['annual_turnover'], 2) }}</td>
                    </tr>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Less: Allowable Expenses</td>
                        <td class="py-2 text-right text-red-600">(₦{{ number_format($report['allowable_deductions'], 2) }})</td>
                    </tr>
                    <tr class="bg-gray-50 border-b">
                        <td class="py-2 pl-2 font-semibold">Assessable Profit / (Loss)</td>
                        <td class="py-2 text-right font-semibold {{ $report['taxable_profit'] < 0 ? 'text-red-600' : '' }}">
                            ₦{{ number_format($report['taxable_profit'], 2) }}
                        </td>
                    </tr>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 text-gray-600">
                            CIT ({{ $report['cit_rate'] }}% of assessable profit)
                            @if($report['is_exempt'])
                                <span class="text-xs text-green-600 ml-1">— Small company exemption (turnover ≤ ₦50M)</span>
                            @endif
                        </td>
                        <td class="py-2 text-right text-orange-700">₦{{ number_format($report['cit_amount'], 2) }}</td>
                    </tr>
                    @if(!$report['is_exempt'])
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 text-gray-500 text-xs pl-2">
                            Minimum Tax (0.5% × ₦{{ number_format($report['annual_turnover'], 0) }}, min ₦200,000)
                        </td>
                        <td class="py-2 text-right text-xs text-gray-500">₦{{ number_format($report['minimum_tax'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="bg-orange-50 border-b-2 border-orange-200">
                        <td class="py-3 pl-2 font-bold text-orange-800">
                            CIT Payable{{ $report['is_exempt'] ? '' : ' (higher of CIT or Minimum Tax)' }}
                        </td>
                        <td class="py-3 text-right font-bold text-orange-700 text-lg">
                            ₦{{ number_format($report['cit_amount'], 2) }}
                        </td>
                    </tr>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 text-gray-600">
                            Development Levy (4% of assessable profit)
                            @if($report['is_exempt'])
                                <span class="text-xs text-green-600 ml-1">— Exempt</span>
                            @endif
                        </td>
                        <td class="py-2 text-right text-purple-700">₦{{ number_format($report['development_levy'], 2) }}</td>
                    </tr>
                    <tr class="bg-purple-50 border-b">
                        <td class="py-3 pl-2 font-bold text-purple-800">Total Tax Liability (CIT + Development Levy)</td>
                        <td class="py-3 text-right font-bold text-purple-700 text-lg">₦{{ number_format($report['total_tax_due'], 2) }}</td>
                    </tr>
                </tbody>
            </table>

            @if(!$report['is_exempt'])
            <p class="text-xs text-gray-400 mt-3">
                Development Levy replaces TETFund, IT Levy, NASENI and Police Trust Fund contributions (Finance Act 2025).
            </p>
            @endif
        </div>
    </div>

    {{-- Filing status / save form --}}
    @if($record->status === 'pending' || $record->status === 'estimated')
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold mb-4">File This CIT Return</h3>
        <form method="POST" action="{{ route('tax.cit.filed', $record) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">NRS Reference Number</label>
                    <input type="text" name="filing_reference" placeholder="e.g. CIT/{{ $year }}/00123"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Filing Date</label>
                    <input type="date" name="filed_date" value="{{ now()->toDateString() }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2 bg-orange-600 text-white text-sm font-medium rounded-md hover:bg-orange-700">
                    Mark as Filed with FIRS
                </button>
                <p class="text-xs text-gray-400">Submit the official return via your NRS TaxPro-Max account first.</p>
            </div>
        </form>
    </div>
    @else
    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        ✓ CIT for {{ $year }} is recorded as <strong>{{ ucfirst($record->status) }}</strong>.
        @if($record->filing_reference)
            NRS Ref: <strong>{{ $record->filing_reference }}</strong>.
        @endif
        <a href="{{ route('tax.cit.index') }}" class="underline ml-2">View CIT history →</a>
    </div>
    @endif

    <div class="text-xs text-gray-400 text-center">
        CIT returns due within 6 months of financial year-end (e.g., June 30 for Dec 31 year-end).
        Filing deadline: <strong>{{ \Carbon\Carbon::parse($report['due_date'])->format('d M Y') }}</strong>.
    </div>
</div>
@endsection
