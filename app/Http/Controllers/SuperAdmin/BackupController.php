<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class BackupController extends Controller
{
    public function index(): View
    {
        $statuses = $this->getBackupStatuses();

        return view('superadmin.backups.index', compact('statuses'));
    }

    public function runNow(): RedirectResponse
    {
        try {
            // Runs synchronously with QUEUE_CONNECTION=sync.
            // When queues go async, this will dispatch to the background automatically.
            Artisan::queue('backup:run --only-db');

            return redirect()->route('superadmin.backups.index')
                ->with('success', 'Backup started. Refresh in a moment to see the new entry.');
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Failed to start backup: ' . $e->getMessage());
        }
    }

    private function getBackupStatuses(): array
    {
        try {
            $config      = config('backup');
            $appName     = $config['backup']['name'];
            $diskNames   = $config['backup']['destination']['disks'];

            $destinations = BackupDestinationFactory::createFromArray($config['backup']);

            $statuses = BackupDestinationStatusFactory::createForDestinations($destinations);

            return $statuses->map(function (BackupDestinationStatus $status) {
                $destination = $status->backupDestination();
                $backups     = $destination->backups();

                $files = $backups->map(fn($b) => [
                    'name'          => basename($b->path()),
                    'date'          => $b->date()->toDateTimeString(),
                    'age_hours'     => round($b->date()->diffInHours(now())),
                    'size_bytes'    => $b->size(),
                    'size_human'    => $this->formatBytes($b->size()),
                ])->sortByDesc('date')->values()->toArray();

                return [
                    'disk'            => $destination->diskName(),
                    'healthy'         => $status->isHealthy(),
                    'health_message'  => $status->getHealthCheckFailureMessages()->implode(', '),
                    'backup_count'    => count($files),
                    'newest_backup'   => $files[0] ?? null,
                    'total_size'      => $this->formatBytes($destination->usedStorage()),
                    'files'           => $files,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            // Backup destination unreachable (e.g. SFTP offline) — degrade gracefully.
            return [[
                'disk'           => 'unknown',
                'healthy'        => false,
                'health_message' => $e->getMessage(),
                'backup_count'   => 0,
                'newest_backup'  => null,
                'total_size'     => '—',
                'files'          => [],
            ]];
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
