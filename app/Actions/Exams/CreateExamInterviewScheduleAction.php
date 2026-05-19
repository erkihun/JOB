<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Enums\ExamInterviewType;
use App\Models\ExamInterviewSchedule;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Auth\Access\AuthorizationException;

class CreateExamInterviewScheduleAction
{
    public function handle(
        Vacancy $vacancy,
        string $title,
        ExamInterviewType $type,
        string $date,
        string $startTime,
        ?string $endTime,
        string $venue,
        ?string $instruction,
        User $createdBy,
    ): ExamInterviewSchedule {
        $permission = $type === ExamInterviewType::Exam ? 'exams.create' : 'interviews.create';

        if (! $createdBy->hasPermissionTo($permission)) {
            throw new AuthorizationException("User does not have [{$permission}] permission.");
        }

        return ExamInterviewSchedule::create([
            'vacancy_id' => $vacancy->id,
            'title' => $title,
            'type' => $type,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue' => $venue,
            'instruction' => $instruction,
            'created_by' => $createdBy->id,
        ]);
    }
}
