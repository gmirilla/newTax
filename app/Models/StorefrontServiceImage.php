<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontServiceImage extends Model
{
    protected $fillable = [
        'tenant_id', 'storefront_service_id', 'image_path', 'sort_order',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefront_service_images.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(StorefrontService::class, 'storefront_service_id');
    }
}
