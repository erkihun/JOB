<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicantsReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly array $filters = []) {}

    public function collection(): Collection
    {
        return $this->rows();
    }

    public function headings(): array
    {
        return [
            'Reference #',
            'Applicant Name',
            'Phone',
            'Email',
            'Gender',
            'National ID',
            'Vacancy',
            'Field of Study',
            'Graduation Date',
            'CGPA',
            'Status',
            'Submitted At',
        ];
    }

    public function toCsv(): string
    {
        return $this->buildCsv($this->headings(), $this->rows()->all());
    }

    protected function rows(): Collection
    {
        $query = Application::query()
            ->with(['applicant', 'vacancy'])
            ->when(isset($this->filters['vacancy_id']), fn ($q) => $q->where('vacancy_id', $this->filters['vacancy_id']))
            ->when(isset($this->filters['status']), fn ($q) => $q->where('status', $this->filters['status']));

        $rows = [];
        foreach ($query->lazy() as $application) {
            $rows[] = [
                $application->reference_number,
                $application->applicant?->full_name ?? '',
                $application->applicant?->phone ?? '',
                $application->applicant?->email ?? '',
                $application->applicant?->gender?->value ?? '',
                $application->applicant?->national_id ?? '',
                $application->vacancy?->code ?? '',
                $application->field_of_study ?? '',
                $application->graduation_date?->format('Y-m-d') ?? '',
                $application->cgpa ?? '',
                $application->status?->value ?? '',
                $application->submitted_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        return collect($rows);
    }

    private function buildCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://memory', 'r+');

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }
}
