<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ExamInterviewApplicant;
use Illuminate\Support\Collection;

class ExamInterviewReportExport extends ApplicantsReportExport
{
    public function __construct(private readonly array $filters = []) {}

    public function headings(): array
    {
        return [
            'Schedule Title',
            'Type',
            'Date',
            'Venue',
            'Applicant Name',
            'Reference #',
            'Vacancy',
            'Status',
            'Score',
            'Remark',
        ];
    }

    protected function rows(): Collection
    {
        $query = ExamInterviewApplicant::query()
            ->with(['schedule.vacancy', 'application.applicant'])
            ->when(
                isset($this->filters['schedule_id']),
                fn ($q) => $q->where('schedule_id', $this->filters['schedule_id'])
            )
            ->when(
                isset($this->filters['type']),
                fn ($q) => $q->whereHas('schedule', fn ($sq) => $sq->where('type', $this->filters['type']))
            );

        $rows = [];
        foreach ($query->lazy() as $record) {
            $rows[] = [
                $record->schedule?->title ?? '',
                $record->schedule?->type?->value ?? '',
                $record->schedule?->date?->format('Y-m-d') ?? '',
                $record->schedule?->venue ?? '',
                $record->application?->applicant?->full_name ?? '',
                $record->application?->reference_number ?? '',
                $record->application?->vacancy?->code ?? '',
                $record->status,
                $record->score ?? '',
                $record->remark ?? '',
            ];
        }

        return collect($rows);
    }
}
