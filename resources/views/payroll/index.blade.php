@extends('layouts.app')

@section('page-title', 'Payroll')

@section('content')
<div class="space-y-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold">Payroll Runs</h2>
            <a href="{{ route('payroll.create') }}"
               class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + Run Payroll
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PAYE Deducted</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Net Pay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pay Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($payrolls as $payroll)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $payroll->getMonthName() }}</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($payroll->total_gross, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">₦{{ number_format($payroll->total_paye, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">₦{{ number_format($payroll->total_pension, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-green-700">₦{{ number_format($payroll->total_net, 2) }}</td>
                        <td class="px-4 py-3 text-sm">{{ $payroll->pay_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            @php $colors = ['draft'=>'yellow','approved'=>'green','paid'=>'blue'] @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $colors[$payroll->status] ?? 'gray' }}-100
                                text-{{ $colors[$payroll->status] ?? 'gray' }}-800">
                                {{ ucfirst($payroll->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm flex items-center gap-3">
                            <a href="{{ route('payroll.show', $payroll) }}" class="text-green-600 hover:underline">View</a>
                            @if($payroll->status === 'draft')
                            <form method="POST" action="{{ route('payroll.recompute', $payroll) }}"
                                  onsubmit="return confirm('Recompute {{ $payroll->getMonthName() }} payroll with current tax rates?')">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:underline text-xs">↺ Recompute</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No payroll runs yet. <a href="{{ route('payroll.create') }}" class="text-green-600">Run first payroll</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $payrolls->links() }}
        </div>
    </div>
</div>
@endsection
