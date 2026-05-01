<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        // My recent submitted expenses
        $myExpenses = Expense::where('tenant_id', $tenant->id)
            ->where('created_by', $user->id)
            ->orderByDesc('expense_date')
            ->limit(10)
            ->get();

        // Link to payroll via email match
        $employee = Employee::where('tenant_id', $tenant->id)
            ->where('email', $user->email)
            ->first();

        $recentPayslips = $employee
            ? $employee->payrollItems()
                ->with('payroll')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
            : collect();

        return view('staff.dashboard', compact('myExpenses', 'employee', 'recentPayslips', 'user'));
    }
}
