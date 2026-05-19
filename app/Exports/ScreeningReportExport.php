<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScreeningReportExport implements FromCollection, WithColumnWidths, WithDefaultStyles, WithHeadings, WithStyles
{
    public function __construct(private readonly array $filters = []) {}

    public function collection(): Collection
    {
        return $this->rows();
    }

    public function headings(): array
    {
        return [
            __('messages.applicant_code'),
            __('fields.full_name'),
            __('fields.gender'),
            __('menus.vacancies'),
            __('messages.vacancy_qualification'),
            __('fields.education_level'),
            __('fields.field_of_study'),
            __('vacancies.status'),
            __('messages.screened_by'),
            __('messages.screened_date'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Applicant Code
            'B' => 26,  // Full Name
            'C' => 14,  // Gender
            'D' => 34,  // Vacancy (title + code)
            'E' => 28,  // Vacancy Qualification
            'F' => 26,  // Education Level
            'G' => 22,  // Field of Study
            'H' => 26,  // Status
            'I' => 20,  // Screened By
            'J' => 14,  // Screened Date
        ];
    }

    public function defaultStyles(Style $defaultStyle): array
    {
        return [
            'font' => ['name' => 'Ebrima', 'size' => 10],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        // Enable text wrap on the vacancy column (D) so title + code stack
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);

        return [
            1 => [
                'font' => [
                    'name' => 'Ebrima',
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1D4ED8'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
        ];
    }

    protected function rows(): Collection
    {
        $query = Application::query()
            ->with(['applicant', 'vacancy', 'screener'])
            ->when(isset($this->filters['vacancy_id']), fn ($q) => $q->where('vacancy_id', $this->filters['vacancy_id']))
            ->when(isset($this->filters['status']), fn ($q) => $q->where('status', $this->filters['status']));

        $rows = [];
        foreach ($query->get() as $app) {
            $vacTitle = trim(($app->vacancy?->title ?? '')."\n".($app->vacancy?->code ?? ''));

            $vacQual = collect([
                $app->vacancy?->field_of_study,
                $app->vacancy?->minimum_experience !== null
                    ? $app->vacancy->minimum_experience.' yrs exp'
                    : null,
            ])->filter()->implode(' · ');

            $rows[] = [
                $app->applicant?->applicant_code ?? '',
                $app->applicant?->full_name ?? '',
                $app->applicant?->gender?->getLabel() ?? '',
                $vacTitle,
                $vacQual,
                $app->applicant?->education_level?->getLabel() ?? '',
                $app->applicant?->field_of_study ?? '',
                $app->status?->getLabel() ?? '',
                $app->screener?->name ?? '',
                $app->screened_at?->format('Y-m-d') ?? '',
            ];
        }

        return collect($rows);
    }
}
