<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

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
        $orgLogo = \App\Models\Setting::get('org.logo', '');

        // Embed logo as Base64 so it renders without network access
        $logoSrc = null;
        if ($orgLogo && Storage::disk('public')->exists($orgLogo)) {
            $mime    = Storage::disk('public')->mimeType($orgLogo) ?: 'image/png';
            $data    = base64_encode(Storage::disk('public')->get($orgLogo));
            $logoSrc = "data:{$mime};base64,{$data}";
        }

        return (new MailMessage)
            ->subject(__('auth.otp_subject', ['org' => $orgName]))
            ->view('emails.password-reset-otp', [
                'otp'      => $this->otp,
                'orgName'  => $orgName,
                'logoSrc'  => $logoSrc,
                'greeting' => __('auth.otp_greeting', ['name' => $name]),
                'line1'    => __('auth.otp_line1'),
                'expires'  => __('auth.otp_expires'),
                'ignore'   => __('auth.otp_ignore'),
                'year'     => date('Y'),
            ]);
    }
}
