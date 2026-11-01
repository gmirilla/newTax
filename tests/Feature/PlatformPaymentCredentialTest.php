<?php

namespace Tests\Feature;

use App\Models\PlatformPaymentCredential;
use Tests\TestCase;

class PlatformPaymentCredentialTest extends TestCase
{
    public function test_secret_key_is_encrypted_in_the_underlying_attribute(): void
    {
        $credential = new PlatformPaymentCredential([
            'provider'   => 'paystack',
            'secret_key' => 'sk_test_abc123',
            'public_key' => 'pk_test_abc123',
        ]);

        // Laravel's "encrypted" cast encrypts eagerly on set, before any save() —
        // the raw attribute must never equal the plaintext we assigned.
        $this->assertNotEquals('sk_test_abc123', $credential->getAttributes()['secret_key']);
        $this->assertNotEquals('pk_test_abc123', $credential->getAttributes()['public_key']);

        // The accessor transparently decrypts back to the original value.
        $this->assertEquals('sk_test_abc123', $credential->secret_key);
        $this->assertEquals('pk_test_abc123', $credential->public_key);
    }

    public function test_secrets_are_hidden_from_array_and_json_serialisation(): void
    {
        $credential = new PlatformPaymentCredential([
            'provider'   => 'paystack',
            'secret_key' => 'sk_test_abc123',
            'public_key' => 'pk_test_abc123',
        ]);

        $this->assertArrayNotHasKey('secret_key', $credential->toArray());
        $this->assertArrayNotHasKey('public_key', $credential->toArray());
        $this->assertStringNotContainsString('sk_test_abc123', $credential->toJson());
    }
}
