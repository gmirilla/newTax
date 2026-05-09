@extends('layouts.app')

@section('page-title', 'Import Employees')

@section('content')
<div class="max-w-3xl mx-auto space-y-6"
     x-data="{
         dragOver: false,
         fileName: '',
         updateMode: false,
         onFile(e) {
             const f = e.target.files[0] || (e.dataTransfer && e.dataTransfer.files[0]);
             this.fileName = f ? f.name : '';
         }
     }">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Import Employees</h1>
            <p class="text-sm text-gray-500 mt-0.5">Upload a CSV or Excel file to bulk-create employees.</p>
        </div>
        <a href="{{ route('payroll.employees.sample') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">
            ⬇ Download Sample CSV
        </a>
    </div>

    {{-- Import form --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <form method="POST" action="{{ route('payroll.employees.import') }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Drop zone --}}
            <div class="relative"
                 @dragover.prevent="dragOver = true"
                 @dragleave.prevent="dragOver = false"
                 @drop.prevent="dragOver = false; onFile($event); $refs.fileInput.files = $event.dataTransfer.files">
                <label :class="dragOver ? 'border-green-500 bg-green-50' : 'border-gray-300 bg-gray-50'"
                       class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-lg cursor-pointer transition-colors hover:border-green-500 hover:bg-green-50">
                    <div class="flex flex-col items-center justify-center gap-2 pointer-events-none">
                        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span x-show="!fileName" class="text-sm text-gray-500">
                            Drag & drop your file here, or <span class="text-green-600 font-medium">browse</span>
                        </span>
                        <span x-show="fileName" x-text="fileName" class="text-sm font-medium text-green-700"></span>
                        <span class="text-xs text-gray-400">.csv, .xlsx, .xls — max 5 MB</span>
                    </div>
                    <input x-ref="fileInput" type="file" name="file" class="hidden"
                           accept=".csv,.xlsx,.xls" @change="onFile($event)" required>
                </label>
            </div>

            {{-- Update existing toggle --}}
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="update_existing" value="0">
                <input type="checkbox" name="update_existing" value="1"
                       x-model="updateMode"
                       class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span>
                    <span class="block text-sm font-medium text-gray-700">Update existing employees</span>
                    <span class="block text-xs text-gray-500">When checked, rows whose email matches an existing employee update that record instead of being skipped.</span>
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50"
                        :disabled="!fileName">
                    Import Employees
                </button>
            </div>
        </form>
    </div>

    {{-- Import errors (from previous run) --}}
    @if(session('import_errors'))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-sm font-semibold text-red-700 mb-2">The following rows were skipped:</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach(session('import_errors') as $err)
            <li class="text-xs text-red-600">{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Column reference --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold mb-3">Column Reference</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Column Name</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Required?</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Format / Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['first_name',            'Yes', 'Text'],
                        ['last_name',             'Yes', 'Text'],
                        ['job_title',             'Yes', 'Text'],
                        ['basic_salary',          'Yes', 'Numeric ₦ — minimum ₦30,000'],
                        ['hire_date',             'Yes', 'YYYY-MM-DD or DD/MM/YYYY'],
                        ['email',                 'No',  'Must be unique within your company (used for update matching)'],
                        ['phone',                 'No',  'Text'],
                        ['department',            'No',  'Text'],
                        ['employment_type',       'No',  'full_time | part_time | contract  (default: full_time)'],
                        ['state_of_residence',    'No',  'Nigerian state name — used for PAYE'],
                        ['tin',                   'No',  'NRS Tax Identification Number'],
                        ['housing_allowance',     'No',  'Numeric ₦ (default 0)'],
                        ['transport_allowance',   'No',  'Numeric ₦ (default 0)'],
                        ['medical_allowance',     'No',  'Numeric ₦ (default 0)'],
                        ['utility_allowance',     'No',  'Numeric ₦ (default 0)'],
                        ['other_allowances',      'No',  'Numeric ₦ (default 0)'],
                        ['nhf_enabled',           'No',  'yes / no  (default: yes)'],
                        ['nhis_enabled',          'No',  'yes / no  (default: no)'],
                        ['nhis_amount',           'No',  'Fixed monthly NHIS/HMO amount in ₦'],
                        ['home_loan_interest',    'No',  'Annual home loan interest paid (NTA 2025 relief)'],
                        ['life_insurance_premium','No',  'Annual life insurance premium (NTA 2025 relief)'],
                        ['annual_rent',           'No',  'Annual rent paid (NTA 2025 relief, max ₦500k)'],
                        ['bank_name',             'No',  'Text'],
                        ['account_number',        'No',  'Text'],
                        ['account_name',          'No',  'Text'],
                    ] as [$col, $req, $note])
                    <tr class="{{ $req === 'Yes' ? 'bg-green-50/40' : '' }}">
                        <td class="px-3 py-1.5 font-mono text-gray-700">{{ $col }}</td>
                        <td class="px-3 py-1.5">
                            @if($req === 'Yes')
                                <span class="text-green-700 font-semibold">Required</span>
                            @else
                                <span class="text-gray-400">Optional</span>
                            @endif
                        </td>
                        <td class="px-3 py-1.5 text-gray-500">{{ $note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
