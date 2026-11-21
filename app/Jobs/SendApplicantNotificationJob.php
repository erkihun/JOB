<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Notifications\SendMessagingNotification;
use App\Models\ApplicantNotification;
use App\Notifications\TemplatedApplicantNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendApplicantNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly ApplicantNotification $notification,
    ) {}

    public function handle(): void
    {
        if ($this->notification->status === 'sent') {
            return;
        }

        $recipient = $this->notification->applicant?->user;

        if ($this->notification->channel === 'email' && empty($recipient?->email)) {
            $this->notification->update(['status' => 'failed']);

            return;
        }

        $sms = $this->notification->channel === 'sms';
        $phone = $this->notification->applicant?->phone ?: $recipient?->phone;
        if ($sms && blank($phone)) {
            $this->notification->update(['status' => 'failed']);

            return;
        }

        try {
            if ($recipient !== null) {
                $message = new TemplatedApplicantNotification($this->notification);
                $message->id = $this->notification->getKey();
                $channels = $message->via($recipient);
                if ($recipient->notifications()->whereKey($message->id)->exists()) {
                    $channels = array_values(array_diff($channels, ['database']));
                }
                if ($channels !== []) {
                    $recipient->notifyNow($message, $channels);
                }
            }
            if ($sms) {
                app(SendMessagingNotification::class)->handle('sms', $this->notification->message, $phone);
            }

            $this->notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->notification->update(['status' => 'failed']);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->notification->update(['status' => 'failed']);
    }
}
