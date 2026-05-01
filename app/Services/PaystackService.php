<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    private readonly string $secretKey;
    private readonly string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paystack.secret_key');
        $this->baseUrl   = config('paystack.payment_url');
    }

    /** Initialize a transaction (one-off or subscription). Returns Paystack response array. */
    public function initializeTransaction(array $data): array
    {
        return Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", $data)
            ->json();
    }

    /** Verify a transaction by reference after the user returns from Paystack. */
    public function verifyTransaction(string $reference): array
    {
        return Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}")
            ->json();
    }

    /** Fetch a subscription by its Paystack subscription code. */
    public function getSubscription(string $subscriptionCode): array
    {
        return Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/subscription/{$subscriptionCode}")
            ->json();
    }

    /** Disable (cancel) a recurring subscription. Requires the email token from the subscription. */
    public function disableSubscription(string $code, string $emailToken): array
    {
        return Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription/disable", [
                'code'  => $code,
                'token' => $emailToken,
            ])
            ->json();
    }
}
