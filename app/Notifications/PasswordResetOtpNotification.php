<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    public function __construct(private readonly string $otp) {}

    /** @return array<string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name    = property_exists($notifiable, 'name') ? (string) $notifiable->name : '';
        $orgName = \App\Models\Setting::get('org.name', config('app.name'));

        return (new MailMessage)
            ->subject(__('auth.otp_subject', ['org' => $orgName]))
            ->view('emails.password-reset-otp', [
                'otp'      => $this->otp,
                'orgName'  => $orgName,
                'greeting' => __('auth.otp_greeting', ['name' => $name]),
                'line1'    => __('auth.otp_line1'),
                'expires'  => __('auth.otp_expires'),
                'ignore'   => __('auth.otp_ignore'),
                'year'     => date('Y'),
            ]);
    }
}
