<?php

namespace App\Jobs;

use App\Mail\SystemNotificationMail;
use App\Models\SystemNotification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSystemNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 120;

    public function __construct(public readonly SystemNotification $notification) {}

    public function handle(): void
    {
        $tenantQuery = Tenant::where('is_active', true);

        if ($this->notification->target_type === SystemNotification::TARGET_PLAN) {
            $planIds = $this->notification->target_ids ?? [];
            $tenantQuery->whereIn('plan_id', $planIds);
        } elseif ($this->notification->target_type === SystemNotification::TARGET_SPECIFIC) {
            $tenantIds = $this->notification->target_ids ?? [];
            $tenantQuery->whereIn('id', $tenantIds);
        }

        $tenantQuery->each(function (Tenant $tenant) {
            if (empty($tenant->email)) return;

            Mail::to($tenant->email)
                ->send(new SystemNotificationMail($this->notification, $tenant));
        });
    }
}
