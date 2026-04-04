<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', fn() => redirect()->route('dashboard'));

// ─── Authenticated + Tenant-scoped routes ───────────────────────────────────
Route::middleware(['auth', 'tenant', 'audit'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Customers & Vendors (quick-create via AJAX) ───────────────────────────
    Route::post('/customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
    Route::post('/vendors/quick',   [VendorController::class,  'quickStore'])->name('vendors.quick-store');

    // ── Invoices ──────────────────────────────────────────────────────────────
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',               [InvoiceController::class, 'index'])->name('index');
        Route::get('/create',         [InvoiceController::class, 'create'])->name('create');
        Route::post('/',              [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}',      [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}',      [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}',   [InvoiceController::class, 'destroy'])->name('destroy');
        // Payment & actions
        Route::post('/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('payment');
        Route::get('/{invoice}/pdf',      [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/{invoice}/send',    [InvoiceController::class, 'sendEmail'])->name('send');
        Route::post('/{invoice}/void',    [InvoiceController::class, 'void'])->name('void');
    });

    // ── Transactions & Expenses ───────────────────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',              [TransactionController::class, 'index'])->name('index');
        Route::get('/create',        [TransactionController::class, 'create'])->name('create');
        Route::post('/',             [TransactionController::class, 'store'])->name('store');
        // Expenses — must be declared before /{id} to prevent "expenses" being treated as an ID
        Route::get('/expenses',      [TransactionController::class, 'expenses'])->name('expenses');
        Route::post('/expenses',     [TransactionController::class, 'storeExpense'])->name('expenses.store');
        Route::get('/{id}',          [TransactionController::class, 'show'])->name('show')->whereNumber('id');
    });

    // ── Tax Compliance ────────────────────────────────────────────────────────
    Route::prefix('tax')->name('tax.')->group(function () {
        Route::get('/',                    [TaxController::class, 'dashboard'])->name('dashboard');

        // VAT
        Route::prefix('vat')->name('vat.')->group(function () {
            Route::get('/',           [TaxController::class, 'vatIndex'])->name('index');
            Route::get('/compute',    [TaxController::class, 'vatCompute'])->name('compute');
            Route::post('/{return}/filed', [TaxController::class, 'vatFiled'])->name('filed');
            Route::post('/{return}/paid',  [TaxController::class, 'vatPaid'])->name('paid');
        });

        // WHT
        Route::prefix('wht')->name('wht.')->group(function () {
            Route::get('/',                              [TaxController::class, 'whtIndex'])->name('index');
            Route::post('/{whtRecord}/remit',            [TaxController::class, 'whtRemit'])->name('remit');
        });

        // CIT
        Route::prefix('cit')->name('cit.')->group(function () {
            Route::get('/',           [TaxController::class, 'citIndex'])->name('index');
            Route::get('/compute',    [TaxController::class, 'citCompute'])->name('compute');
            Route::post('/{record}/filed', [TaxController::class, 'citFiled'])->name('filed');
        });
    });

    // ── Payroll ───────────────────────────────────────────────────────────────
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/',                 [PayrollController::class, 'index'])->name('index');
        Route::get('/create',           [PayrollController::class, 'create'])->name('create');
        Route::post('/',                [PayrollController::class, 'store'])->name('store');

        // Employees — static routes must come before /{payroll} wildcard
        Route::get('/employees',                    [PayrollController::class, 'employees'])->name('employees');
        Route::get('/employees/create',             [PayrollController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees',                   [PayrollController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{employee}/edit',    [PayrollController::class, 'editEmployee'])->name('employees.edit');
        Route::put('/employees/{employee}',         [PayrollController::class, 'updateEmployee'])->name('employees.update');

        // Parameterized routes last
        Route::get('/item/{item}/payslip',    [PayrollController::class, 'payslip'])->name('payslip');
        Route::get('/{payroll}',              [PayrollController::class, 'show'])->name('show')->whereNumber('payroll');
        Route::post('/{payroll}/approve',     [PayrollController::class, 'approve'])->name('approve')->whereNumber('payroll');
        Route::post('/{payroll}/recompute',   [PayrollController::class, 'recompute'])->name('recompute')->whereNumber('payroll');
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',              [ReportController::class, 'index'])->name('index');
        Route::get('/profit-loss',   [ReportController::class, 'profitAndLoss'])->name('pl');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('bs');
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('tb');
        Route::get('/vat',           [ReportController::class, 'vatReport'])->name('vat');
        Route::get('/cit',           [ReportController::class, 'citReport'])->name('cit');
        Route::get('/tax-summary',   [ReportController::class, 'taxSummary'])->name('tax-summary');
    });

});

// ─── Super Admin (platform owner) routes ────────────────────────────────────
Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {

        Route::get('/',           [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/companies',  [SuperAdminController::class, 'companies'])->name('companies');

        Route::prefix('companies/{tenant}')->name('companies.')->group(function () {
            Route::get('/',                  [SuperAdminController::class, 'showCompany'])->name('show');
            Route::post('/toggle',           [SuperAdminController::class, 'toggleActive'])->name('toggle');
            Route::patch('/subscription',    [SuperAdminController::class, 'updateSubscription'])->name('subscription');
            Route::post('/remind',           [SuperAdminController::class, 'sendReminder'])->name('remind');
            Route::post('/impersonate',      [SuperAdminController::class, 'impersonate'])->name('impersonate');
        });

        Route::post('/bulk-reminder',        [SuperAdminController::class, 'sendBulkReminder'])->name('bulk-reminder');
    });

// Exit impersonation (accessible while impersonating, outside superadmin middleware)
Route::middleware(['auth'])
    ->post('/superadmin/exit-impersonate', [SuperAdminController::class, 'exitImpersonate'])
    ->name('superadmin.exit-impersonate');

// Auth routes (provided by Laravel Breeze/built-in)
require __DIR__ . '/auth.php';
