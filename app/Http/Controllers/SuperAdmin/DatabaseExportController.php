<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportDatabaseJob;
use App\Models\AuditLog;
use App\Services\DatabaseExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseExportController extends Controller
{
    public function __construct(private readonly DatabaseExportService $service) {}

    public function index(): View
    {
        $tables = $this->service->getExportableTables();

        // Row counts are cached briefly — loading them on every page view would
        // be slow on a large database.
        $rowCounts = Cache::remember('superadmin_export_row_counts', 300, function () use ($tables) {
            return $this->service->getTableRowCounts($tables);
        });

        $storageBytes = $this->service->getStorageFileSize();

        return view('superadmin.database.export', compact('tables', 'rowCounts', 'storageBytes'));
    }

    public function export(): StreamedResponse|RedirectResponse
    {
        $filename = 'naijabooks-export-' . now()->format('Y-m-d-His') . '.zip';
        $zipPath  = $this->service->getExportDirectory() . DIRECTORY_SEPARATOR . $filename;

        // With QUEUE_CONNECTION=sync this runs immediately in the same request.
        // When queues go async, the zip will be written in a background worker
        // and this request will need to poll for completion (future work).
        ExportDatabaseJob::dispatch($zipPath);

        if (!file_exists($zipPath)) {
            return redirect()->route('superadmin.database.export')
                ->with('error', 'Export failed to generate. Check the application logs.');
        }

        $fileSize = filesize($zipPath);

        AuditLog::record(
            'superadmin.database_exported',
            auth()->user(),
            [],
            ['filename' => $filename, 'size_bytes' => $fileSize],
            'superadmin'
        );

        return response()->streamDownload(function () use ($zipPath) {
            $handle = fopen($zipPath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            @unlink($zipPath);
        }, $filename, [
            'Content-Type'   => 'application/zip',
            'Content-Length' => $fileSize,
        ]);
    }
}
