<?php

namespace App\Services;

use App\Models\PlatformPaymentCredential;

/**
 * Single chokepoint for resolving a payment gateway credential: a DB-stored
 * value (set via the superadmin UI) takes precedence over the .env/config
 * value, so key rotation never requires server access or a restart.
 */
class PaymentCredentialResolver
{
    public function secretKey(string $provider = 'paystack'): ?string
    {
        return PlatformPaymentCredential::cached($provider)?->secret_key
            ?? config("{$provider}.secret_key");
    }

    public function publicKey(string $provider = 'paystack'): ?string
    {
        return PlatformPaymentCredential::cached($provider)?->public_key
            ?? config("{$provider}.public_key");
    }
}
