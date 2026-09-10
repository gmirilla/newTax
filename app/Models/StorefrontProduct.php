<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorefrontProduct extends Model
{
    protected $fillable = [
        'tenant_id', 'inventory_item_id', 'is_published', 'web_description', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefront_products.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(StorefrontProductImage::class)->orderBy('sort_order');
    }

    public function description(): string
    {
        return $this->web_description ?: (string) ($this->item?->description ?? '');
    }
}
