<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontProductImage extends Model
{
    protected $fillable = [
        'tenant_id', 'storefront_product_id', 'image_path', 'sort_order',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefront_product_images.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StorefrontProduct::class, 'storefront_product_id');
    }
}
