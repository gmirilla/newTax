@extends('layouts.app')

@section('page-title', 'Employees')

@section('content')
<div class="space-y-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold">Employees ({{ $employees->total() }})</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('payroll.employees.sample') }}"
                   class="px-3 py-1.5 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
                    ⬇ Sample CSV
                </a>
                <a href="{{ route('payroll.employees.import') }}"
                   class="px-3 py-1.5 border border-indigo-300 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-50">
                    ↑ Import
                </a>
                <a href="{{ route('payroll.employees.create') }}"
                   class="px-4 py-1.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    + Add Employee
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Job Title</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Basic Salary</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Salary</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">State (PAYE)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hire Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employees as $emp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $emp->employee_id }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-400">{{ $emp->department }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $emp->job_title }}</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($emp->basic_salary, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">
                            ₦{{ number_format($emp->gross_salary, 2) }}
                            <div class="flex justify-end gap-1 mt-0.5">
                                @if($emp->nhf_enabled)
                                    <span class="text-xs bg-blue-50 text-blue-500 px-1 rounded">NHF</span>
                                @endif
                                @if($emp->nhis_enabled)
                                    <span class="text-xs bg-purple-50 text-purple-500 px-1 rounded">HMO</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $emp->state_of_residence ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $emp->hire_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                {{ $emp->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $emp->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('payroll.employees.edit', $emp) }}"
                               class="text-xs text-indigo-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No employees yet. <a href="{{ route('payroll.employees.create') }}" class="text-green-600">Add first employee</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection
