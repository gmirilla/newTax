<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Models\User;
use App\Support\NotificationPayloadSerializer;
use Illuminate\Notifications\Events\NotificationFailed;

class LogNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        try {
            NotificationLog::create([
                'tenant_id'           => $event->notifiable instanceof User ? $event->notifiable->tenant_id : null,
                'notifiable_type'     => get_class($event->notifiable),
                'notifiable_id'       => $event->notifiable->getKey(),
                'notification_class'  => get_class($event->notification),
                'channel'             => $event->channel,
                'status'              => NotificationLog::STATUS_FAILED,
                'error_message'       => $event->data['exception']?->getMessage(),
                'payload'             => NotificationPayloadSerializer::capture($event->notification),
            ]);
        } catch (\Throwable) {
            // Never let logging failure break the actual notification pipeline.
        }
    }
}
