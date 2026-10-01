<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ExamInterviewType;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\ExamInterviewSchedule;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates, moves and cancels exam / practical / interview sessions. All timeline
 * rules (after the closing date, after screening, interview after exam, not in
 * the past, end after start, no applicant double-booking) are enforced here.
 */
class CreateExamInterviewScheduleAction
{
    public function __construct(
        private readonly RecruitmentTimelineService $timeline,
        private readonly RecruitmentStateMachine $stateMachine,
        private readonly LogAuditAction $auditLogger,
    ) {}

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
        $this->authorize($createdBy, $type, 'create');

        return DB::transaction(function () use ($vacancy, $title, $type, $date, $startTime, $endTime, $venue, $instruction, $createdBy): ExamInterviewSchedule {
            $this->lockAnnouncement($vacancy);
            [$startsAt, $endsAt] = $this->range($date, $startTime, $endTime);
            $this->timeline->assertCanSchedule($vacancy, $type, $startsAt, $endsAt);

            $schedule = ExamInterviewSchedule::create([
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

            $this->auditLogger->handle(
                action: $type->value.'_created',
                module: 'exam_interview',
                recordId: $schedule->id,
                newValues: $this->auditValues($schedule),
            );

            $this->advanceStage($vacancy, $type);

            return $schedule;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated title/type/date/start_time/end_time/venue/instruction/vacancy_id
     */
    public function update(ExamInterviewSchedule $schedule, array $data, User $user): ExamInterviewSchedule
    {
        $type = $data['type'] instanceof ExamInterviewType ? $data['type'] : ExamInterviewType::from((string) $data['type']);
        $this->authorize($user, $schedule->type, 'update');
        $this->authorize($user, $type, 'update');

        return DB::transaction(function () use ($schedule, $data, $type): ExamInterviewSchedule {
            $schedule = ExamInterviewSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            $vacancy = Vacancy::findOrFail($data['vacancy_id'] ?? $schedule->vacancy_id);
            $this->lockAnnouncement($vacancy);

            $hasInvitations = $schedule->assignedApplicants()->exists();
            if ($hasInvitations && ($vacancy->id !== $schedule->vacancy_id || $type !== $schedule->type)) {
                // People were invited to this session for this position and stage.
                throw RecruitmentRuleException::because('recruitment.errors.stage_not_allowed', 'type');
            }

            [$startsAt, $endsAt] = $this->range($data['date'], $data['start_time'], $data['end_time'] ?? null);
            $timeChanged = $schedule->starts_at === null || ! $schedule->starts_at->equalTo($startsAt)
                || $schedule->ends_at === null || ! $schedule->ends_at->equalTo($endsAt)
                || $vacancy->id !== $schedule->vacancy_id || $type !== $schedule->type;

            if ($timeChanged) {
                $this->timeline->assertCanSchedule($vacancy, $type, $startsAt, $endsAt, $schedule);

                if ($key = $this->timeline->rescheduleConflictViolation($schedule, $startsAt, $endsAt)) {
                    throw RecruitmentRuleException::because($key, 'date');
                }
            }

            $old = $this->auditValues($schedule);
            $schedule->update([...$data, 'type' => $type, 'vacancy_id' => $vacancy->id]);

            $this->auditLogger->handle(
                action: $type->value.'_changed',
                module: 'exam_interview',
                recordId: $schedule->id,
                oldValues: $old,
                newValues: $this->auditValues($schedule),
            );

            return $schedule;
        });
    }

    public function cancel(ExamInterviewSchedule $schedule, User $user): void
    {
        $this->authorize($user, $schedule->type, 'delete');

        DB::transaction(function () use ($schedule): void {
            $schedule = ExamInterviewSchedule::with('vacancy')->whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            if ($schedule->vacancy?->announcement?->lifecycleStatus() === RecruitmentStatus::Finalized) {
                throw RecruitmentRuleException::because('recruitment.errors.stage_not_allowed', 'schedule');
            }

            $values = $this->auditValues($schedule) + ['invited' => $schedule->assignedApplicants()->count()];
            $schedule->delete();

            $this->auditLogger->handle(
                action: $schedule->type->value.'_cancelled',
                module: 'exam_interview',
                recordId: $schedule->id,
                oldValues: $values,
            );
        });
    }

    private function authorize(User $user, ExamInterviewType $type, string $ability): void
    {
        $permission = ($type->isExamLike() ? 'exams.' : 'interviews.').$ability;

        if (! $user->can($permission)) {
            throw new AuthorizationException("User does not have [{$permission}] permission.");
        }
    }

    /** Serialize concurrent schedule/stage changes for the same recruitment. */
    private function lockAnnouncement(Vacancy $vacancy): void
    {
        $vacancy->setRelation('announcement', RecruitmentAnnouncement::withTrashed()
            ->whereKey($vacancy->announcement_id)->lockForUpdate()->first());
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(string $date, string $startTime, ?string $endTime): array
    {
        [$startsAt, $endsAt] = ExamInterviewSchedule::computeRange($date, $startTime, $endTime);

        if ($endsAt->lte($startsAt)) {
            throw RecruitmentRuleException::because('recruitment.errors.schedule_end_before_start', 'end_time');
        }

        return [$startsAt, $endsAt];
    }

    private function advanceStage(Vacancy $vacancy, ExamInterviewType $type): void
    {
        $target = match ($type) {
            ExamInterviewType::Exam => RecruitmentStatus::Exam,
            ExamInterviewType::Interview => RecruitmentStatus::Interview,
            ExamInterviewType::Practical => null,
        };

        if ($target !== null && $vacancy->announcement !== null) {
            $this->stateMachine->advanceTo($vacancy->announcement, $target);
        }
    }

    /** @return array<string, mixed> */
    private function auditValues(ExamInterviewSchedule $schedule): array
    {
        return [
            'vacancy_id' => $schedule->vacancy_id,
            'type' => $schedule->type->value,
            'title' => $schedule->title,
            'starts_at' => $schedule->starts_at?->toDateTimeString(),
            'ends_at' => $schedule->ends_at?->toDateTimeString(),
            'venue' => $schedule->venue,
        ];
    }
}
