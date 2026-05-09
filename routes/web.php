<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirsOnboardingController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Route;

// ─── Marketing / Public site ─────────────────────────────────────────────────
Route::get('/',          [MarketingController::class, 'home'])->name('home');
Route::get('/features',  [MarketingController::class, 'features'])->name('marketing.features');
Route::get('/pricing',   [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/about',     [MarketingController::class, 'about'])->name('marketing.about');
Route::get('/contact',   [MarketingController::class, 'contact'])->name('marketing.contact');
Route::post('/contact',  [MarketingController::class, 'contactSubmit'])->name('marketing.contact.submit');

// ─── Paystack Webhook (no auth, CSRF excluded — see bootstrap/app.php) ───────
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack');

// ─── Team invite acceptance (no auth required) ───────────────────────────────
Route::prefix('invite')->name('invite.')->group(function () {
    Route::get('/{token}',  [InviteController::class, 'show'])->name('show');
    Route::post('/{token}', [InviteController::class, 'accept'])->name('accept');
});

// ─── Public invoice view (no auth required) ──────────────────────────────────
Route::prefix('inv')->name('invoice.public.')->group(function () {
    Route::get('/{token}',     [PublicInvoiceController::class, 'show'])->name('show');
    Route::get('/{token}/pdf', [PublicInvoiceController::class, 'downloadPdf'])->name('pdf');
});

// ─── Authenticated + Tenant-scoped routes ───────────────────────────────────
Route::middleware(['auth', 'verified', 'tenant', 'audit'])->group(function () {

    // Dashboard (all roles — DashboardController redirects staff to staff.dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profile (all roles) ───────────────────────────────────────────────────
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',         [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/',       [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // ── Staff portal (staff role) ─────────────────────────────────────────────
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    });

    // ── Admin-only ────────────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {

        // Billing
        Route::get('/billing',                   [BillingController::class, 'index'])->name('billing');
        Route::get('/billing/checkout/{plan}',   [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/callback',          [BillingController::class, 'callback'])->name('billing.callback');
        Route::post('/billing/downgrade/{plan}', [BillingController::class, 'downgrade'])->name('billing.downgrade');
        Route::post('/billing/cancel',           [BillingController::class, 'cancel'])->name('billing.cancel');

        // Company settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/company',        [CompanySettingsController::class, 'edit'])->name('company');
            Route::patch('/company',      [CompanySettingsController::class, 'update'])->name('company.update');
            Route::post('/company/logo',  [CompanySettingsController::class, 'uploadLogo'])->name('company.logo.upload');
            Route::delete('/company/logo',[CompanySettingsController::class, 'deleteLogo'])->name('company.logo.delete');

            Route::get('/firs',             [FirsOnboardingController::class, 'showForm'])->name('firs')->middleware('plan:firs');
            Route::post('/firs',            [FirsOnboardingController::class, 'store'])->name('firs.store')->middleware('plan:firs');
            Route::post('/firs/deactivate', [FirsOnboardingController::class, 'deactivate'])->name('firs.deactivate')->middleware('plan:firs');
        });

        // Team management
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/',                      [TeamController::class, 'index'])->name('index');
            Route::post('/invite',               [TeamController::class, 'invite'])->name('invite');
            Route::patch('/{user}/role',         [TeamController::class, 'updateRole'])->name('role');
            Route::post('/{user}/toggle',        [TeamController::class, 'toggleActive'])->name('toggle');
            Route::delete('/{user}',             [TeamController::class, 'destroy'])->name('destroy');
            Route::delete('/invites/{invite}',   [TeamController::class, 'cancelInvite'])->name('invite.cancel');
        });
    });

    // ── Admin + Accountant ────────────────────────────────────────────────────
    Route::middleware('role:admin,accountant')->group(function () {

        // Customers & Vendors quick-create
        Route::post('/customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
        Route::post('/vendors/quick',   [VendorController::class,  'quickStore'])->name('vendors.quick-store');

        // Quotes / Proforma Invoices
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/',                [QuoteController::class, 'index'])->name('index');
            Route::get('/create',          [QuoteController::class, 'create'])->name('create');
            Route::post('/preview',        [QuoteController::class, 'preview'])->name('preview');
            Route::post('/',               [QuoteController::class, 'store'])->name('store');
            Route::get('/{quote}',         [QuoteController::class, 'show'])->name('show');
            Route::get('/{quote}/edit',    [QuoteController::class, 'edit'])->name('edit');
            Route::put('/{quote}',         [QuoteController::class, 'update'])->name('update');
            Route::delete('/{quote}',      [QuoteController::class, 'destroy'])->name('destroy');
            Route::post('/{quote}/send',   [QuoteController::class, 'send'])->name('send');
            Route::post('/{quote}/accept', [QuoteController::class, 'accept'])->name('accept');
            Route::post('/{quote}/decline',[QuoteController::class, 'decline'])->name('decline');
            Route::get('/{quote}/pdf',     [QuoteController::class, 'downloadPdf'])->name('pdf');
        });

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'index'])->name('index');
            Route::get('/create',         [InvoiceController::class, 'create'])->name('create');
            Route::post('/preview',       [InvoiceController::class, 'preview'])->name('preview');
            Route::post('/',              [InvoiceController::class, 'store'])->name('store');
            Route::get('/import',         [InvoiceController::class, 'importForm'])->name('import');
            Route::post('/import',        [InvoiceController::class, 'import'])->name('import.process');
            Route::get('/sample',         [InvoiceController::class, 'downloadSample'])->name('sample');
            Route::get('/{invoice}',      [InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}',      [InvoiceController::class, 'update'])->name('update');
            Route::delete('/{invoice}',   [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/{invoice}/payment',    [InvoiceController::class, 'recordPayment'])->name('payment');
            Route::get('/{invoice}/pdf',         [InvoiceController::class, 'downloadPdf'])->name('pdf');
            Route::post('/{invoice}/send',       [InvoiceController::class, 'sendEmail'])->name('send');
            Route::post('/{invoice}/void',       [InvoiceController::class, 'void'])->name('void');
            Route::post('/{invoice}/submit-firs',[InvoiceController::class, 'submitToFirs'])->name('submit-firs')->middleware('plan:firs');
        });

        // Transactions & Expenses
        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/',             [TransactionController::class, 'index'])->name('index');
            Route::get('/export/excel', [TransactionController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf',   [TransactionController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/create',       [TransactionController::class, 'create'])->name('create');
            Route::post('/',            [TransactionController::class, 'store'])->name('store');
            Route::get('/expenses',                    [TransactionController::class, 'expenses'])->name('expenses');
            Route::post('/expenses',                   [TransactionController::class, 'storeExpense'])->name('expenses.store');
            Route::get('/expenses/{expense}/edit',     [TransactionController::class, 'editExpense'])->name('expenses.edit');
            Route::put('/expenses/{expense}',          [TransactionController::class, 'updateExpense'])->name('expenses.update');
            Route::delete('/expenses/{expense}',       [TransactionController::class, 'destroyExpense'])->name('expenses.destroy');
            Route::post('/expenses/{expense}/approve', [TransactionController::class, 'approveExpense'])->name('expenses.approve');
            Route::post('/expenses/{expense}/reject',  [TransactionController::class, 'rejectExpense'])->name('expenses.reject');
            Route::post('/expenses/{expense}/pay',     [TransactionController::class, 'payExpense'])->name('expenses.pay');
            Route::get('/{id}',         [TransactionController::class, 'show'])->name('show')->whereNumber('id');
        });

        // Tax Compliance
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/', [TaxController::class, 'dashboard'])->name('dashboard');
            Route::prefix('vat')->name('vat.')->group(function () {
                Route::get('/',                    [TaxController::class, 'vatIndex'])->name('index');
                Route::get('/compute',             [TaxController::class, 'vatCompute'])->name('compute');
                Route::post('/{return}/filed',     [TaxController::class, 'vatFiled'])->name('filed');
                Route::post('/{return}/paid',      [TaxController::class, 'vatPaid'])->name('paid');
            });
            Route::prefix('wht')->name('wht.')->group(function () {
                Route::get('/',                    [TaxController::class, 'whtIndex'])->name('index');
                Route::post('/{whtRecord}/remit',  [TaxController::class, 'whtRemit'])->name('remit');
            });
            Route::prefix('cit')->name('cit.')->group(function () {
                Route::get('/',                    [TaxController::class, 'citIndex'])->name('index');
                Route::get('/compute',             [TaxController::class, 'citCompute'])->name('compute');
                Route::post('/{record}/filed',     [TaxController::class, 'citFiled'])->name('filed');
            });
        });

        // Payroll (also requires plan:payroll)
        Route::prefix('payroll')->name('payroll.')->middleware('plan:payroll')->group(function () {
            Route::get('/',               [PayrollController::class, 'index'])->name('index');
            Route::get('/create',         [PayrollController::class, 'create'])->name('create');
            Route::post('/',              [PayrollController::class, 'store'])->name('store');
            Route::get('/employees',                 [PayrollController::class, 'employees'])->name('employees');
            Route::get('/employees/import',          [PayrollController::class, 'importEmployeesForm'])->name('employees.import');
            Route::post('/employees/import',         [PayrollController::class, 'importEmployees'])->name('employees.import.process');
            Route::get('/employees/sample',          [PayrollController::class, 'downloadEmployeeSample'])->name('employees.sample');
            Route::get('/employees/create',          [PayrollController::class, 'createEmployee'])->name('employees.create');
            Route::post('/employees',                [PayrollController::class, 'storeEmployee'])->name('employees.store');
            Route::get('/employees/{employee}/edit', [PayrollController::class, 'editEmployee'])->name('employees.edit');
            Route::put('/employees/{employee}',      [PayrollController::class, 'updateEmployee'])->name('employees.update');
            Route::get('/item/{item}/payslip',       [PayrollController::class, 'payslip'])->name('payslip');
            Route::get('/{payroll}',                 [PayrollController::class, 'show'])->name('show')->whereNumber('payroll');
            Route::post('/{payroll}/approve',        [PayrollController::class, 'approve'])->name('approve')->whereNumber('payroll');
            Route::post('/{payroll}/recompute',      [PayrollController::class, 'recompute'])->name('recompute')->whereNumber('payroll');
            Route::get('/{payroll}/export/pdf',      [PayrollController::class, 'downloadPdf'])->name('export.pdf')->whereNumber('payroll');
            Route::get('/{payroll}/export/excel',    [PayrollController::class, 'downloadExcel'])->name('export.excel')->whereNumber('payroll');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',                   [ReportController::class, 'index'])->name('index');
            Route::get('/profit-loss',        [ReportController::class, 'profitAndLoss'])->name('pl');
            Route::get('/profit-loss/pdf',    [ReportController::class, 'profitAndLossPdf'])->name('pl.pdf');
            Route::get('/profit-loss/excel',  [ReportController::class, 'profitAndLossExcel'])->name('pl.excel');
            Route::get('/balance-sheet',      [ReportController::class, 'balanceSheet'])->name('bs');
            Route::get('/balance-sheet/pdf',  [ReportController::class, 'balanceSheetPdf'])->name('bs.pdf');
            Route::get('/balance-sheet/excel',[ReportController::class, 'balanceSheetExcel'])->name('bs.excel');
            Route::get('/trial-balance',      [ReportController::class, 'trialBalance'])->name('tb');
            Route::get('/ledger',             [ReportController::class, 'ledger'])->name('ledger');
            Route::get('/ledger/pdf',         [ReportController::class, 'ledgerPdf'])->name('ledger.pdf');
            Route::get('/ledger/excel',       [ReportController::class, 'ledgerExcel'])->name('ledger.excel');
            Route::get('/vat',                [ReportController::class, 'vatReport'])->name('vat');
            Route::get('/cit',                [ReportController::class, 'citReport'])->name('cit');
            Route::get('/tax-summary',        [ReportController::class, 'taxSummary'])->name('tax-summary');
        });
    });

});

