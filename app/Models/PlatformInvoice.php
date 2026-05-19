<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoice extends Model
{
    protected $fillable = [
        'invoice_number', 'tenant_id', 'agreement_id',
        'period_start', 'period_end', 'amount', 'due_date',
        'status', 'paid_at', 'payment_method', 'payment_reference',
        'notes', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'period_start' => 'date',
        'period_end'   => 'date',
        'due_date'     => 'date',
        'paid_at'      => 'datetime',
    ];

    const STATUS_DRAFT   = 'draft';
    const STATUS_SENT    = 'sent';
    const STATUS_PAID    = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_VOID    = 'void';

    const STATUS_COLORS = [
        'draft'   => 'gray',
        'sent'    => 'blue',
        'paid'    => 'green',
        'overdue' => 'red',
        'void'    => 'gray',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(EnterpriseAgreement::class, 'agreement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(fn($q) => $q->where('status', self::STATUS_SENT)
                ->where('due_date', '<', now()->toDateString()));
    }

    /** Generate the next sequential invoice number (PLT-YYYYMM-NNNN). */
    public static function nextNumber(): string
    {
        $prefix = 'PLT-' . now()->format('Ym') . '-';

        $last = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
