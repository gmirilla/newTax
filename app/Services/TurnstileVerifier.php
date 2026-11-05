<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Cloudflare Turnstile response token server-side.
 * If no secret key is configured (e.g. local dev), verification is skipped
 * and treated as passing — forms fall back to honeypot + throttling only.
 */
class TurnstileVerifier
{
    public function isConfigured(): bool
    {
        return filled(config('turnstile.secret_key'));
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post(config('turnstile.verify_url'), [
                'secret'   => config('turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification request failed: ' . $e->getMessage());

            return false;
        }
    }
}
