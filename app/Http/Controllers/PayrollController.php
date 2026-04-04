<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayeService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayeService $payeService
    ) {}

    public function index(Request $request): View
    {
        $tenant   = $request->user()->tenant;
        $payrolls = Payroll::where('tenant_id', $tenant->id)
            ->orderBy('pay_year', 'desc')
            ->orderBy('pay_month', 'desc')
            ->paginate(12);

        return view('payroll.index', compact('payrolls'));
    }

    public function create(Request $request): View
    {
        $tenant    = $request->user()->tenant;
        $employees = Employee::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        return view('payroll.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pay_year'  => 'required|integer|min:2020|max:' . (now()->year + 1),
            'pay_month' => 'required|integer|min:1|max:12',
            'pay_date'  => 'required|date',
        ]);

        $tenant = $request->user()->tenant;

        // Check if payroll already exists for this period
        $exists = Payroll::where('tenant_id', $tenant->id)
            ->where('pay_year', $request->pay_year)
            ->where('pay_month', $request->pay_month)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Payroll for this period already exists.');
        }

        DB::transaction(function () use ($request, $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            $payroll = Payroll::create([
                'tenant_id'  => $tenant->id,
                'pay_year'   => $request->pay_year,
                'pay_month'  => $request->pay_month,
                'pay_date'   => $request->pay_date,
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]);

            $totals = [
                'gross'            => 0,
                'paye'             => 0,
                'pension'          => 0,
                'employer_pension' => 0,
                'nhf'              => 0,
                'nhis'             => 0,
                'net'              => 0,
            ];

            foreach ($employees as $employee) {
                $overrides = $request->input("employees.{$employee->id}", []);
                $item      = $this->payeService->computeMonthlyPaye($employee, $overrides);
                $item->payroll_id  = $payroll->id;
                $item->employee_id = $employee->id;
                $item->save();

                $totals['gross']            += $item->gross_pay;
                $totals['paye']             += $item->paye_tax;
                $totals['pension']          += $item->pension_employee;
                $totals['employer_pension'] += $item->pension_employer;
                $totals['nhf']              += $item->nhf;
                $totals['nhis']             += ($item->nhis ?? 0);
                $totals['net']              += $item->net_pay;
            }

            $payroll->update([
                'total_gross'            => round($totals['gross'], 2),
                'total_paye'             => round($totals['paye'], 2),
                'total_pension'          => round($totals['pension'], 2),
                'total_employer_pension' => round($totals['employer_pension'], 2),
                'total_nhf'              => round($totals['nhf'], 2),
                'total_nhis'             => round($totals['nhis'], 2),
                'total_net'              => round($totals['net'], 2),
            ]);
        });

        return redirect()->route('payroll.index')
            ->with('success', 'Payroll processed successfully.');
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load(['items.employee', 'creator', 'approver']);

        return view('payroll.show', compact('payroll'));
    }

    public function approve(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Only draft payrolls can be approved.');
        }

        $payroll->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Payroll approved.');
    }

    /**
     * Recompute all payslip figures for a draft payroll using current employee
     * salary structures and current tax rates, while preserving per-run
     * overrides (bonus, overtime, deductions, notes) entered at run time.
     */
    public function recompute(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Only draft payrolls can be recomputed.');
        }

        DB::transaction(function () use ($payroll) {
            $payroll->load('items.employee');

            $totals = [
                'gross'            => 0,
                'paye'             => 0,
                'pension'          => 0,
                'employer_pension' => 0,
                'nhf'              => 0,
                'nhis'             => 0,
                'net'              => 0,
            ];

            foreach ($payroll->items as $item) {
                $employee = $item->employee;

                // Preserve the per-run overrides entered when the payroll was created
                $overrides = [
                    'bonus'             => (float)$item->bonus,
                    'overtime'          => (float)$item->overtime,
                    'loan_deduction'    => (float)($item->loan_deduction ?? 0),
                    'advance_deduction' => (float)($item->advance_deduction ?? 0),
                    'penalty_deduction' => (float)($item->penalty_deduction ?? 0),
                    'other_deductions'  => (float)($item->other_deductions ?? 0),
                    'notes'             => $item->notes,
                ];

                // Recompute using current employee salary data + current tax rates
                $fresh = $this->payeService->computeMonthlyPaye($employee, $overrides);

                $item->update([
                    'basic_salary'          => $fresh->basic_salary,
                    'housing_allowance'     => $fresh->housing_allowance,
                    'transport_allowance'   => $fresh->transport_allowance,
                    'medical_allowance'     => $fresh->medical_allowance,
                    'utility_allowance'     => $fresh->utility_allowance,
                    'other_allowances'      => $fresh->other_allowances,
                    'gross_pay'             => $fresh->gross_pay,
                    'pension_employee'      => $fresh->pension_employee,
                    'pension_employer'      => $fresh->pension_employer,
                    'nhf'                   => $fresh->nhf,
                    'nhis'                  => $fresh->nhis,
                    'home_loan_relief'      => $fresh->home_loan_relief,
                    'life_insurance_relief' => $fresh->life_insurance_relief,
                    'rent_relief'           => $fresh->rent_relief,
                    'consolidated_relief'   => $fresh->consolidated_relief,
                    'taxable_income'        => $fresh->taxable_income,
                    'paye_tax'              => $fresh->paye_tax,
                    'net_pay'               => $fresh->net_pay,
                ]);

                $totals['gross']            += $fresh->gross_pay;
                $totals['paye']             += $fresh->paye_tax;
                $totals['pension']          += $fresh->pension_employee;
                $totals['employer_pension'] += $fresh->pension_employer;
                $totals['nhf']              += $fresh->nhf;
                $totals['nhis']             += ($fresh->nhis ?? 0);
                $totals['net']              += $fresh->net_pay;
            }

            $payroll->update([
                'total_gross'            => round($totals['gross'], 2),
                'total_paye'             => round($totals['paye'], 2),
                'total_pension'          => round($totals['pension'], 2),
                'total_employer_pension' => round($totals['employer_pension'], 2),
                'total_nhf'              => round($totals['nhf'], 2),
                'total_nhis'             => round($totals['nhis'], 2),
                'total_net'              => round($totals['net'], 2),
            ]);
        });

        return back()->with('success', 'Payroll recomputed with current salary structures and tax rates.');
    }

    public function payslip(PayrollItem $item): View
    {
        $item->load(['employee', 'payroll.tenant']);
        $payslip = $this->payeService->generatePayslip($item);

        return view('payroll.payslip', compact('payslip'));
    }

    public function employees(Request $request): View
    {
        $tenant    = $request->user()->tenant;
        $employees = Employee::where('tenant_id', $tenant->id)
            ->orderBy('last_name')
            ->paginate(20);

        return view('payroll.employees', compact('employees'));
    }

    public function createEmployee(Request $request): View
    {
        return view('payroll.employee-form');
    }

    public function editEmployee(Employee $employee): View
    {
        $this->ensureSameTenant($employee);
        return view('payroll.employee-form', compact('employee'));
    }

    public function updateEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensureSameTenant($employee);

        $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'email'                  => 'nullable|email',
            'hire_date'              => 'required|date',
            'job_title'              => 'required|string|max:100',
            'basic_salary'           => 'required|numeric|min:30000',
            'nhis_amount'            => 'nullable|numeric|min:0',
            'home_loan_interest'     => 'nullable|numeric|min:0',
            'life_insurance_premium' => 'nullable|numeric|min:0',
            'annual_rent'            => 'nullable|numeric|min:0',
        ]);

        $employee->update(array_merge(
            $request->only([
                'first_name', 'last_name', 'email', 'phone', 'address',
                'state_of_residence', 'tin', 'bank_name', 'account_number', 'account_name',
                'hire_date', 'termination_date', 'job_title', 'department', 'employment_type',
                'basic_salary', 'housing_allowance', 'transport_allowance',
                'medical_allowance', 'utility_allowance', 'other_allowances',
                'nhis_amount', 'home_loan_interest', 'life_insurance_premium', 'annual_rent',
            ]),
            [
                'nhf_enabled'  => $request->boolean('nhf_enabled', true),
                'nhis_enabled' => $request->boolean('nhis_enabled', false),
            ]
        ));

        $employee->gross_salary = $employee->calculateGrossSalary();
        $employee->save();

        return redirect()->route('payroll.employees')
            ->with('success', "{$employee->full_name}'s profile updated.");
    }

    /** Abort if the employee belongs to a different tenant. */
    private function ensureSameTenant(Employee $employee): void
    {
        if ($employee->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'email'                  => 'nullable|email',
            'hire_date'              => 'required|date',
            'job_title'              => 'required|string|max:100',
            'basic_salary'           => 'required|numeric|min:30000',
            'nhis_amount'            => 'nullable|numeric|min:0',
            'home_loan_interest'     => 'nullable|numeric|min:0',
            'life_insurance_premium' => 'nullable|numeric|min:0',
            'annual_rent'            => 'nullable|numeric|min:0',
        ]);

        $tenant     = $request->user()->tenant;
        $employeeId = 'EMP-' . str_pad(
            Employee::where('tenant_id', $tenant->id)->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $employee = Employee::create(array_merge(
            $request->only([
                'first_name', 'last_name', 'email', 'phone', 'address',
                'state_of_residence', 'tin', 'bank_name', 'account_number', 'account_name',
                'hire_date', 'job_title', 'department', 'employment_type',
                'basic_salary', 'housing_allowance', 'transport_allowance',
                'medical_allowance', 'utility_allowance', 'other_allowances',
                'nhis_amount', 'home_loan_interest', 'life_insurance_premium', 'annual_rent',
            ]),
            [
                'tenant_id'    => $tenant->id,
                'employee_id'  => $employeeId,
                'nhf_enabled'  => $request->boolean('nhf_enabled', true),
                'nhis_enabled' => $request->boolean('nhis_enabled', false),
            ]
        ));

        $employee->gross_salary = $employee->calculateGrossSalary();
        $employee->save();

        return redirect()->route('payroll.employees')
            ->with('success', "Employee {$employee->full_name} added.");
    }
}
