<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storefront extends Model
{
    protected $fillable = [
        'tenant_id', 'is_enabled', 'vat_applicable', 'whatsapp_number', 'banner_image', 'description',
        'pickup_enabled', 'delivery_enabled', 'pickup_address', 'delivery_states',
    ];

    protected $casts = [
        'is_enabled'       => 'boolean',
        'vat_applicable'   => 'boolean',
        'pickup_enabled'   => 'boolean',
        'delivery_enabled' => 'boolean',
        'delivery_states'  => 'array',
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

    public function offersPickupAndDelivery(): bool
    {
        return $this->pickup_enabled && $this->delivery_enabled;
    }

    /** Display lines for the public "where we deliver / pick up" notice. */
    public function deliveryNoticeLines(): array
    {
        $lines = [];

        if ($this->delivery_enabled && !empty($this->delivery_states)) {
            $lines[] = 'Delivery to: ' . implode(', ', $this->delivery_states);
        }

        if ($this->pickup_enabled && $this->pickup_address) {
            $lines[] = 'Pickup available at: ' . $this->pickup_address;
        }

        return $lines;
    }
}
