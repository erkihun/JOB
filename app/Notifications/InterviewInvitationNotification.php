<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamInterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ExamInterviewSchedule $schedule) {}

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
        $vacancy = $this->schedule->vacancy?->title ?? $this->schedule->title;

        $mail = (new MailMessage)
            ->subject(trans('notifications.interview_invitation.subject', ['vacancy' => $vacancy]))
            ->greeting(trans('notifications.interview_invitation.greeting', ['name' => $name]))
            ->line(trans('notifications.interview_invitation.body', ['vacancy' => $vacancy]))
            ->line(trans('notifications.interview_invitation.details', [
                'date' => $this->schedule->date->format('d M Y'),
                'time' => $this->schedule->start_time,
                'venue' => $this->schedule->venue,
            ]));

        if ($this->schedule->instruction) {
            $mail->line(trans('notifications.interview_invitation.instructions', ['instructions' => $this->schedule->instruction]));
        }

        return $mail->line(trans('notifications.interview_invitation.closing'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interview_invitation',
            'schedule_id' => $this->schedule->id,
            'title' => $this->schedule->title,
            'date' => $this->schedule->date->format('Y-m-d'),
            'start_time' => $this->schedule->start_time,
            'venue' => $this->schedule->venue,
        ];
    }
}
