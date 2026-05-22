<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSystemNotificationEmail;
use App\Models\Plan;
use App\Models\SystemNotification;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = SystemNotification::with('createdBy')
            ->withCount('reads')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('superadmin.notifications.index', compact('notifications'));
    }

    public function create(): View
    {
        $plans          = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $tenants        = Tenant::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $preTargetType  = request('target_type', 'all');
        $preTenantId    = (int) request('tenant_id', 0);

        return view('superadmin.notifications.create', compact('plans', 'tenants', 'preTargetType', 'preTenantId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:150',
            'message'     => 'required|string|max:5000',
            'type'        => 'required|in:info,warning,critical,success',
            'target_type' => 'required|in:all,plan,specific',
            'target_ids'  => 'nullable|array',
            'target_ids.*'=> 'integer',
            'expires_at'  => 'nullable|date|after:now',
            'status'      => 'required|in:draft,sent',
        ]);

        if ($validated['target_type'] === SystemNotification::TARGET_ALL) {
            $validated['target_ids'] = null;
        }

        $notification = SystemNotification::create([
            ...$validated,
            'send_at'    => $validated['status'] === SystemNotification::STATUS_SENT ? now() : null,
            'created_by' => auth()->id(),
        ]);

        if ($notification->status === SystemNotification::STATUS_SENT
            && $notification->type === SystemNotification::TYPE_CRITICAL) {
            SendSystemNotificationEmail::dispatch($notification);
        }

        $label = $notification->status === SystemNotification::STATUS_SENT ? 'sent' : 'saved as draft';

        return redirect()->route('superadmin.notifications.index')
            ->with('success', "Notification \"{$notification->title}\" {$label}.");
    }

    public function show(SystemNotification $notification): View
    {
        $notification->loadMissing('createdBy');
        $notification->loadCount('reads');

        $recentReads = $notification->reads()
            ->with('user.tenant')
            ->latest('read_at')
            ->limit(20)
            ->get();

        return view('superadmin.notifications.show', compact('notification', 'recentReads'));
    }

    public function destroy(SystemNotification $notification): RedirectResponse
    {
        $notification->delete();

        return redirect()->route('superadmin.notifications.index')
            ->with('success', 'Notification deleted.');
    }
}
