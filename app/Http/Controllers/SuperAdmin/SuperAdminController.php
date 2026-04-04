<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_companies'  => Tenant::withTrashed()->count(),
            'active_companies' => Tenant::where('is_active', true)->count(),
            'inactive_companies' => Tenant::where('is_active', false)->count(),
            'free_plan'        => Tenant::where('subscription_plan', 'free')->count(),
            'starter_plan'     => Tenant::where('subscription_plan', 'starter')->count(),
            'pro_plan'         => Tenant::where('subscription_plan', 'pro')->count(),
            'enterprise_plan'  => Tenant::where('subscription_plan', 'enterprise')->count(),
            'expiring_soon'    => Tenant::where('is_active', true)
                                    ->whereNotNull('subscription_expires_at')
                                    ->where('subscription_expires_at', '<=', now()->addDays(14))
                                    ->where('subscription_expires_at', '>=', now())
                                    ->count(),
            'expired'          => Tenant::where('is_active', true)
                                    ->whereNotNull('subscription_expires_at')
                                    ->where('subscription_expires_at', '<', now())
                                    ->count(),
            'total_users'      => User::where('is_superadmin', false)->count(),
            'new_this_month'   => Tenant::whereMonth('created_at', now()->month)
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
        $tenant->load(['users' => fn($q) => $q->orderBy('name')]);
        $tenant->loadCount(['users', 'invoices']);

        $activityLog = \App\Models\AuditLog::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('superadmin.companies.show', compact('tenant', 'activityLog'));
    }

    public function toggleActive(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);

        $action = $tenant->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Company \"{$tenant->name}\" has been {$action}.");
    }

    public function updateSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan'       => 'required|in:free,starter,pro,enterprise',
            'subscription_expires_at' => 'nullable|date|after:today',
            'subscription_status'     => 'required|in:active,suspended,cancelled',
        ]);

        $tenant->update($data);

        return back()->with('success', 'Subscription updated successfully.');
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
                     ->subject("Subscription Reminder — {$tenant->name} | NaijaBooks");
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
                         ->subject("Your subscription expires soon — NaijaBooks");
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
        // Store superadmin's ID so we can restore later
        session(['superadmin_id' => auth()->id()]);

        $adminUser = $tenant->users()->where('role', 'admin')->first();
        if (!$adminUser) {
            return back()->with('error', 'No admin user found for this company.');
        }

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

        auth()->loginUsingId($superAdminId);

        return redirect()->route('superadmin.dashboard')
            ->with('success', 'Returned to superadmin view.');
    }
}
