<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationType;
use App\Models\NotificationTemplate;

class RenderNotificationTemplateAction
{
    /**
     * @param  array<string, mixed>  $placeholders
     */
    public function handle(
        NotificationType $type,
        array $placeholders = [],
        string $locale = 'en',
    ): array {
        $template = NotificationTemplate::findForType($type, $locale);

        if ($template === null) {
            return [
                'subject' => $this->defaultSubject($type, $placeholders, $locale),
                'body' => $this->defaultBody($type, $placeholders, $locale),
            ];
        }

        return [
            'subject' => $this->replacePlaceholders($template->subject, $placeholders),
            'body' => $this->replacePlaceholders($template->body, $placeholders),
        ];
    }

    private function replacePlaceholders(string $text, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $text = str_replace('{{ '.$key.' }}', (string) $value, $text);
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }

        return $text;
    }

    private function defaultSubject(NotificationType $type, array $placeholders, string $locale): string
    {
        $vacancy = $placeholders['vacancy_title'] ?? '';
        $key = $type->value;

        $transKey = "notifications.{$key}.subject";

        if (trans()->has($transKey, $locale)) {
            return trans($transKey, ['vacancy' => $vacancy], $locale);
        }

        return $type->label();
    }

    private function defaultBody(NotificationType $type, array $placeholders, string $locale): string
    {
        $name = $placeholders['applicant_name'] ?? '';
        $vacancy = $placeholders['vacancy_title'] ?? '';
        $reference = $placeholders['reference_number'] ?? '';
        $date = $placeholders['date'] ?? '';
        $time = $placeholders['time'] ?? '';
        $venue = $placeholders['venue'] ?? '';
        $instr = $placeholders['instructions'] ?? '';
        $message = $placeholders['message'] ?? '';
        $remark = $placeholders['remark'] ?? '';

        $t = fn (string $key, array $params = []) => trans("notifications.{$key}", $params, $locale);

        $parts = match ($type) {
            NotificationType::ApplicationSubmitted => [
                $t("{$type->value}.greeting", ['name' => $name]),
                $t("{$type->value}.body", ['vacancy' => $vacancy, 'reference' => $reference]),
                $t("{$type->value}.closing"),
            ],
            NotificationType::CorrectionRequired => array_filter([
                $t("{$type->value}.greeting", ['name' => $name]),
                $t("{$type->value}.body", ['vacancy' => $vacancy]),
                $remark ? $t("{$type->value}.remark", ['remark' => $remark]) : null,
                $t("{$type->value}.closing"),
            ]),
            NotificationType::ScreeningPassed,
            NotificationType::ScreeningFailed => [
                $t("{$type->value}.greeting", ['name' => $name]),
                $t("{$type->value}.body", ['vacancy' => $vacancy]),
                $t("{$type->value}.closing"),
            ],
            NotificationType::ExamInvitation,
            NotificationType::InterviewInvitation => array_filter([
                $t("{$type->value}.greeting", ['name' => $name]),
                $t("{$type->value}.body", ['vacancy' => $vacancy]),
                $t("{$type->value}.details", ['date' => $date, 'time' => $time, 'venue' => $venue]),
                $instr ? $t("{$type->value}.instructions", ['instructions' => $instr]) : null,
                $t("{$type->value}.closing"),
            ]),
            NotificationType::Selected,
            NotificationType::Waitlisted,
            NotificationType::NotSelected => array_filter([
                $t("{$type->value}.greeting", ['name' => $name]),
                $t("{$type->value}.body", ['vacancy' => $vacancy]),
                $message ? $message : null,
                $t("{$type->value}.closing"),
            ]),
            NotificationType::General => [$message],
        };

        return implode("\n\n", array_values($parts));
    }
}
