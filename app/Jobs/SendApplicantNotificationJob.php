<?php

declare(strict_types=1);

namespace App\Jobs;

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
        $recipient = $this->notification->applicant?->user;

        if ($recipient === null) {
            $this->notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return;
        }

        try {
            $recipient->notify(new TemplatedApplicantNotification($this->notification));

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
