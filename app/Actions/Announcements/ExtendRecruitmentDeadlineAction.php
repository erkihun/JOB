<?php

declare(strict_types=1);

namespace App\Actions\Announcements;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Jobs\NotifyDeadlineExtensionJob;
use App\Models\Application;
use App\Models\RecruitmentAnnouncement;
use App\Models\RecruitmentDeadlineExtension;
use App\Models\User;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The only way to move the closing date of a published announcement.
 *
 * - requires `recruitment-announcements.extend-deadline`, a reason, and a later date
 * - blocked once screening, exams or interviews have started (no reopening workflow)
 * - a Closed announcement becomes Published (accepting) again until the new date
 * - submitted applications are untouched; the previous deadline is kept in
 *   recruitment_deadline_extensions and in the audit log
 * - applicants of the announcement are notified through the queue
 */
final class ExtendRecruitmentDeadlineAction
{
    public function __construct(
        private readonly RecruitmentTimelineService $timeline,
        private readonly RecruitmentStateMachine $stateMachine,
        private readonly LogAuditAction $auditLogger,
    ) {}

    public function handle(
        RecruitmentAnnouncement $announcement,
        User $user,
        string $newClosingDate,
        string $reason,
        ?string $reference = null,
    ): RecruitmentDeadlineExtension {
        if (! $user->can('recruitment-announcements.extend-deadline')) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw RecruitmentRuleException::withMessages(['reason' => [__('validation.required', ['attribute' => __('recruitment.reason')])]]);
        }

        $extension = DB::transaction(function () use ($announcement, $user, $newClosingDate, $reason, $reference): RecruitmentDeadlineExtension {
            $locked = RecruitmentAnnouncement::whereKey($announcement->getKey())->lockForUpdate()->firstOrFail();

            if ($key = $this->timeline->deadlineExtensionViolation($locked)) {
                throw RecruitmentRuleException::because($key, 'new_closing_date');
            }

            $tz = $this->timeline->timezone();
            $old = CarbonImmutable::parse($locked->closing_date->toDateString(), $tz);
            $new = CarbonImmutable::parse($newClosingDate, $tz)->startOfDay();

            if ($new->lte($old)) {
                throw RecruitmentRuleException::because('recruitment.errors.extension_not_later', 'new_closing_date');
            }

            if ($new->lt($this->timeline->now()->startOfDay())) {
                throw RecruitmentRuleException::because('recruitment.errors.extension_in_past', 'new_closing_date');
            }

            $locked->closing_date = $new->toDateString();
            $locked->save();

            // A closed announcement accepts applications again until the new date.
            if ($locked->lifecycleStatus() === RecruitmentStatus::Closed) {
                $this->stateMachine->transitionAnnouncement($locked, RecruitmentStatus::Published, $reason, 'deadline_extension');
            }

            $extension = RecruitmentDeadlineExtension::create([
                'announcement_id' => $locked->id,
                'old_closing_date' => $old->toDateString(),
                'new_closing_date' => $new->toDateString(),
                'reason' => $reason,
                'reference' => filled($reference) ? trim((string) $reference) : null,
                'extended_by' => $user->id,
                'extended_at' => $this->timeline->now(),
                'notified_count' => $this->applicantIdsFor($locked)->count(),
            ]);

            $this->auditLogger->handle(
                action: 'deadline_extended',
                module: 'recruitment',
                recordId: (string) $locked->id,
                oldValues: ['closing_date' => $old->toDateString()],
                newValues: array_filter([
                    'closing_date' => $new->toDateString(),
                    'reason' => $reason,
                    'reference' => $extension->reference,
                    'extension_id' => $extension->id,
                ], fn ($v) => $v !== null),
            );

            Cache::forget('dashboard.stats');
            Cache::forget('dashboard.vacancy_load');

            return $extension;
        });

        $announcement->refresh();

        if ($extension->notified_count > 0) {
            NotifyDeadlineExtensionJob::dispatch($extension->id)->afterCommit();
        }

        return $extension;
    }

    /** Applicants with a live (not withdrawn) application under the announcement. */
    public function applicantIdsFor(RecruitmentAnnouncement $announcement): Collection
    {
        return Application::query()
            ->whereIn('vacancy_id', $announcement->vacancies()->select('id'))
            ->where('status', '!=', ApplicationStatus::Withdrawn->value)
            ->distinct()
            ->pluck('applicant_id');
    }
}