// ─── Super Admin (platform owner) routes ────────────────────────────────────
Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {

        Route::get('/',           [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/companies',  [SuperAdminController::class, 'companies'])->name('companies');

        // Subscription transactions
        Route::get('/transactions',              [SuperAdminController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/export/excel', [SuperAdminController::class, 'transactionsExportExcel'])->name('transactions.export.excel');
        Route::get('/transactions/export/pdf',   [SuperAdminController::class, 'transactionsExportPdf'])->name('transactions.export.pdf');

        // Plans CRUD
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/',            [PlanController::class, 'index'])->name('index');
            Route::get('/create',      [PlanController::class, 'create'])->name('create');
            Route::post('/',           [PlanController::class, 'store'])->name('store');
            Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
            Route::put('/{plan}',      [PlanController::class, 'update'])->name('update');
            Route::delete('/{plan}',   [PlanController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('companies/{tenant}')->name('companies.')->group(function () {
            Route::get('/',                   [SuperAdminController::class, 'showCompany'])->name('show');
            Route::post('/toggle',            [SuperAdminController::class, 'toggleActive'])->name('toggle');
            Route::patch('/subscription',     [SuperAdminController::class, 'updateSubscription'])->name('subscription');
            Route::post('/extend-trial',      [SuperAdminController::class, 'extendTrial'])->name('extend-trial');
            Route::post('/remind',            [SuperAdminController::class, 'sendReminder'])->name('remind');
            Route::post('/impersonate',       [SuperAdminController::class, 'impersonate'])->name('impersonate');
        });

        Route::post('/bulk-reminder',        [SuperAdminController::class, 'sendBulkReminder'])->name('bulk-reminder');
    });

// Exit impersonation (accessible while impersonating, outside superadmin middleware)
Route::middleware(['auth'])
    ->post('/superadmin/exit-impersonate', [SuperAdminController::class, 'exitImpersonate'])
    ->name('superadmin.exit-impersonate');

// Auth routes (provided by Laravel Breeze/built-in)
require __DIR__ . '/auth.php';
