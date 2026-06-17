<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\InventoryLocation;

class StockMovement extends Model
{
    // Immutable ledger — updated_at is disabled; created_at is set by DB default
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'item_id', 'location_id', 'transfer_pair_id',
        'type', 'quantity', 'unit_cost',
        'running_balance', 'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'        => 'decimal:3',
        'unit_cost'       => 'decimal:2',
        'running_balance' => 'decimal:3',
    ];

    public const INBOUND_TYPES  = ['restock', 'opening', 'adjustment_in', 'transfer_in', 'production_in'];
    public const OUTBOUND_TYPES = ['sale', 'adjustment_out', 'transfer_out', 'production_out'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'transfer_pair_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
