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
        return Cache::remember(
            "platform_payment_credentials.{$provider}",
            60,
            fn () => static::where('provider', $provider)->first()
        );
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
