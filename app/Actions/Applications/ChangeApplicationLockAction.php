<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Audit\LogAuditAction;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Application;
use App\Models\User;
use App\Services\Recruitment\RecruitmentTimelineService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Administrative lock / time-boxed reopening of one application.
 *
 * Reopening never happens silently: it needs `applications.unlock`, a reason and
 * an expiry. Once the expiry passes the application is read-only again with no
 * further action (RecruitmentTimelineService checks `reopened_until`).
 */
final class ChangeApplicationLockAction
{
    public const MAX_REOPEN_DAYS = 14;

    public function __construct(
        private readonly RecruitmentTimelineService $timeline,
        private readonly LogAuditAction $auditLogger,
    ) {}

    public function lock(Application $application, User $user, string $reason): Application
    {
        if (! $user->can('applications.lock')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($application, $user, $reason): Application {
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $old = ['locked_at' => $locked->locked_at?->toDateTimeString(), 'reopened_until' => $locked->reopened_until?->toDateTimeString()];

            $locked->forceFill([
                'locked_at' => $this->timeline->now(),
                'locked_by' => $user->id,
                'lock_reason' => trim($reason),
                'reopened_until' => null,
            ])->save();

            $this->auditLogger->handle(
                action: 'application_locked',
                module: 'applications',
                recordId: $locked->id,
                oldValues: $old,
                newValues: ['locked_at' => $locked->locked_at->toDateTimeString(), 'reason' => trim($reason)],
            );

            return $locked;
        });
    }

    public function reopen(Application $application, User $user, string $until, string $reason): Application
    {
        if (! $user->can('applications.unlock')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($application, $user, $until, $reason): Application {
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $locked->load('vacancy');
            $announcement = $locked->vacancy?->announcement;

            // Only after the deadline, before a screening decision, while the recruitment is live.
            if ($announcement === null || $announcement->lifecycleStatus()->isTerminal()
                || ! $this->timeline->hasApplicationPeriodEnded($announcement)
                || ! in_array($locked->status, RecruitmentTimelineService::AWAITING_SCREENING_STATUSES, true)) {
                throw RecruitmentRuleException::because('recruitment.errors.reopen_not_allowed', 'reopened_until');
            }

            $now = $this->timeline->now();
            $expiry = CarbonImmutable::parse($until, $this->timeline->timezone())->endOfDay();
            if ($expiry->lte($now) || $expiry->gt($now->addDays(self::MAX_REOPEN_DAYS)->endOfDay())) {
                throw RecruitmentRuleException::because('recruitment.errors.reopen_window', 'reopened_until', ['days' => self::MAX_REOPEN_DAYS]);
            }

            $old = ['locked_at' => $locked->locked_at?->toDateTimeString(), 'reopened_until' => $locked->reopened_until?->toDateTimeString()];

            $locked->forceFill([
                'locked_at' => null,
                'locked_by' => null,
                'lock_reason' => null,
                'reopened_until' => $expiry,
                'reopened_by' => $user->id,
                'reopen_reason' => trim($reason),
            ])->save();

            $this->auditLogger->handle(
                action: 'application_reopened',
                module: 'applications',
                recordId: $locked->id,
                oldValues: $old,
                newValues: ['reopened_until' => $expiry->toDateTimeString(), 'reason' => trim($reason), 'relocks_automatically' => true],
            );

            return $locked;
        });
    }
}
