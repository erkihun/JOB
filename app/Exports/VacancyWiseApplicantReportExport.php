<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Application;

class VacancyWiseApplicantReportExport extends ApplicantsReportExport
{
    public function headings(): array
    {
        return [...parent::headings(), __('vacancies.announcement_code'), __('vacancies.opening_date'), __('vacancies.closing_date')];
    }

    protected function additionalColumns(Application $application): array
    {
        $announcement = $application->vacancy?->announcement;

        return [
            $announcement?->code ?? '',
            $announcement?->opening_date?->format('Y-m-d') ?? '',
            $announcement?->closing_date?->format('Y-m-d') ?? '',
        ];
    }
}
