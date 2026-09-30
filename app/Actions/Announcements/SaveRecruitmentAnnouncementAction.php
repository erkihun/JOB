<?php

declare(strict_types=1);

namespace App\Actions\Announcements;

use App\Models\RecruitmentAnnouncement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaveRecruitmentAnnouncementAction
{
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
            $publishedAt = $data['published_at'] ?? null;
            if ($data['status'] === 'published' && $publishedAt === null) {
                $publishedAt = $announcement->published_at ?? now();
            }
            $announcement->fill(Arr::except($data, 'institution_ids'));
            $announcement->content = $data['content'] ?? '';
            $announcement->published_at = $data['status'] === 'draft' ? null : $publishedAt;
            if (! $announcement->exists) {
                $announcement->created_by = $userId;
            }
            $announcement->save();
            $announcement->institutions()->sync($institutionIds);
            Cache::forget('dashboard.stats');
            Cache::forget('dashboard.vacancy_load');

            return $announcement;
        });
    }
}
