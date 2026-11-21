<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Setting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class ApplyMailSettings
{
    private readonly string $serverMailer;

    private readonly array $serverFrom;

    public function __construct()
    {
        $this->serverMailer = config('mail.default');
        $this->serverFrom = config('mail.from');
    }

    public function configuredSenderAddress(): string
    {
        return $this->senderAddress(Setting::get('mail.from_address'), Setting::get('mail.mode'));
    }

    private function senderAddress(?string $address, ?string $mode): string
    {
        // Previous installers seeded this example even when MAIL_FROM_ADDRESS was set.
        if ($mode === null && $address === 'noreply@example.com') {
            return $this->serverFrom['address'];
        }

        return $address ?: $this->serverFrom['address'];
    }

    public function handle(MailManager $manager): void
    {
        // Read a committed snapshot so long-running workers pick up credential rotations.
        $settings = Setting::query()->whereLike('key', 'mail.%')->pluck('value', 'key');
        $mode = $settings->get('mail.mode', 'server');

        config([
            'mail.default' => match ($mode) {
                'smtp' => 'managed_smtp',
                'log' => 'log',
                default => $this->serverMailer,
            },
            'mail.from.name' => $settings->get('mail.from_name') ?: $this->serverFrom['name'],
            'mail.from.address' => $this->senderAddress($settings->get('mail.from_address'), $settings->get('mail.mode')),
        ]);

        if ($mode === 'smtp') {
            config(['mail.mailers.managed_smtp' => ['transport' => 'managed_smtp']]);
            $manager->extend('managed_smtp', static function () use ($manager, $settings): TransportInterface {
                $password = $settings->get('mail.smtp_password');
                $security = $settings->get('mail.security');

                return $manager->createSymfonyTransport([
                    'transport' => 'smtp',
                    'host' => $settings->get('mail.host'),
                    'port' => (int) $settings->get('mail.port'),
                    'scheme' => $security === 'tls' ? 'smtps' : 'smtp',
                    'require_tls' => $security === 'starttls',
                    'auto_tls' => $security !== 'none',
                    'username' => $settings->get('mail.username'),
                    'password' => $password ? Crypt::decryptString($password) : null,
                    'timeout' => 10,
                    'local_domain' => config('mail.mailers.smtp.local_domain'),
                ]);
            });
        }

        $manager->forgetMailers();
    }
}
