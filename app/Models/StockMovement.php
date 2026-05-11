<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    // Immutable ledger — updated_at is disabled; created_at is set by DB default
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'item_id', 'type', 'quantity', 'unit_cost',
        'running_balance', 'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'        => 'decimal:3',
        'unit_cost'       => 'decimal:2',
        'running_balance' => 'decimal:3',
    ];

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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
