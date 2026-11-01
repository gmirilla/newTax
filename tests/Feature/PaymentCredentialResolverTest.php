<?php

namespace Tests\Feature;

use App\Models\PlatformPaymentCredential;
use App\Services\PaymentCredentialResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCredentialResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_config_when_no_db_row_exists(): void
    {
        config(['paystack.secret_key' => 'env_secret', 'paystack.public_key' => 'env_public']);

        $resolver = app(PaymentCredentialResolver::class);

        $this->assertSame('env_secret', $resolver->secretKey());
        $this->assertSame('env_public', $resolver->publicKey());
    }

    public function test_db_value_takes_precedence_over_config(): void
    {
        config(['paystack.secret_key' => 'env_secret', 'paystack.public_key' => 'env_public']);

        PlatformPaymentCredential::create([
            'provider'   => 'paystack',
            'secret_key' => 'db_secret',
            'public_key' => 'db_public',
        ]);

        $resolver = app(PaymentCredentialResolver::class);

        $this->assertSame('db_secret', $resolver->secretKey());
        $this->assertSame('db_public', $resolver->publicKey());
    }
}
