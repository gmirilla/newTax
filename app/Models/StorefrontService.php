<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorefrontService extends Model
{
    use SoftDeletes;

    public const MAX_IMAGES = 4;

    protected $fillable = [
        'tenant_id', 'storefront_category_id', 'name', 'description',
        'price', 'is_published', 'vat_applicable', 'sort_order',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'is_published'   => 'boolean',
        'vat_applicable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefront_services.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StorefrontCategory::class, 'storefront_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(StorefrontServiceImage::class)->orderBy('sort_order');
    }
}
