<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Audit\LogAuditAction;
use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentAnnouncement;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Brings stored announcement statuses in line with their dates.
 *
 * Effective state is always computed from the dates (an announcement past its
 * closing day is treated as closed even before this runs), so the command only
 * records what already happened: it audits "opened" once and moves
 * Published → Closed once. Running it repeatedly creates no duplicate transitions.
 */
class SyncRecruitmentStatusesCommand extends Command
{
    protected $signature = 'recruitment:sync-statuses';

    protected $description = 'Record recruitment announcements that have opened or closed according to their dates';

    public function handle(RecruitmentTimelineService $timeline, RecruitmentStateMachine $stateMachine, LogAuditAction $auditLogger): int
    {
        $opened = 0;
        $closed = 0;

        RecruitmentAnnouncement::query()
            ->where('status', RecruitmentStatus::Published->value)
            ->whereNotNull('published_at')
            ->whereNotNull('closing_date')
            ->orderBy('id')
            ->each(function (RecruitmentAnnouncement $announcement) use ($timeline, $stateMachine, $auditLogger, &$opened, &$closed): void {
                try {
                    if ($timeline->hasApplicationPeriodEnded($announcement)) {
                        $stateMachine->transitionAnnouncement($announcement, RecruitmentStatus::Closed, context: 'system');
                        $closed++;

                        return;
                    }

                    if ($announcement->opened_at === null && $timeline->isOpen($announcement)) {
                        DB::transaction(function () use ($announcement, $auditLogger, $timeline, &$opened): void {
                            $updated = RecruitmentAnnouncement::whereKey($announcement->id)->whereNull('opened_at')
                                ->update(['opened_at' => $timeline->now()]);

                            if ($updated === 1) {
                                $auditLogger->handle(
                                    action: 'announcement_opened',
                                    module: 'recruitment',
                                    recordId: (string) $announcement->id,
                                    newValues: ['opening_date' => $announcement->opening_date?->toDateString(), 'context' => 'system'],
                                );
                                $opened++;
                            }
                        });
                    }
                } catch (Throwable $e) {
                    report($e);
                    $this->error("Announcement {$announcement->id}: {$e->getMessage()}");
                }
            });

        $this->info("Opened: {$opened}, closed: {$closed}.");

        return self::SUCCESS;
    }
}
