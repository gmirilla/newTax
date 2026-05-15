<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\SubscriptionTransactionExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $planBreakdown = Plan::withCount(['tenants' => fn($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($p) => [$p->slug => $p->tenants_count]);

        $stats = [
            'total_companies'   => Tenant::withTrashed()->count(),
            'active_companies'  => Tenant::where('is_active', true)->count(),
            'inactive_companies'=> Tenant::where('is_active', false)->count(),
            'trialing'          => Tenant::where('subscription_status', 'trialing')->count(),
            'plan_breakdown'    => $planBreakdown,
            'expiring_soon'     => Tenant::where('is_active', true)
                                    ->whereNotNull('subscription_expires_at')
                                    ->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])
                                    ->count(),
            'in_grace'          => Tenant::where('is_active', true)
                                    ->whereIn('subscription_status', ['active', 'cancelled', 'suspended'])
                                    ->whereNotNull('subscription_expires_at')
                                    ->where('subscription_expires_at', '<', now())
                                    ->where('subscription_expires_at', '>=', now()->subDays(7))
                                    ->count(),
            'expired'           => Tenant::where('is_active', true)
                                    ->whereNotNull('subscription_expires_at')
                                    ->where('subscription_expires_at', '<', now()->subDays(7))
                                    ->count(),
            'total_users'       => User::where('is_superadmin', false)->count(),
            'new_this_month'    => Tenant::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count(),
        ];

        $recentCompanies = Tenant::withTrashed()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $expiringSoon = Tenant::where('is_active', true)
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<=', now()->addDays(14))
            ->orderBy('subscription_expires_at')
            ->get();

        return view('superadmin.dashboard', compact('stats', 'recentCompanies', 'expiringSoon'));
    }

    public function companies(Request $request): View
    {
        $query = Tenant::withTrashed()
            ->withCount('users')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%')
                  ->orWhere('tin', 'ilike', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            match($request->status) {
                'active'   => $query->where('is_active', true)->whereNull('deleted_at'),
                'inactive' => $query->where('is_active', false)->whereNull('deleted_at'),
                'deleted'  => $query->onlyTrashed(),
                default    => null,
            };
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        if ($request->filled('expiry')) {
            match($request->expiry) {
                'expired'  => $query->where('subscription_expires_at', '<', now()),
                'expiring' => $query->whereBetween('subscription_expires_at', [now(), now()->addDays(14)]),
                default    => null,
            };
        }

        $companies = $query->paginate(20)->withQueryString();

        return view('superadmin.companies.index', compact('companies'));
    }

    public function showCompany(Tenant $tenant): View
    {
        $tenant->load(['users' => fn($q) => $q->orderBy('name'), 'plan']);
        $tenant->loadCount(['users', 'invoices']);

        $activityLog = \App\Models\AuditLog::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->orderBy('price_monthly')->get();

        return view('superadmin.companies.show', compact('tenant', 'activityLog', 'plans'));
    }

    public function transactions(Request $request): View
    {
        $query = $this->buildTransactionQuery($request);

        $payments = $query->with(['tenant', 'plan'])
            ->orderByDesc('paid_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total_revenue'     => SubscriptionPayment::where('status', 'success')->sum('amount'),
            'revenue_this_month'=> SubscriptionPayment::where('status', 'success')
                                    ->whereMonth('paid_at', now()->month)
                                    ->whereYear('paid_at',  now()->year)
                                    ->sum('amount'),
            'total_count'       => SubscriptionPayment::count(),
            'success_count'     => SubscriptionPayment::where('status', 'success')->count(),
            'failed_count'      => SubscriptionPayment::where('status', 'failed')->count(),
        ];

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('superadmin.transactions.index', compact('payments', 'stats', 'plans'));
    }

    public function transactionsExportExcel(Request $request)
    {
        $payments = $this->buildTransactionQuery($request)
            ->with(['tenant', 'plan'])
            ->orderByDesc('paid_at')
            ->get();

        $filters  = $request->only(['search', 'plan', 'status', 'cycle', 'date_from', 'date_to']);
        $filename = 'Subscription_Transactions_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new SubscriptionTransactionExport($payments, $filters), $filename);
    }

    public function transactionsExportPdf(Request $request)
    {
        $payments = $this->buildTransactionQuery($request)
            ->with(['tenant', 'plan'])
            ->orderByDesc('paid_at')
            ->get();

        $filters  = $request->only(['search', 'plan', 'status', 'cycle', 'date_from', 'date_to']);
        $filename = 'Subscription_Transactions_' . now()->format('Ymd') . '.pdf';

        return Pdf::loadView('superadmin.transactions.export-pdf', compact('payments', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with(['tenant', 'user'])
            ->orderByDesc('created_at');

        if ($request->filled('tenant')) {
            $query->whereHas('tenant', fn($q) =>
                $q->where('name', 'ilike', '%' . $request->tenant . '%')
                  ->orWhere('email', 'ilike', '%' . $request->tenant . '%')
            );
        }

        if ($request->filled('event')) {
            $query->where('event', 'like', $request->event . '%');
        }

        if ($request->filled('tags')) {
            $query->where('tags', 'like', '%' . $request->tags . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('scope')) {
            match($request->scope) {
                'superadmin' => $query->where('tags', 'like', '%superadmin%'),
                'tenant'     => $query->where('tags', 'not like', '%superadmin%'),
                default      => null,
            };
        }

        $logs = $query->paginate(50)->withQueryString();

        $eventTypes = AuditLog::selectRaw('event, count(*) as cnt')
            ->groupBy('event')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'event');

        $stats = [
            'today'      => AuditLog::whereDate('created_at', today())->count(),
            'superadmin' => AuditLog::where('tags', 'like', '%superadmin%')->whereDate('created_at', today())->count(),
            'total'      => AuditLog::count(),
        ];

        return view('superadmin.audit-logs.index', compact('logs', 'eventTypes', 'stats'));
    }

    private function superaudit(string $event, Tenant $tenant, array $old = [], array $new = []): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => Tenant::class,
                'auditable_id'   => $tenant->id,
                'old_values'     => $old,
                'new_values'     => $new,
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'url'            => request()->fullUrl(),
                'tags'           => 'superadmin',
            ]);
        } catch (\Throwable) {
            // Never fail a request due to audit logging
        }
    }

    private function buildTransactionQuery(Request $request)
    {
        $query = SubscriptionPayment::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('tenant', fn($q) =>
                $q->where('name', 'ilike', "%{$term}%")
                  ->orWhere('email', 'ilike', "%{$term}%")
            );
        }

        if ($request->filled('plan')) {
            $query->where('plan_id', $request->plan);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cycle')) {
            $query->where('billing_cycle', $request->cycle);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        return $query;
    }

    public function toggleActive(Tenant $tenant): RedirectResponse
    {
        $oldStatus = $tenant->is_active;
        $tenant->update(['is_active' => !$tenant->is_active]);
        $action = $tenant->is_active ? 'activated' : 'deactivated';

        $this->superaudit('superadmin.company_' . $action, $tenant, ['is_active' => $oldStatus], ['is_active' => $tenant->is_active]);

        return back()->with('success', "Company \"{$tenant->name}\" has been {$action}.");
    }

    public function updateSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'plan_id'                 => 'required|exists:plans,id',
            'subscription_expires_at' => 'nullable|date|after:today',
            'subscription_status'     => 'required|in:active,trialing,suspended,cancelled,grace',
            'trial_ends_at'           => 'nullable|date',
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        $old = $tenant->only(['plan_id', 'subscription_plan', 'subscription_status', 'subscription_expires_at', 'trial_ends_at']);

        $tenant->update([
            'plan_id'                 => $plan->id,
            'subscription_plan'       => $plan->slug,
            'subscription_status'     => $data['subscription_status'],
            'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
            'trial_ends_at'           => $data['trial_ends_at'] ?? null,
        ]);

        $this->superaudit('superadmin.subscription_updated', $tenant, $old, [
            'plan'   => $plan->slug,
            'status' => $data['subscription_status'],
            'expires' => $data['subscription_expires_at'] ?? null,
        ]);

        return back()->with('success', "Subscription updated to {$plan->name}.");
    }

    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $days = (int) $request->input('days', 14);
        $days = max(1, min(90, $days)); // clamp 1–90

        $base = ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
            ? $tenant->trial_ends_at
            : now();

        $oldTrialEnd = $tenant->trial_ends_at;

        $tenant->update([
            'trial_ends_at'       => $base->copy()->addDays($days),
            'subscription_status' => 'trialing',
        ]);

        $this->superaudit('superadmin.trial_extended', $tenant,
            ['trial_ends_at' => $oldTrialEnd?->toDateString()],
            ['trial_ends_at' => $tenant->trial_ends_at->toDateString(), 'days_added' => $days]
        );

        return back()->with('success', "Trial extended by {$days} days for {$tenant->name}.");
    }

    public function sendReminder(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $customMessage = $request->input('message');

        // Send email to tenant's primary email
        try {
            Mail::send('superadmin.emails.subscription-reminder', [
                'tenant'        => $tenant,
                'customMessage' => $customMessage,
            ], function ($mail) use ($tenant) {
                $mail->to($tenant->email)
                     ->subject("Subscription Reminder — {$tenant->name} | AccountTaxNG");
            });

            $tenant->update(['reminder_sent_at' => now()]);

            return back()->with('success', "Subscription reminder sent to {$tenant->email}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send reminder: ' . $e->getMessage());
        }
    }

    public function sendBulkReminder(Request $request): RedirectResponse
    {
        $tenants = Tenant::where('is_active', true)
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<=', now()->addDays(14))
            ->get();

        $sent = 0;
        foreach ($tenants as $tenant) {
            try {
                Mail::send('superadmin.emails.subscription-reminder', [
                    'tenant'        => $tenant,
                    'customMessage' => null,
                ], function ($mail) use ($tenant) {
                    $mail->to($tenant->email)
                         ->subject("Your subscription expires soon — AccountTaxNG");
                });
                $tenant->update(['reminder_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                // log and continue
                logger()->error("Reminder failed for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Sent reminders to {$sent} company/companies.");
    }

    public function impersonate(Tenant $tenant): RedirectResponse
    {
        $superAdminId = auth()->id();

        $adminUser = $tenant->users()->where('role', 'admin')->first();
        if (!$adminUser) {
            return back()->with('error', 'No admin user found for this company.');
        }

        $this->superaudit('superadmin.impersonation_started', $tenant, [], [
            'impersonated_user_id'    => $adminUser->id,
            'impersonated_user_email' => $adminUser->email,
        ]);

        session(['superadmin_id' => $superAdminId]);
        auth()->login($adminUser);

        return redirect()->route('dashboard')
            ->with('info', "You are now viewing as {$tenant->name}. Use 'Exit Impersonation' to return.");
    }

    public function exitImpersonate(): RedirectResponse
    {
        $superAdminId = session()->pull('superadmin_id');
        if (!$superAdminId) {
            return redirect()->route('dashboard');
        }

        // Log exit before switching auth back
        $currentTenant = auth()->user()?->tenant;
        if ($currentTenant) {
            AuditLog::create([
                'tenant_id'      => $currentTenant->id,
                'user_id'        => $superAdminId,
                'event'          => 'superadmin.impersonation_ended',
                'auditable_type' => Tenant::class,
                'auditable_id'   => $currentTenant->id,
                'old_values'     => [],
                'new_values'     => ['tenant' => $currentTenant->name],
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'url'            => request()->fullUrl(),
                'tags'           => 'superadmin,impersonation',
            ]);
        }

        auth()->loginUsingId($superAdminId);

        return redirect()->route('superadmin.dashboard')
            ->with('success', 'Returned to superadmin view.');
    }
}
