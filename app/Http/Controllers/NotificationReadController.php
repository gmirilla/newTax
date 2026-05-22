<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Models\SystemNotificationRead;
use Illuminate\Http\JsonResponse;

class NotificationReadController extends Controller
{
    public function store(SystemNotification $notification): JsonResponse
    {
        SystemNotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'user_id' => auth()->id()],
            ['read_at' => now()],
        );

        return response()->json(['ok' => true]);
    }
}
