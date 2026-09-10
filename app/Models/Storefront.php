<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storefront extends Model
{
    protected $fillable = [
        'tenant_id', 'is_enabled', 'whatsapp_number', 'banner_image', 'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefronts.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** WhatsApp deep link pre-filled with an order summary. */
    public function whatsappLink(string $text): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }

        $number = preg_replace('/\D+/', '', $this->whatsapp_number);

        return "https://wa.me/{$number}?text=" . rawurlencode($text);
    }
}
