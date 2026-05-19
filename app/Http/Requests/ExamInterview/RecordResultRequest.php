<?php

declare(strict_types=1);

namespace App\Http\Requests\ExamInterview;

use App\Enums\ExamInterviewType;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExamInterviewSchedule|null $schedule */
        $schedule = $this->route('schedule');

        /** @var ExamInterviewApplicant|null $applicantRecord */
        $applicantRecord = $this->route('applicantRecord');

        if (! $schedule || ! $applicantRecord || $applicantRecord->schedule_id !== $schedule->id) {
            return false;
        }

        $permission = $schedule->type === ExamInterviewType::Exam
            ? 'exams.record-results'
            : 'interviews.record-results';

        return (bool) $this->user()?->hasAnyRole(['super_admin', 'admin', 'hr_manager'])
            || (bool) $this->user()?->hasPermissionTo($permission);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['invited', 'attended', 'absent', 'passed', 'failed'])],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'remark' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
