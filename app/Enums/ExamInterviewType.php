<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamInterviewType: string
{
    case Exam = 'exam';
    case Interview = 'interview';

    public function getLabel(): string
    {
        return __('statuses.exam_interview_type.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
