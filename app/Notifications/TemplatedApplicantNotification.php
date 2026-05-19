<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ApplicantNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplatedApplicantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ApplicantNotification $notificationRecord) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->notificationRecord->channel === 'email' && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notificationRecord->subject ?? $this->notificationRecord->type->label())
            ->line($this->notificationRecord->message);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'applicant_notification_id' => $this->notificationRecord->id,
            'application_id' => $this->notificationRecord->application_id,
            'type' => $this->notificationRecord->type->value,
            'subject' => $this->notificationRecord->subject,
            'message' => $this->notificationRecord->message,
        ];
    }
}
