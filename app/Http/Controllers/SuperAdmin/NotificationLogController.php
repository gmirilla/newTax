<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NotificationLog;
use App\Support\NotificationPayloadSerializer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = NotificationLog::with('notifiable')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('notification_class'), fn ($q) => $q->where('notification_class', $request->input('notification_class')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $notificationClasses = NotificationLog::query()
            ->select('notification_class')
            ->distinct()
            ->orderBy('notification_class')
            ->pluck('notification_class');

        return view('superadmin.notification-logs.index', compact('logs', 'notificationClasses'));
    }

    public function resend(NotificationLog $notificationLog): RedirectResponse
    {
        $notifiable = $notificationLog->notifiable;

        if (!$notifiable) {
            return back()->with('error', 'Cannot resend: the original recipient no longer exists.');
        }

        try {
            $notification = NotificationPayloadSerializer::reconstruct(
                $notificationLog->notification_class,
                $notificationLog->payload ?? []
            );
        } catch (ModelNotFoundException) {
            return back()->with('error', 'Cannot resend: a record this notification referenced no longer exists.');
        }

        $notifiable->notify($notification);

        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.notification_resent',
            'auditable_type' => NotificationLog::class,
            'auditable_id'   => $notificationLog->id,
            'old_values'     => [],
            'new_values'     => ['notification_class' => $notificationLog->notification_class],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,notification',
        ]);

        return back()->with('success', 'Notification resent.');
    }
}
