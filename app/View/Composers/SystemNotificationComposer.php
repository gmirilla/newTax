<?php

namespace App\View\Composers;

use App\Models\SystemNotification;
use Illuminate\View\View;

class SystemNotificationComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (!$user || !$user->tenant_id) {
            $view->with('systemNotifications', collect());
            $view->with('unreadNotificationCount', 0);
            return;
        }

        $notifications = SystemNotification::forUser(
            userId:   $user->id,
            tenantId: (int) $user->tenant_id,
            planId:   $user->tenant?->plan_id ? (int) $user->tenant->plan_id : null,
        );

        $view->with('systemNotifications', $notifications);
        $view->with('unreadNotificationCount', $notifications->count());
    }
}
