<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_price',
        'subtotal', 'vat_applicable', 'vat_rate', 'vat_amount',
        'total', 'account_code', 'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'decimal:2',
        'unit_price'     => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'vat_applicable' => 'boolean',
        'vat_rate'       => 'decimal:2',
        'vat_amount'     => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal   = round($this->quantity * $this->unit_price, 2);
        $this->vat_amount = $this->vat_applicable
            ? round($this->subtotal * $this->vat_rate / 100, 2)
            : 0;
        $this->total      = $this->subtotal + $this->vat_amount;
    }
}
