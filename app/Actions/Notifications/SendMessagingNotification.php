<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Setting;
use App\Support\EthiopianPhone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SendMessagingNotification
{
    public function handle(string $channel, string $message, ?string $phone = null): void
    {
        if (! in_array($channel, ['sms', 'telegram'], true)) {
            throw new RuntimeException(__('settings.messaging_failed'));
        }

        // Workers must pick up newly saved credentials and disabled channels.
        $settings = Setting::query()->whereLike('key', $channel.'.%')->pluck('value', 'key');
        if (! $settings->get($channel.'.enabled') || ! $settings->get($channel.'.token')) {
            throw new RuntimeException(__('settings.messaging_disabled'));
        }

        $client = Http::acceptJson()->connectTimeout(5)->timeout(12)->withoutRedirecting();
        try {
            if ($channel === 'telegram') {
                $token = Crypt::decryptString($settings['telegram.token']);
                $response = $client->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $settings['telegram.chat_id'], 'text' => $message,
                ]);
                $accepted = $response->successful() && $response->json('ok') === true && $response->json('result.message_id') !== null;
            } else {
                if ($phone && preg_match('/^(?:0?|251)[79]\d{8}$/D', $phone)) {
                    $phone = EthiopianPhone::normalize($phone);
                }
                if (! preg_match('/^\+[1-9]\d{6,14}$/D', $phone ?? '')) {
                    throw new RuntimeException(__('settings.sms_phone_required'));
                }
                $key = 'sms.iam.'.hash('sha256', $settings->sortKeys()->toJson());
                $token = Cache::remember($key, now()->addMinutes(15), fn () => $this->issueSmsToken($settings));
                $response = $client->withHeaders(['X-Auth-Token' => Crypt::decryptString($token)])->post(
                    rtrim($settings['sms.smn_url'], '/').'/v2/'.$settings['sms.project_id'].'/notifications/sms',
                    ['endpoint' => $phone, 'message' => $message],
                );
                if ($response->unauthorized()) {
                    Cache::forget($key);
                }
                $accepted = $response->successful() && filled($response->json('message_id'));
            }
        } catch (ConnectionException) {
            // Transport exceptions can include the Telegram bot token in the URL.
            throw new RuntimeException(__('settings.messaging_failed'));
        }

        if (! $accepted) {
            throw new RuntimeException(__('settings.messaging_failed'));
        }
    }

    private function issueSmsToken(Collection $settings): string
    {
        $response = Http::acceptJson()->connectTimeout(5)->timeout(12)->withoutRedirecting()
            ->post(rtrim($settings['sms.iam_url'], '/').'/v3/auth/tokens', [
                'auth' => [
                    'identity' => [
                        'methods' => ['password'],
                        'password' => ['user' => [
                            'name' => $settings['sms.username'],
                            'domain' => ['name' => $settings['sms.domain']],
                            'password' => Crypt::decryptString($settings['sms.token']),
                        ]],
                    ],
                    'scope' => ['project' => ['id' => $settings['sms.project_id']]],
                ],
            ]);
        $token = $response->header('X-Subject-Token');
        if (! $response->successful() || blank($token)) {
            throw new RuntimeException(__('settings.messaging_failed'));
        }

        return Crypt::encryptString($token);
    }
}
