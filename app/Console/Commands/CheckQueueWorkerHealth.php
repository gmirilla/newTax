<?php

namespace App\Console\Commands;

use App\Mail\QueueWorkerStatusAlert;
use App\Support\QueueHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckQueueWorkerHealth extends Command
{
    protected $signature = 'queue:check-worker-health';
    protected $description = 'Alert by email if the queue worker appears down (or has recovered)';

    private const ALERT_SENT_CACHE_KEY = 'queue.worker.down_alert_sent';

    public function handle(): int
    {
        $isDown      = QueueHealth::isPresumedDown();
        $alertAlreadySent = Cache::get(self::ALERT_SENT_CACHE_KEY, false);

        if ($isDown && !$alertAlreadySent) {
            $this->sendAlert(isDown: true);
            Cache::put(self::ALERT_SENT_CACHE_KEY, true, now()->addDay());
            $this->warn('Queue worker appears down — alert sent.');
        } elseif (!$isDown && $alertAlreadySent) {
            $this->sendAlert(isDown: false);
            Cache::forget(self::ALERT_SENT_CACHE_KEY);
            $this->info('Queue worker has recovered — alert sent.');
        } else {
            $this->info($isDown ? 'Queue worker still down — already alerted.' : 'Queue worker healthy.');
        }

        return self::SUCCESS;
    }

    private function sendAlert(bool $isDown): void
    {
        try {
            Mail::to($this->recipient())->send(
                new QueueWorkerStatusAlert($isDown, QueueHealth::heartbeatAt())
            );
        } catch (\Throwable $e) {
            // A mail failure must never make the health check itself fail.
            Log::error('Failed to send queue worker health alert: ' . $e->getMessage());
        }
    }

    private function recipient(): string
    {
        return env('QUEUE_ALERT_EMAIL', config('mail.from.address'));
    }
}
