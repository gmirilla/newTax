<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'bank_name', 'account_number', 'account_type',
        'currency', 'gl_account_id', 'opening_balance', 'is_default',
        'is_active', 'sort_order', 'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('bank_accounts.tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    /**
     * Current GL balance from journal entries (debit - credit for an asset account).
     */
    public function glBalance(): float
    {
        $account = $this->glAccount;
        if (! $account) {
            return 0.0;
        }

        $debits  = $account->journalEntries()->where('entry_type', 'debit')->sum('amount');
        $credits = $account->journalEntries()->where('entry_type', 'credit')->sum('amount');

        return (float) $debits - (float) $credits;
    }

    /**
     * Allocate the next available GL code in the 1004-1099 range for this tenant.
     */
    public static function nextGlCode(int $tenantId): string
    {
        $used = Account::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('code', ['1004', '1099'])
            ->pluck('code')
            ->map(fn($c) => (int) $c)
            ->sort()
            ->values();

        for ($code = 1004; $code <= 1099; $code++) {
            if (! $used->contains($code)) {
                return (string) $code;
            }
        }

        return '1099'; // Fallback — chart of accounts is very full
    }
}
