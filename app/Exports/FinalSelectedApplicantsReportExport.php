<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ApplicationStatus;

class FinalSelectedApplicantsReportExport extends ApplicantsReportExport
{
    public function __construct(array $filters = [])
    {
        parent::__construct(array_merge($filters, ['status' => ApplicationStatus::Selected->value]));
    }
}
