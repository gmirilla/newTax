<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueMonitorController extends Controller
{
    public function index(): View
    {
        return view('superadmin.queue.index', ['status' => $this->getQueueStatus()]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        $id = DB::table('failed_jobs')->where('uuid', $uuid)->value('id');

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        $this->audit('superadmin.failed_job_retried', $id, ['uuid' => $uuid]);

        return redirect()->route('superadmin.queue.index')->with('success', "Job {$uuid} re-queued.");
    }

    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        $this->audit('superadmin.failed_jobs_retried_all', 0, []);

        return redirect()->route('superadmin.queue.index')->with('success', 'All failed jobs re-queued.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $id = DB::table('failed_jobs')->where('uuid', $uuid)->value('id');

        Artisan::call('queue:forget', ['id' => $uuid]);

        $this->audit('superadmin.failed_job_deleted', $id, ['uuid' => $uuid]);

        return redirect()->route('superadmin.queue.index')->with('success', "Failed job {$uuid} deleted.");
    }

    public function restartWorkers(): RedirectResponse
    {
        Artisan::call('queue:restart');

        $this->audit('superadmin.queue_restart_signalled', 0, []);

        return redirect()->route('superadmin.queue.index')->with(
            'success',
            'Restart signal sent. Running workers will finish their current job and exit — '
                . 'your process supervisor must bring them back up. This will NOT revive a worker that has already crashed.'
        );
    }

    private function getQueueStatus(): array
    {
        try {
            $heartbeat = Cache::get('queue.worker.heartbeat_at');
            // diffInMinutes() returns a signed difference (negative for past timestamps) — use the absolute value.
            $isStale   = !$heartbeat || now()->diffInMinutes($heartbeat, true) > 3;

            $oldestPending = DB::table('jobs')->min('created_at');

            return [
                'pending_count'              => DB::table('jobs')->count(),
                'failed_count'               => DB::table('failed_jobs')->count(),
                'failed_jobs'                => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get(),
                'oldest_pending_age_minutes'  => $oldestPending
                    ? now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($oldestPending), true)
                    : null,
                'worker_heartbeat_at'   => $heartbeat,
                'worker_presumed_down'  => $isStale,
            ];
        } catch (\Throwable $e) {
            return [
                'error'                      => $e->getMessage(),
                'pending_count'              => 0,
                'failed_count'               => 0,
                'failed_jobs'                => collect(),
                'oldest_pending_age_minutes' => null,
                'worker_heartbeat_at'        => null,
                'worker_presumed_down'       => true,
            ];
        }
    }

    private function audit(string $event, ?int $auditableId, array $newValues): void
    {
        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => $event,
            'auditable_type' => 'failed_job',
            'auditable_id'   => $auditableId ?? 0,
            'old_values'     => [],
            'new_values'     => $newValues,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,queue',
        ]);
    }
}
