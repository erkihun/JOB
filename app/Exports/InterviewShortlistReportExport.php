<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ExamInterviewType;

class InterviewShortlistReportExport extends ExamInterviewReportExport
{
    public function __construct(array $filters = [])
    {
        parent::__construct(array_merge($filters, ['type' => ExamInterviewType::Interview->value]));
    }
}
