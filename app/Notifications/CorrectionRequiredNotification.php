<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectionRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Application $application,
        private readonly ?string $remark = null,
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

        $message = (new MailMessage)
            ->subject(trans('notifications.correction_required.subject', ['vacancy' => $vacancy]))
            ->greeting(trans('notifications.correction_required.greeting', ['name' => $name]))
            ->line(trans('notifications.correction_required.body', ['vacancy' => $vacancy]));

        if ($this->remark !== null) {
            $message->line(trans('notifications.correction_required.remark', ['remark' => $this->remark]));
        }

        return $message->line(trans('notifications.correction_required.closing'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'correction_required',
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'remark' => $this->remark,
        ];
    }
}
