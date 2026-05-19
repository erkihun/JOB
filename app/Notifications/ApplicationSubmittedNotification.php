<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Application $application) {}

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

        return (new MailMessage)
            ->subject(trans('notifications.application_submitted.subject', ['vacancy' => $vacancy]))
            ->greeting(trans('notifications.application_submitted.greeting', ['name' => $name]))
            ->line(trans('notifications.application_submitted.body', [
                'vacancy' => $vacancy,
                'reference' => $this->application->reference_number,
            ]))
            ->line(trans('notifications.application_submitted.closing'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'application_submitted',
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'vacancy' => $this->application->vacancy?->code,
        ];
    }
}
