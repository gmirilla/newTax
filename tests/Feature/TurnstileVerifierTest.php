<?php

namespace Tests\Feature;

use App\Services\TurnstileVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileVerifierTest extends TestCase
{
    public function test_verification_passes_automatically_when_no_secret_key_is_configured(): void
    {
        config(['turnstile.secret_key' => null]);

        $this->assertTrue(app(TurnstileVerifier::class)->verify(null));
        $this->assertTrue(app(TurnstileVerifier::class)->verify('anything'));
    }

    public function test_a_blank_token_fails_when_a_secret_key_is_configured(): void
    {
        config(['turnstile.secret_key' => 'test-secret']);

        $this->assertFalse(app(TurnstileVerifier::class)->verify(null));
        $this->assertFalse(app(TurnstileVerifier::class)->verify(''));
    }

    public function test_a_valid_token_passes_when_cloudflare_confirms_success(): void
    {
        config(['turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true], 200)]);

        $this->assertTrue(app(TurnstileVerifier::class)->verify('good-token', '1.2.3.4'));

        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret' && $request['response'] === 'good-token');
    }

    public function test_an_invalid_token_fails_when_cloudflare_rejects_it(): void
    {
        config(['turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('bad-token'));
    }

    public function test_a_network_failure_fails_closed(): void
    {
        config(['turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('some-token'));
    }
}
