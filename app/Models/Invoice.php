<?php

namespace App\Models;

use App\Models\Concerns\HasDiscount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, HasDiscount;

    protected $fillable = [
        'tenant_id', 'customer_id', 'transaction_id',
        'invoice_number', 'reference', 'invoice_date', 'due_date',
        'subtotal', 'vat_amount', 'wht_amount', 'discount_amount', 'discount_type', 'discount_value',
        'total_amount', 'amount_paid', 'balance_due',
        'vat_applicable', 'wht_applicable', 'wht_rate',
        'status', 'notes', 'terms', 'currency', 'qr_code', 'created_by',
        'firs_status', 'is_b2c', 'public_token', 'bank_account_id',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'wht_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value'  => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'balance_due'     => 'decimal:2',
        'vat_applicable'  => 'boolean',
        'wht_applicable'  => 'boolean',
        'wht_rate'        => 'decimal:2',
        'is_b2c'          => 'boolean',
    ];

    public const VAT_RATE = 7.5; // Nigerian VAT rate 7.5%

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->public_token)) {
                $invoice->public_token = (string) Str::uuid();
            }
        });
    }

    public function publicUrl(): string
    {
        return route('invoice.public.show', ['token' => $this->public_token]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function firsSubmission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InvoiceFirsSubmission::class);
    }

    public function restockRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\RestockRequest::class, 'invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid'
            && $this->due_date->isPast()
            && !in_array($this->status, ['cancelled', 'void']);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items->sum('subtotal');
        $lineVat  = (float) $this->items->where('vat_applicable', true)->sum('vat_amount');
        $t        = $this->computeTotals($subtotal, $lineVat);

        $this->subtotal        = $subtotal;
        $this->vat_amount      = $t['vat'];
        $this->wht_amount      = $t['wht'];
        $this->discount_amount = $t['discount'];
        $this->total_amount    = $t['total'];
        $this->balance_due     = $this->total_amount - $this->amount_paid;
        $this->save();
    }
}
