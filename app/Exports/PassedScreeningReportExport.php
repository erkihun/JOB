<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ApplicationStatus;

class PassedScreeningReportExport extends ScreeningReportExport
{
    public function __construct(array $filters = [])
    {
        parent::__construct(array_merge($filters, ['status' => ApplicationStatus::PassedScreening->value]));
    }
}
