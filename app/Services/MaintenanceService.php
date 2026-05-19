<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceBreakdown;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceLaborLog;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\MaintenanceWorkOrderPart;
use App\Models\StockMovement;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(private BookkeepingService $bookkeeping) {}

    // ── Work Order Number Generation ──────────────────────────────────────────

    public function nextWorkOrderNumber(Tenant $tenant): string
    {
        $prefix = 'MWO-' . now()->format('Ym') . '-';

        $last = MaintenanceWorkOrder::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('work_order_number', 'like', $prefix . '%')
            ->orderByDesc('work_order_number')
            ->value('work_order_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextBreakdownNumber(Tenant $tenant): string
    {
        $prefix = 'BRK-' . now()->format('Ym') . '-';

        $last = MaintenanceBreakdown::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('breakdown_number', 'like', $prefix . '%')
            ->orderByDesc('breakdown_number')
            ->value('breakdown_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextAssetCode(Tenant $tenant): string
    {
        $last = MaintenanceAsset::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('asset_code', 'like', 'AST-%')
            ->orderByDesc('asset_code')
            ->value('asset_code');

        $seq = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'AST-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Breakdown Reporting ───────────────────────────────────────────────────

    /**
     * Report a machine breakdown: creates breakdown record, marks asset as breakdown,
     * optionally creates a corrective work order.
     */
    public function reportBreakdown(
        Tenant $tenant,
        array $data,
        bool $createWorkOrder = true
    ): MaintenanceBreakdown {
        return DB::transaction(function () use ($tenant, $data, $createWorkOrder) {
            $breakdown = MaintenanceBreakdown::create([
                'tenant_id'         => $tenant->id,
                'breakdown_number'  => $this->nextBreakdownNumber($tenant),
                'asset_id'          => $data['asset_id'],
                'reported_by'       => $data['reported_by'],
                'issue_description' => $data['issue_description'],
                'severity'          => $data['severity'] ?? MaintenanceBreakdown::SEVERITY_MEDIUM,
                'downtime_start'    => $data['downtime_start'] ?? now(),
                'status'            => MaintenanceBreakdown::STATUS_OPEN,
            ]);

            // Mark asset unavailable
            MaintenanceAsset::withoutGlobalScope('tenant')
                ->where('id', $data['asset_id'])
                ->update(['status' => MaintenanceAsset::STATUS_BREAKDOWN]);

            // Auto-create corrective work order
            if ($createWorkOrder) {
                $wo = $this->createWorkOrder($tenant, [
                    'source_type'   => MaintenanceWorkOrder::SOURCE_CORRECTIVE,
                    'asset_id'      => $data['asset_id'],
                    'breakdown_id'  => $breakdown->id,
                    'title'         => 'Breakdown: ' . $data['issue_description'],
                    'description'   => $data['issue_description'],
                    'priority'      => $data['severity'] ?? MaintenanceWorkOrder::PRIORITY_HIGH,
                    'scheduled_date'=> now()->toDateString(),
                    'created_by'    => $data['reported_by'],
                ]);

                $breakdown->update(['work_order_id' => $wo->id]);
            }

            return $breakdown->refresh();
        });
    }

    // ── Work Order Lifecycle ──────────────────────────────────────────────────

    public function createWorkOrder(Tenant $tenant, array $data): MaintenanceWorkOrder
    {
        return MaintenanceWorkOrder::create([
            'tenant_id'        => $tenant->id,
            'work_order_number'=> $this->nextWorkOrderNumber($tenant),
            'source_type'      => $data['source_type']    ?? MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'         => $data['asset_id'],
            'schedule_id'      => $data['schedule_id']    ?? null,
            'breakdown_id'     => $data['breakdown_id']   ?? null,
            'title'            => $data['title'],
            'description'      => $data['description']    ?? null,
            'priority'         => $data['priority']       ?? MaintenanceWorkOrder::PRIORITY_MEDIUM,
            'assigned_to'      => $data['assigned_to']    ?? null,
            'scheduled_date'   => $data['scheduled_date'] ?? null,
            'estimated_hours'  => $data['estimated_hours']?? 0,
            'status'           => MaintenanceWorkOrder::STATUS_OPEN,
            'created_by'       => $data['created_by'],
        ]);
    }

    public function assignWorkOrder(MaintenanceWorkOrder $wo, int $userId): void
    {
        $wo->update([
            'assigned_to' => $userId,
            'status'      => MaintenanceWorkOrder::STATUS_ASSIGNED,
        ]);

        // Asset moves to under_maintenance
        MaintenanceAsset::withoutGlobalScope('tenant')
            ->where('id', $wo->asset_id)
            ->where('status', MaintenanceAsset::STATUS_ACTIVE)
            ->update(['status' => MaintenanceAsset::STATUS_UNDER_MAINTENANCE]);
    }

    public function startWorkOrder(MaintenanceWorkOrder $wo): void
    {
        $wo->update([
            'status'     => MaintenanceWorkOrder::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function completeWorkOrder(MaintenanceWorkOrder $wo, ?string $remarks = null): void
    {
        $wo->update([
            'status'       => MaintenanceWorkOrder::STATUS_COMPLETED,
            'completed_at' => now(),
            'remarks'      => $remarks ?? $wo->remarks,
        ]);
    }

    /**
     * Close a work order: consume inventory parts, calculate costs, post GL entry.
     */
    public function closeWorkOrder(MaintenanceWorkOrder $wo): void
    {
        DB::transaction(function () use ($wo) {
            $tenant = $wo->tenant;

            // 1. Consume inventory parts — deduct stock
            $partsCost = 0.0;
            foreach ($wo->parts as $part) {
                $qtyUsed = (float) $part->quantity_requested;
                $item    = InventoryItem::withoutGlobalScope('tenant')
                    ->lockForUpdate()
                    ->findOrFail($part->inventory_item_id);

                if ((float) $item->current_stock < $qtyUsed) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$item->name}'. " .
                        "Available: {$item->current_stock}, Required: {$qtyUsed}."
                    );
                }

                $unitCost = (float) $item->avg_cost ?: (float) $item->cost_price;
                $subtotal = round($qtyUsed * $unitCost, 2);

                $part->update([
                    'quantity_used' => $qtyUsed,
                    'unit_cost'     => $unitCost,
                    'subtotal'      => $subtotal,
                ]);

                $item->decrement('current_stock', $qtyUsed);

                StockMovement::create([
                    'tenant_id'       => $tenant->id,
                    'item_id'         => $item->id,
                    'type'            => 'adjustment_out',
                    'quantity'        => $qtyUsed,
                    'unit_cost'       => $unitCost,
                    'running_balance' => (float) $item->fresh()->current_stock,
                    'reference_type'  => MaintenanceWorkOrder::class,
                    'reference_id'    => $wo->id,
                    'notes'           => "Used in work order {$wo->work_order_number}",
                    'created_by'      => $wo->created_by,
                ]);

                $partsCost += $subtotal;
            }

            // 2. Recalculate actual_hours from labor logs
            $laborLogs  = $wo->laborLogs;
            $laborHours = (float) $laborLogs->sum('hours_worked');
            $laborCost  = (float) $laborLogs->sum('labor_cost');

            $wo->update(['actual_hours' => $laborHours]);

            // 3. Upsert maintenance_costs record
            $totalCost = $laborCost + $partsCost;

            $cost = MaintenanceCost::updateOrCreate(
                ['work_order_id' => $wo->id],
                [
                    'tenant_id'     => $tenant->id,
                    'asset_id'      => $wo->asset_id,
                    'labor_cost'    => $laborCost,
                    'parts_cost'    => $partsCost,
                    'external_cost' => 0,
                    'total_cost'    => $totalCost,
                ]
            );

            // 4. Post GL entry if total > 0: Dr 5500 Maintenance Expense / Cr 1200 Inventory + 2001 AP
            if ($totalCost > 0) {
                $accts = Account::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('code', ['5500', '1200', '2001'])
                    ->pluck('id', 'code');

                if ($accts->isNotEmpty() && $accts->has('5500')) {
                    $entries = [];

                    // Debit: Maintenance & Repairs Expense
                    $entries[] = ['account_id' => $accts['5500'], 'entry_type' => 'debit', 'amount' => $totalCost];

                    // Credit: Inventory for parts consumed
                    if ($partsCost > 0 && $accts->has('1200')) {
                        $entries[] = ['account_id' => $accts['1200'], 'entry_type' => 'credit', 'amount' => $partsCost];
                    }

                    // Credit: Accounts Payable for labour accrual
                    if ($laborCost > 0 && $accts->has('2001')) {
                        $entries[] = ['account_id' => $accts['2001'], 'entry_type' => 'credit', 'amount' => $laborCost];
                    }

                    // If we can't match all credits (accounts missing), skip GL posting
                    $creditTotal = collect($entries)->where('entry_type', 'credit')->sum('amount');
                    if (round($creditTotal, 2) === round($totalCost, 2)) {
                        $tx = $this->bookkeeping->postJournalEntry(
                            tenant: $tenant,
                            data: [
                                'reference'        => $wo->work_order_number,
                                'transaction_date' => now()->toDateString(),
                                'type'             => 'expense',
                                'description'      => "Maintenance: {$wo->work_order_number} — {$wo->title}",
                            ],
                            entries: $entries
                        );

                        $cost->update(['transaction_id' => $tx->id, 'posted_at' => now()]);
                    }
                }
            }

            // 5. Close the work order
            $wo->update(['status' => MaintenanceWorkOrder::STATUS_CLOSED, 'closed_at' => now()]);

            // 6. Restore asset status to active
            MaintenanceAsset::withoutGlobalScope('tenant')
                ->where('id', $wo->asset_id)
                ->whereIn('status', [
                    MaintenanceAsset::STATUS_UNDER_MAINTENANCE,
                    MaintenanceAsset::STATUS_BREAKDOWN,
                ])
                ->update(['status' => MaintenanceAsset::STATUS_ACTIVE]);

            // 7. If corrective WO, close breakdown
            if ($wo->breakdown_id) {
                MaintenanceBreakdown::withoutGlobalScope('tenant')
                    ->where('id', $wo->breakdown_id)
                    ->whereIn('status', [
                        MaintenanceBreakdown::STATUS_OPEN,
                        MaintenanceBreakdown::STATUS_IN_PROGRESS,
                    ])
                    ->update([
                        'status'       => MaintenanceBreakdown::STATUS_RESOLVED,
                        'downtime_end' => now(),
                        'downtime_hours' => round(
                            now()->diffInMinutes(
                                MaintenanceBreakdown::withoutGlobalScope('tenant')
                                    ->find($wo->breakdown_id)?->downtime_start ?? now()
                            ) / 60, 2
                        ),
                    ]);
            }
        });
    }

    // ── Labor Logging ─────────────────────────────────────────────────────────

    public function logLabor(MaintenanceWorkOrder $wo, array $data): MaintenanceLaborLog
    {
        $hoursWorked = (float) $data['hours_worked'];
        $hourlyRate  = (float) ($data['hourly_rate'] ?? 0);

        $log = MaintenanceLaborLog::create([
            'tenant_id'   => $wo->tenant_id,
            'work_order_id'=> $wo->id,
            'user_id'     => $data['user_id'],
            'work_date'   => $data['work_date'] ?? now()->toDateString(),
            'hours_worked'=> $hoursWorked,
            'hourly_rate' => $hourlyRate,
            'labor_cost'  => round($hoursWorked * $hourlyRate, 2),
            'description' => $data['description'] ?? null,
        ]);

        // Keep actual_hours denormalised
        $wo->increment('actual_hours', $hoursWorked);

        return $log;
    }

    // ── PM Schedule Generation ────────────────────────────────────────────────

    /**
     * Generate work orders for all due/overdue active PM schedules.
     * Called by the daily artisan command. Skips if already generated today.
     */
    public function generateDuePmWorkOrders(): int
    {
        $count = 0;

        $schedules = MaintenanceSchedule::withoutGlobalScope('tenant')
            ->with(['tenant', 'asset'])
            ->where('is_active', true)
            ->where('next_due_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('last_generated_at')
                  ->orWhereDate('last_generated_at', '<', now()->toDateString());
            })
            ->get();

        foreach ($schedules as $schedule) {
            // Skip if asset is retired
            if ($schedule->asset->status === MaintenanceAsset::STATUS_RETIRED) {
                continue;
            }

            // Skip if there is already an open WO for this schedule
            $openExists = MaintenanceWorkOrder::withoutGlobalScope('tenant')
                ->where('schedule_id', $schedule->id)
                ->whereNotIn('status', [
                    MaintenanceWorkOrder::STATUS_COMPLETED,
                    MaintenanceWorkOrder::STATUS_CLOSED,
                ])
                ->exists();

            if ($openExists) {
                continue;
            }

            DB::transaction(function () use ($schedule, &$count) {
                $this->createWorkOrder($schedule->tenant, [
                    'source_type'      => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
                    'asset_id'         => $schedule->asset_id,
                    'schedule_id'      => $schedule->id,
                    'title'            => $schedule->name . ' — ' . $schedule->asset->asset_name,
                    'description'      => $schedule->checklist
                        ? implode("\n", $schedule->checklist)
                        : null,
                    'priority'         => MaintenanceWorkOrder::PRIORITY_MEDIUM,
                    'assigned_to'      => $schedule->assigned_technician_id,
                    'scheduled_date'   => $schedule->next_due_date->toDateString(),
                    'estimated_hours'  => $schedule->estimated_hours,
                    'created_by'       => $schedule->created_by,
                ]);

                $schedule->advanceNextDueDate();
                $schedule->last_generated_at = now();
                $schedule->save();

                $count++;
            });
        }

        return $count;
    }
}
