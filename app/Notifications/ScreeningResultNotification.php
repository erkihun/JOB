<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScreeningResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Application $application,
        private readonly ApplicationStatus $result,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = property_exists($notifiable, 'name') ? (string) $notifiable->name : '';
        $vacancy = $this->application->vacancy?->title ?? $this->application->vacancy?->code ?? '';
        $key = $this->result === ApplicationStatus::PassedScreening ? 'screening_passed' : 'screening_failed';

        return (new MailMessage)
            ->subject(trans("notifications.{$key}.subject", ['vacancy' => $vacancy]))
            ->greeting(trans("notifications.{$key}.greeting", ['name' => $name]))
            ->line(trans("notifications.{$key}.body", ['vacancy' => $vacancy]))
            ->line(trans("notifications.{$key}.closing"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'screening_result',
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'result' => $this->result->value,
            'result_label' => $this->result->label(),
        ];
    }
}
