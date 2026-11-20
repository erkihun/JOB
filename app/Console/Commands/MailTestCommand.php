<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends one email synchronously (no queue) so SMTP settings can be verified
 * from hosts where interactive tinker is unavailable (e.g. Plesk).
 *
 *   mail:test you@example.com          plain-text test message
 *   mail:test you@example.com --otp    the real HTML code email (code 000000)
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test
        {email : Recipient address}
        {--otp : Send the HTML one-time-code email template instead of plain text}';

    protected $description = 'Send a test email immediately and show which account (if any) owns the address';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Invalid email address: {$email}");

            return self::INVALID;
        }

        $this->showMailConfig();
        $this->showAccount($email);

        try {
            if ($this->option('otp')) {
                Notification::route('mail', $email)
                    ->notifyNow(new PasswordResetOtpNotification('000000'));
            } else {
                Mail::raw(
                    'This is a test email from '.config('app.name').'. If you received it, outgoing mail works.',
                    fn ($message) => $message->to($email)->subject(config('app.name').' - SMTP test'),
                );
            }
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('%s email sent to %s.', $this->option('otp') ? 'Code (OTP)' : 'Test', $email));
        $this->line('If it does not arrive within a few minutes, check the Spam folder.');

        return self::SUCCESS;
    }

    private function showMailConfig(): void
    {
        $mailer = (string) config('mail.default');
        $this->line("Mailer:  {$mailer}");

        if ($mailer === 'smtp') {
            $this->line(sprintf(
                'SMTP:    %s:%s (scheme: %s, user: %s)',
                config('mail.mailers.smtp.host'),
                config('mail.mailers.smtp.port'),
                config('mail.mailers.smtp.scheme') ?: 'auto',
                config('mail.mailers.smtp.username') ?: '-',
            ));
        }

        $this->line('From:    '.config('mail.from.address'));
    }

    /** Password-reset codes are only sent to accounts with the matching role. */
    private function showAccount(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->warn('Account: none - the forgot-password pages send nothing to this address.');

            return;
        }

        $roles = $user->getRoleNames()->implode(', ') ?: 'no roles';
        $this->line(sprintf(
            'Account: #%s %s [%s], email %s',
            $user->id,
            $user->name,
            $roles,
            $user->email_verified_at ? 'verified' : 'NOT verified',
        ));

        $this->line($user->hasRole('applicant')
            ? '         Applicant reset page: /applicant/forgot-password'
            : '         Admin reset page: /admin/forgot-password (applicant page sends nothing)');
    }
}
