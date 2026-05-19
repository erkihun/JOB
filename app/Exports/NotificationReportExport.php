<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ApplicantNotification;
use Illuminate\Support\Collection;

class NotificationReportExport extends ApplicantsReportExport
{
    public function headings(): array
    {
        return [
            'Applicant',
            'Reference #',
            'Type',
            'Channel',
            'Subject',
            'Status',
            'Sent At',
        ];
    }

    protected function rows(): Collection
    {
        return ApplicantNotification::query()
            ->with(['applicant', 'application'])
            ->lazy()
            ->map(fn (ApplicantNotification $notification): array => [
                $notification->applicant?->full_name ?? '',
                $notification->application?->reference_number ?? '',
                $notification->type?->value ?? '',
                $notification->channel,
                $notification->subject ?? '',
                $notification->status,
                $notification->sent_at?->format('Y-m-d H:i:s') ?? '',
            ])
            ->collect();
    }
}
