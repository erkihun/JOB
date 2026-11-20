<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one plain-text email synchronously (no queue) so SMTP settings can be
 * verified from hosts where interactive tinker is unavailable (e.g. Plesk).
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Recipient address}';

    protected $description = 'Send a test email immediately to verify the mail configuration';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Invalid email address: {$email}");

            return self::INVALID;
        }

        $mailer = (string) config('mail.default');
        $this->line("Mailer: {$mailer}");
        if ($mailer === 'smtp') {
            $this->line(sprintf(
                'SMTP:   %s:%s (scheme: %s, user: %s)',
                config('mail.mailers.smtp.host'),
                config('mail.mailers.smtp.port'),
                config('mail.mailers.smtp.scheme') ?: 'auto',
                config('mail.mailers.smtp.username') ?: '-',
            ));
        }
        $this->line('From:   '.config('mail.from.address'));

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you received it, outgoing mail works.',
                fn ($message) => $message->to($email)->subject(config('app.name').' - SMTP test'),
            );
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$email}.");

        return self::SUCCESS;
    }
}
