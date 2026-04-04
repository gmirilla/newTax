<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'pay_year', 'pay_month', 'pay_date', 'status',
        'total_gross', 'total_paye', 'total_pension', 'total_nhf', 'total_net',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'pay_date'     => 'date',
        'total_gross'  => 'decimal:2',
        'total_paye'   => 'decimal:2',
        'total_pension'=> 'decimal:2',
        'total_nhf'    => 'decimal:2',
        'total_net'    => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getMonthName(): string
    {
        return date('F Y', mktime(0, 0, 0, $this->pay_month, 1, $this->pay_year));
    }
}
