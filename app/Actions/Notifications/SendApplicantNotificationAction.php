<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Actions\Audit\LogAuditAction;
use App\Enums\NotificationType;
use App\Jobs\SendApplicantNotificationJob;
use App\Models\Applicant;
use App\Models\ApplicantNotification;
use App\Models\Application;

class SendApplicantNotificationAction
{
    public function __construct(
        private readonly RenderNotificationTemplateAction $renderer,
        private readonly LogAuditAction $auditLogger,
    ) {}

    public function handle(
        Applicant $applicant,
        NotificationType $type,
        array $placeholders = [],
        ?Application $application = null,
        string $channel = 'in_system',
    ): ApplicantNotification {
        $application?->loadMissing(['vacancy', 'applicant']);

        $placeholders = array_merge([
            'applicant_name' => $applicant->full_name,
            'vacancy_title' => $application?->vacancy?->title ?? $application?->vacancy?->code ?? '',
            'reference_number' => $application?->reference_number ?? '',
            'contact_information' => config('mail.from.address', ''),
            'instructions' => '',
            'message' => '',
        ], $placeholders);

        $locale = $applicant->preferred_locale
            ?? $applicant->user?->preferred_locale
            ?? app()->getLocale()
            ?? (string) config('app.locale', 'en');

        $rendered = $this->renderer->handle($type, $placeholders, $locale);

        $notification = ApplicantNotification::create([
            'applicant_id' => $applicant->id,
            'application_id' => $application?->id,
            'type' => $type,
            'channel' => $channel,
            'subject' => $rendered['subject'],
            'message' => $rendered['body'],
            'status' => 'pending',
        ]);

        SendApplicantNotificationJob::dispatch($notification);

        $this->auditLogger->handle(
            action: 'notification_sent',
            module: 'notifications',
            recordId: $notification->id,
            newValues: [
                'type' => $type->value,
                'applicant_id' => $applicant->id,
                'channel' => $channel,
            ],
        );

        return $notification;
    }

    public function resend(ApplicantNotification $notification): ApplicantNotification
    {
        if ($notification->status !== 'failed') {
            return $notification;
        }

        $notification->update(['status' => 'pending']);

        SendApplicantNotificationJob::dispatch($notification);

        $this->auditLogger->handle(
            action: 'notification_resent',
            module: 'notifications',
            recordId: $notification->id,
            newValues: ['type' => $notification->type->value],
        );

        return $notification->refresh();
    }
}
