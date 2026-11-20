<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One-time code email, used both for password resets and for verifying a new
 * applicant's email address. Sent synchronously so the code arrives while the
 * user is still on the "enter your code" page.
 */
class PasswordResetOtpNotification extends Notification
{
    public const PURPOSE_RESET = 'reset';

    public const PURPOSE_VERIFY = 'verify';

    public function __construct(
        private readonly string $otp,
        private readonly string $purpose = self::PURPOSE_RESET,
    ) {}

    /** @return array<string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = (string) ($notifiable->name ?? '');
        $orgName = Setting::get('org.name', config('app.name'));
        $prefix = $this->purpose === self::PURPOSE_VERIFY ? 'auth.verify_otp_' : 'auth.otp_';

        return (new MailMessage)
            ->subject(__($prefix.'subject', ['org' => $orgName]))
            ->view('emails.password-reset-otp', [
                'otp' => $this->otp,
                'orgName' => $orgName,
                'heading' => __($prefix.'heading'),
                'preview' => __($prefix.'preview', ['otp' => $this->otp]),
                'greeting' => __('auth.otp_greeting', ['name' => $name]),
                'line1' => __($prefix.'line1'),
                'expires' => __('auth.otp_expires'),
                'ignore' => __($prefix.'ignore'),
                'year' => date('Y'),
            ]);
    }
}
