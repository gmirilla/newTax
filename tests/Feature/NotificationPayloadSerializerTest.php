<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Tenant;
use App\Notifications\ReferralRewardedNotification;
use App\Support\NotificationPayloadSerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

/** Local fixture: mixes a model arg with a scalar arg, matching this app's promoted-property convention. */
class SerializerFixtureNotification extends Notification
{
    public function __construct(
        public readonly Referral $referral,
        public readonly string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}

class NotificationPayloadSerializerTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $name): Tenant
    {
        return Tenant::create([
            'name'            => $name,
            'slug'            => strtolower(str_replace(' ', '-', $name)) . '-' . uniqid(),
            'email'           => strtolower(str_replace(' ', '', $name)) . '@test.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 1_000_000,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);
    }

    public function test_capture_and_reconstruct_round_trips_a_model_constructor_argument(): void
    {
        $referrer = $this->makeTenant('Referrer Co');
        $referee  = $this->makeTenant('Referee Co');

        $referral = Referral::withoutGlobalScope('tenant')->create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => 'rewarded',
            'reward_ngn'         => 2000,
        ]);

        $notification = new ReferralRewardedNotification($referral);

        $payload = NotificationPayloadSerializer::capture($notification);

        $this->assertSame(Referral::class, $payload['referral']['__model']);
        $this->assertSame($referral->id, $payload['referral']['__id']);

        $rebuilt = NotificationPayloadSerializer::reconstruct(ReferralRewardedNotification::class, $payload);

        $this->assertInstanceOf(ReferralRewardedNotification::class, $rebuilt);
        $this->assertSame($referral->id, $rebuilt->referral->id);
    }

    public function test_reconstruct_throws_when_referenced_model_no_longer_exists(): void
    {
        $referrer = $this->makeTenant('Referrer Co');
        $referee  = $this->makeTenant('Referee Co');

        $referral = Referral::withoutGlobalScope('tenant')->create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => 'rewarded',
            'reward_ngn'         => 2000,
        ]);

        $payload = ['referral' => ['__model' => Referral::class, '__id' => $referral->id]];
        $referral->delete();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        NotificationPayloadSerializer::reconstruct(ReferralRewardedNotification::class, $payload);
    }

    public function test_capture_preserves_plain_scalar_constructor_arguments(): void
    {
        $referrer = $this->makeTenant('Referrer Co');
        $referee  = $this->makeTenant('Referee Co');

        $referral = Referral::withoutGlobalScope('tenant')->create([
            'referrer_tenant_id' => $referrer->id,
            'referee_tenant_id'  => $referee->id,
            'status'             => 'rewarded',
            'reward_ngn'         => 2000,
        ]);

        $notification = new SerializerFixtureNotification($referral, 'approved');

        $payload = NotificationPayloadSerializer::capture($notification);

        $this->assertSame('approved', $payload['event']);
        $this->assertSame(Referral::class, $payload['referral']['__model']);

        $rebuilt = NotificationPayloadSerializer::reconstruct(SerializerFixtureNotification::class, $payload);

        $this->assertSame('approved', $rebuilt->event);
        $this->assertSame($referral->id, $rebuilt->referral->id);
    }
}
