<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    public function __construct(private readonly string $otp) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name    = property_exists($notifiable, 'name') ? (string) $notifiable->name : '';
        $orgName = \App\Models\Setting::get('org.name', config('app.name'));

        return (new MailMessage)
            ->subject(__('auth.otp_subject', ['org' => $orgName]))
            ->greeting(__('auth.otp_greeting', ['name' => $name]))
            ->line(__('auth.otp_line1'))
            ->line('**' . $this->otp . '**')
            ->line(__('auth.otp_expires'))
            ->line(__('auth.otp_ignore'));
    }
}
