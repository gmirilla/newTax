<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'sub_type',
        'parent_id', 'opening_balance', 'current_balance',
        'is_system', 'is_active', 'description',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_system'       => 'boolean',
        'is_active'       => 'boolean',
    ];

    // Nigerian SME default chart of accounts
    public const DEFAULT_ACCOUNTS = [
        // Assets (1xxx)
        ['code' => '1001', 'name' => 'Cash on Hand',           'type' => 'asset', 'sub_type' => 'cash'],
        ['code' => '1002', 'name' => 'Bank Account - Current', 'type' => 'asset', 'sub_type' => 'bank'],
        ['code' => '1003', 'name' => 'Bank Account - Savings', 'type' => 'asset', 'sub_type' => 'bank'],
        ['code' => '1100', 'name' => 'Accounts Receivable',    'type' => 'asset', 'sub_type' => 'accounts_receivable'],
        ['code' => '1200', 'name' => 'Inventory',              'type' => 'asset', 'sub_type' => 'inventory'],
        ['code' => '1300', 'name' => 'Prepaid Expenses',       'type' => 'asset', 'sub_type' => 'other_asset'],
        ['code' => '1500', 'name' => 'Fixed Assets',           'type' => 'asset', 'sub_type' => 'fixed_asset'],
        // Liabilities (2xxx)
        ['code' => '2001', 'name' => 'Accounts Payable',       'type' => 'liability', 'sub_type' => 'accounts_payable'],
        ['code' => '2100', 'name' => 'VAT Payable',            'type' => 'liability', 'sub_type' => 'vat_payable'],
        ['code' => '2101', 'name' => 'Input VAT Control',      'type' => 'liability', 'sub_type' => 'vat_control'],
        ['code' => '2200', 'name' => 'WHT Payable',            'type' => 'liability', 'sub_type' => 'wht_payable'],
        ['code' => '2300', 'name' => 'PAYE Payable',           'type' => 'liability', 'sub_type' => 'paye_payable'],
        ['code' => '2400', 'name' => 'CIT Payable',            'type' => 'liability', 'sub_type' => 'cit_payable'],
        ['code' => '2500', 'name' => 'Bank Loan',              'type' => 'liability', 'sub_type' => 'loan'],
        // Equity (3xxx)
        ['code' => '3001', 'name' => "Owner's Equity",         'type' => 'equity', 'sub_type' => 'owners_equity'],
        ['code' => '3100', 'name' => 'Retained Earnings',      'type' => 'equity', 'sub_type' => 'retained_earnings'],
        // Revenue (4xxx)
        ['code' => '4001', 'name' => 'Sales Revenue',          'type' => 'revenue', 'sub_type' => 'sales_revenue'],
        ['code' => '4002', 'name' => 'Service Income',         'type' => 'revenue', 'sub_type' => 'service_revenue'],
        ['code' => '4003', 'name' => 'Other Income',           'type' => 'revenue', 'sub_type' => 'other_revenue'],
        // Expenses (5xxx)
        ['code' => '5001', 'name' => 'Cost of Goods Sold',     'type' => 'expense', 'sub_type' => 'cost_of_goods_sold'],
        ['code' => '5100', 'name' => 'Salaries & Wages',       'type' => 'expense', 'sub_type' => 'salaries'],
        ['code' => '5200', 'name' => 'Office Rent',            'type' => 'expense', 'sub_type' => 'rent'],
        ['code' => '5300', 'name' => 'Utilities',              'type' => 'expense', 'sub_type' => 'utilities'],
        ['code' => '5400', 'name' => 'Transport & Travel',     'type' => 'expense', 'sub_type' => 'transport'],
        ['code' => '5500', 'name' => 'Other Operating Expenses','type' => 'expense', 'sub_type' => 'other_expense'],
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
