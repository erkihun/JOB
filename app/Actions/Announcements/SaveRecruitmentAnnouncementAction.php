<?php

declare(strict_types=1);

namespace App\Actions\Announcements;

use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Services\Recruitment\RecruitmentStateMachine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaveRecruitmentAnnouncementAction
{
    public function __construct(private readonly RecruitmentStateMachine $stateMachine) {}

    /**
     * Create or edit an announcement from the admin form.
     *
     * The form only chooses between "draft" and "published"; every status change
     * goes through RecruitmentStateMachine (audited), publishing needs
     * `recruitment-announcements.publish`, and once published the application
     * period can only move through ExtendRecruitmentDeadlineAction. Announcements
     * already past "published" (closed, screening, …) keep their stage.
     */
    public function handle(RecruitmentAnnouncement $announcement, array $data, string $userId): RecruitmentAnnouncement
    {
        return DB::transaction(function () use ($announcement, $data, $userId): RecruitmentAnnouncement {
            if ($announcement->exists) {
                $announcement = RecruitmentAnnouncement::whereKey($announcement->id)->lockForUpdate()->firstOrFail();
            }
            $institutionIds = $data['institution_ids'] ?? [];
            if ($announcement->exists && $announcement->vacancies()->withTrashed()
                ->whereNotNull('institution_id')->whereNotIn('institution_id', $institutionIds)->exists()) {
                throw ValidationException::withMessages([
                    'institution_ids' => __('vacancies.announcement_institutions_in_use'),
                ]);
            }

            $current = $announcement->exists ? $announcement->lifecycleStatus() : RecruitmentStatus::Draft;
            $requested = ($data['status'] ?? 'draft') === 'published' ? RecruitmentStatus::Published : RecruitmentStatus::Draft;
            $publish = $current === RecruitmentStatus::Draft && $requested === RecruitmentStatus::Published;
            $unpublish = $current === RecruitmentStatus::Published && $requested === RecruitmentStatus::Draft;

            if (($publish || $unpublish) && ! User::find($userId)?->can('recruitment-announcements.publish')) {
                throw new AuthorizationException(__('recruitment.errors.publish_forbidden'));
            }

            $attributes = Arr::except($data, ['institution_ids', 'status', 'published_at']);
            if ($current !== RecruitmentStatus::Draft) {
                // The request already rejected changes; never let them through here either.
                unset($attributes['opening_date'], $attributes['closing_date']);
            }
            if ($current->hasStartedAssessment()) {
                unset($attributes['exam_required']);
            }

            $announcement->fill($attributes);
            $announcement->content = $data['content'] ?? '';

            // Publish time is chosen only while (re)publishing or still scheduled.
            if (in_array($current, [RecruitmentStatus::Draft, RecruitmentStatus::Published], true) && ! $unpublish) {
                $publishedAt = $data['published_at'] ?? null;
                if ($requested === RecruitmentStatus::Published && $publishedAt === null) {
                    $publishedAt = $announcement->published_at ?? now();
                }
                $announcement->published_at = $requested === RecruitmentStatus::Draft ? null : $publishedAt;
            }

            if (! $announcement->exists) {
                $announcement->created_by = $userId;
                $announcement->status = RecruitmentStatus::Draft->value;
            }
            $announcement->save();
            $announcement->institutions()->sync($institutionIds);

            if ($publish || $unpublish) {
                $this->stateMachine->transitionAnnouncement($announcement, $requested);
            }

            Cache::forget('dashboard.stats');
            Cache::forget('dashboard.vacancy_load');

            return $announcement;
        });
    }
}
