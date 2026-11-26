<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PlatformPaymentCredential extends Model
{
    protected $fillable = [
        'provider',
        'secret_key',
        'public_key',
        'credentials_set_at',
        'updated_by_user_id',
    ];

    /**
     * Prevent secrets from appearing in logs, error reports, or JSON serialisation.
     */
    protected $hidden = [
        'secret_key',
        'public_key',
    ];

    protected $casts = [
        'secret_key'          => 'encrypted',
        'public_key'          => 'encrypted',
        'credentials_set_at'  => 'datetime',
    ];

    public static function cached(string $provider = 'paystack'): ?static
    {
        // Cache the raw attribute array, never the model: with cache.serializable_classes=false
        // a cached model is read back as __PHP_Incomplete_Class and breaks the ?static return.
        // The raw attributes keep secret_key/public_key encrypted at rest in the cache.
        $attributes = Cache::remember(
            "platform_payment_credentials.{$provider}",
            60,
            fn () => static::where('provider', $provider)->first()?->getAttributes()
        );

        return is_array($attributes) ? (new static)->newFromBuilder($attributes) : null;
    }

    public static function forgetCache(string $provider = 'paystack'): void
    {
        Cache::forget("platform_payment_credentials.{$provider}");
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
