<?php

use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirsOnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuoteController;
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

    // ── Quotes / Proforma Invoices ────────────────────────────────────────────
    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/',               [QuoteController::class, 'index'])->name('index');
        Route::get('/create',         [QuoteController::class, 'create'])->name('create');
        Route::post('/',              [QuoteController::class, 'store'])->name('store');
        Route::get('/{quote}',        [QuoteController::class, 'show'])->name('show');
        Route::get('/{quote}/edit',   [QuoteController::class, 'edit'])->name('edit');
        Route::put('/{quote}',        [QuoteController::class, 'update'])->name('update');
        Route::delete('/{quote}',     [QuoteController::class, 'destroy'])->name('destroy');
        Route::post('/{quote}/send',  [QuoteController::class, 'send'])->name('send');
        Route::post('/{quote}/accept',[QuoteController::class, 'accept'])->name('accept');
        Route::post('/{quote}/decline',[QuoteController::class, 'decline'])->name('decline');
        Route::get('/{quote}/pdf',    [QuoteController::class, 'downloadPdf'])->name('pdf');
    });

    // ── Invoices ──────────────────────────────────────────────────────────────
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',               [InvoiceController::class, 'index'])->name('index');
        Route::get('/create',         [InvoiceController::class, 'create'])->name('create');
        Route::post('/',              [InvoiceController::class, 'store'])->name('store');
        Route::get('/import',         [InvoiceController::class, 'importForm'])->name('import');
        Route::post('/import',        [InvoiceController::class, 'import'])->name('import.process');
        Route::get('/sample',         [InvoiceController::class, 'downloadSample'])->name('sample');
        Route::get('/{invoice}',      [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}',      [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}',   [InvoiceController::class, 'destroy'])->name('destroy');
        // Payment & actions
        Route::post('/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('payment');
        Route::get('/{invoice}/pdf',      [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/{invoice}/send',    [InvoiceController::class, 'sendEmail'])->name('send');
        Route::post('/{invoice}/void',       [InvoiceController::class, 'void'])->name('void');
        Route::post('/{invoice}/submit-firs', [InvoiceController::class, 'submitToFirs'])->name('submit-firs');
    });

    // ── Profile ───────────────────────────────────────────────────────────────
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',    [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/',  [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // ── Settings (admin-only sections gated inside controllers) ───────────────
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/company',        [CompanySettingsController::class, 'edit'])->name('company');
        Route::patch('/company',      [CompanySettingsController::class, 'update'])->name('company.update');
        Route::post('/company/logo',  [CompanySettingsController::class, 'uploadLogo'])->name('company.logo.upload');
        Route::delete('/company/logo',[CompanySettingsController::class, 'deleteLogo'])->name('company.logo.delete');

        Route::get('/firs',              [FirsOnboardingController::class, 'showForm'])->name('firs');
        Route::post('/firs',             [FirsOnboardingController::class, 'store'])->name('firs.store');
        Route::post('/firs/deactivate',  [FirsOnboardingController::class, 'deactivate'])->name('firs.deactivate');
    });

    // ── Transactions & Expenses ───────────────────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',              [TransactionController::class, 'index'])->name('index');
        Route::get('/export/excel',  [TransactionController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf',    [TransactionController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/create',        [TransactionController::class, 'create'])->name('create');
        Route::post('/',             [TransactionController::class, 'store'])->name('store');
        // Expenses — must be declared before /{id} to prevent "expenses" being treated as an ID
        Route::get('/expenses',                        [TransactionController::class, 'expenses'])->name('expenses');
        Route::post('/expenses',                       [TransactionController::class, 'storeExpense'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit',         [TransactionController::class, 'editExpense'])->name('expenses.edit');
        Route::put('/expenses/{expense}',              [TransactionController::class, 'updateExpense'])->name('expenses.update');
        Route::delete('/expenses/{expense}',           [TransactionController::class, 'destroyExpense'])->name('expenses.destroy');
        Route::post('/expenses/{expense}/approve',     [TransactionController::class, 'approveExpense'])->name('expenses.approve');
        Route::post('/expenses/{expense}/reject',      [TransactionController::class, 'rejectExpense'])->name('expenses.reject');
        Route::post('/expenses/{expense}/pay',         [TransactionController::class, 'payExpense'])->name('expenses.pay');
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
        Route::get('/employees/import',             [PayrollController::class, 'importEmployeesForm'])->name('employees.import');
        Route::post('/employees/import',            [PayrollController::class, 'importEmployees'])->name('employees.import.process');
        Route::get('/employees/sample',             [PayrollController::class, 'downloadEmployeeSample'])->name('employees.sample');
        Route::get('/employees/create',             [PayrollController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees',                   [PayrollController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{employee}/edit',    [PayrollController::class, 'editEmployee'])->name('employees.edit');
        Route::put('/employees/{employee}',         [PayrollController::class, 'updateEmployee'])->name('employees.update');

        // Parameterized routes last
        Route::get('/item/{item}/payslip',    [PayrollController::class, 'payslip'])->name('payslip');
        Route::get('/{payroll}',              [PayrollController::class, 'show'])->name('show')->whereNumber('payroll');
        Route::post('/{payroll}/approve',     [PayrollController::class, 'approve'])->name('approve')->whereNumber('payroll');
        Route::post('/{payroll}/recompute',   [PayrollController::class, 'recompute'])->name('recompute')->whereNumber('payroll');
        Route::get('/{payroll}/export/pdf',   [PayrollController::class, 'downloadPdf'])->name('export.pdf')->whereNumber('payroll');
        Route::get('/{payroll}/export/excel', [PayrollController::class, 'downloadExcel'])->name('export.excel')->whereNumber('payroll');
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',              [ReportController::class, 'index'])->name('index');
        Route::get('/profit-loss',        [ReportController::class, 'profitAndLoss'])->name('pl');
        Route::get('/profit-loss/pdf',    [ReportController::class, 'profitAndLossPdf'])->name('pl.pdf');
        Route::get('/profit-loss/excel',  [ReportController::class, 'profitAndLossExcel'])->name('pl.excel');
        Route::get('/balance-sheet',       [ReportController::class, 'balanceSheet'])->name('bs');
        Route::get('/balance-sheet/pdf',   [ReportController::class, 'balanceSheetPdf'])->name('bs.pdf');
        Route::get('/balance-sheet/excel', [ReportController::class, 'balanceSheetExcel'])->name('bs.excel');
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('tb');
        Route::get('/ledger',             [ReportController::class, 'ledger'])->name('ledger');
        Route::get('/ledger/pdf',         [ReportController::class, 'ledgerPdf'])->name('ledger.pdf');
        Route::get('/ledger/excel',       [ReportController::class, 'ledgerExcel'])->name('ledger.excel');
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
