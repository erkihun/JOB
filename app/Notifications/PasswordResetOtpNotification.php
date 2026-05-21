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
        $name         = property_exists($notifiable, 'name') ? (string) $notifiable->name : '';
        $orgName      = \App\Models\Setting::get('org.name', config('app.name'));
        $brandColor   = \App\Models\Setting::get('appearance.primary_color', '#1d4ed8');
        $orgLogo      = \App\Models\Setting::get('org.logo', '');
        $logoUrl      = $orgLogo ? url(\Illuminate\Support\Facades\Storage::url($orgLogo)) : null;

        return (new MailMessage)
            ->subject(__('auth.otp_subject', ['org' => $orgName]))
            ->view('emails.password-reset-otp', [
                'otp'        => $this->otp,
                'name'       => $name,
                'orgName'    => $orgName,
                'brandColor' => $brandColor,
                'logoUrl'    => $logoUrl,
                'greeting'   => __('auth.otp_greeting', ['name' => $name]),
                'line1'      => __('auth.otp_line1'),
                'expires'    => __('auth.otp_expires'),
                'ignore'     => __('auth.otp_ignore'),
                'year'       => date('Y'),
            ]);
    }
}
