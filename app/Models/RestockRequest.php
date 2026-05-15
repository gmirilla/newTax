<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductionOrder;

class RestockRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_RECEIVED  = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'item_id', 'request_number',
        'quantity_requested', 'quantity_received', 'unit_cost',
        'supplier_name', 'supplier_invoice_no', 'notes',
        'status', 'requested_by', 'approved_by',
        'approved_at', 'received_at', 'rejection_reason', 'invoice_id',
        'production_order_id',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_received'  => 'decimal:3',
        'unit_cost'          => 'decimal:2',
        'approved_at'        => 'datetime',
        'received_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('restock_requests.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', self::class);
    }

    public function totalCost(): float
    {
        return round((float) $this->quantity_requested * (float) $this->unit_cost, 2);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }
}
