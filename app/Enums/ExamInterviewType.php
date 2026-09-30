<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamInterviewType: string
{
    case Exam = 'exam';
    case Interview = 'interview';
    case Practical = 'practical';

    public function getLabel(): string
    {
        return __('statuses.exam_interview_type.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * A practical test is a kind of exam: it uses the exam permissions and the
     * exam invitation template.
     */
    public function isExamLike(): bool
    {
        return $this !== self::Interview;
    }
}
