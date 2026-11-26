<?php

namespace Tests\Feature;

use App\Models\PlatformPaymentCredential;
use App\Services\PaymentCredentialResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * cache.serializable_classes is false, so a cached Eloquent model is read back as
 * __PHP_Incomplete_Class in production (database cache) and breaks the ?static return type.
 */
class PlatformPaymentCredentialCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Make the test cache behave like production's database store: values are serialized.
        config(['cache.stores.array.serialize' => true]);
        Cache::purge('array');
        Cache::flush();
    }

    public function test_cached_credential_survives_a_serialising_cache_hit(): void
    {
        $row = new PlatformPaymentCredential(['provider' => 'paystack']);
        $row->secret_key = 'sk_live_abc';
        $row->public_key = 'pk_live_abc';
        $row->save();

        $first  = PlatformPaymentCredential::cached('paystack');   // miss → DB, stores in cache
        $second = PlatformPaymentCredential::cached('paystack');   // hit  → from serialized cache

        $this->assertInstanceOf(PlatformPaymentCredential::class, $first);
        $this->assertInstanceOf(PlatformPaymentCredential::class, $second);
        $this->assertSame('sk_live_abc', $second->secret_key);
        $this->assertSame('pk_live_abc', $second->public_key);
        $this->assertSame('sk_live_abc', app(PaymentCredentialResolver::class)->secretKey());
    }

    public function test_secret_is_not_stored_in_plaintext_in_the_cache(): void
    {
        $row = new PlatformPaymentCredential(['provider' => 'paystack']);
        $row->secret_key = 'sk_live_plain';
        $row->save();

        PlatformPaymentCredential::cached('paystack');

        $stored = json_encode(Cache::get('platform_payment_credentials.paystack'));
        $this->assertStringNotContainsString('sk_live_plain', $stored);
    }

    public function test_missing_credential_returns_null_and_falls_back_to_config(): void
    {
        config(['paystack.secret_key' => 'sk_from_env']);

        $this->assertNull(PlatformPaymentCredential::cached('paystack'));
        $this->assertNull(PlatformPaymentCredential::cached('paystack'));
        $this->assertSame('sk_from_env', app(PaymentCredentialResolver::class)->secretKey());
    }

    public function test_forget_cache_picks_up_updated_credentials(): void
    {
        $row = new PlatformPaymentCredential(['provider' => 'paystack']);
        $row->secret_key = 'sk_old';
        $row->save();
        PlatformPaymentCredential::cached('paystack');

        $row->secret_key = 'sk_new';
        $row->save();
        PlatformPaymentCredential::forgetCache('paystack');

        $this->assertSame('sk_new', PlatformPaymentCredential::cached('paystack')->secret_key);
    }
}
