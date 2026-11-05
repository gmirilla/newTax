<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', $this->token) . '?email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset your AccountTaxNG password')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You requested a password reset. Click the button below to choose a new password.')
            ->action('Reset Password', $url)
            ->line('This link expires in 60 minutes.')
            ->line("If you didn't request this, you can safely ignore this email — your password won't change.")
            ->salutation('AccountTaxNG');
    }
}
