<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRewardedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Referral $referral,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $referee   = $this->referral->referee;
        $rewardNgn = number_format((float) $this->referral->reward_ngn, 0);
        $balance   = number_format((float) $notifiable->tenant->referral_credit_ngn, 0);

        return (new MailMessage)
            ->subject("You've earned ₦{$rewardNgn} referral credit")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("**{$referee->name}** just made their first payment on AccountTaxNG — the account they signed up with using your referral link.")
            ->line("You've been credited **₦{$rewardNgn}** towards your next subscription renewal.")
            ->line("Your available referral credit is now **₦{$balance}**.")
            ->action('View Your Referrals', route('referrals.index'))
            ->line('Keep sharing your link — credit is applied automatically at your next renewal.')
            ->salutation('AccountTaxNG');
    }
}
