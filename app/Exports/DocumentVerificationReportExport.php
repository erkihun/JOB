<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ApplicationDocument;
use Illuminate\Support\Collection;

class DocumentVerificationReportExport extends ApplicantsReportExport
{
    public function headings(): array
    {
        return [
            'Reference #',
            'Applicant Name',
            'Document',
            'Verification Status',
            'Remark',
            'Verified At',
        ];
    }

    protected function rows(): Collection
    {
        return ApplicationDocument::query()
            ->with(['application.applicant', 'vacancyDocument'])
            ->lazy()
            ->map(fn (ApplicationDocument $document): array => [
                $document->application?->reference_number ?? '',
                $document->application?->applicant?->full_name ?? '',
                $document->vacancyDocument?->document_name ?? '',
                $document->verification_status?->value ?? '',
                $document->verification_remark ?? '',
                $document->verified_at?->format('Y-m-d H:i:s') ?? '',
            ])
            ->collect();
    }
}
