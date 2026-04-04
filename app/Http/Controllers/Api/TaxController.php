<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CitService;
use App\Services\ReportService;
use App\Services\VatService;
use App\Services\WhtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function __construct(
        private readonly VatService    $vatService,
        private readonly WhtService    $whtService,
        private readonly CitService    $citService,
        private readonly ReportService $reportService
    ) {}

    public function vatCompute(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $tenant    = $request->user()->tenant;
        $vatReturn = $this->vatService->createOrUpdateReturn(
            $tenant, $request->year, $request->month
        );

        return response()->json($vatReturn);
    }

    public function vatSummary(Request $request): JsonResponse
    {
        $tenant  = $request->user()->tenant;
        $summary = $this->vatService->getDashboardSummary($tenant);

        return response()->json($summary);
    }

    public function whtSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $tenant   = $request->user()->tenant;
        $schedule = $this->whtService->generateMonthlySchedule(
            $tenant, $request->year, $request->month
        );

        return response()->json($schedule);
    }

    public function citCompute(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $tenant = $request->user()->tenant;
        $record = $this->citService->createOrUpdateRecord($tenant, $request->year);

        return response()->json($record);
    }

    public function complianceDashboard(Request $request): JsonResponse
    {
        $tenant    = $request->user()->tenant;
        $dashboard = $this->reportService->getComplianceDashboard($tenant);

        // Exclude heavy objects for API response
        unset($dashboard['wht']['schedule']['records']);

        return response()->json($dashboard);
    }
}
