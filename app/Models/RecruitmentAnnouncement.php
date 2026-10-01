<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecruitmentStage;
use App\Enums\RecruitmentStatus;
use App\Services\Recruitment\RecruitmentTimelineService;
use App\Services\SanitizeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentAnnouncement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_announcements';

    protected $fillable = [
        'subject',
        'code',
        'opening_date',
        'closing_date',
        'content',
        'status',
        'exam_required',
        'published_at',
        'opened_at',
        'closed_at',
        'finalized_at',
        'cancelled_at',
        'created_by',
    ];

    /**
     * `status` must only change through RecruitmentStateMachine; it stays a plain
     * string column (values of RecruitmentStatus) for backward compatibility.
     */
    protected $attributes = [
        'status' => 'draft',
        'exam_required' => true,
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'finalized_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'opening_date' => 'date',
            'closing_date' => 'date',
            'exam_required' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string, string>
     */
    protected function content(): Attribute
    {
        return Attribute::set(
            fn (?string $value): string => app(SanitizeHtml::class)->clean($value),
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class, 'announcement_id');
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, Vacancy::class, 'announcement_id', 'vacancy_id');
    }

    public function deadlineExtensions(): HasMany
    {
        return $this->hasMany(RecruitmentDeadlineExtension::class, 'announcement_id')->latest('extended_at');
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'announcement_institution', 'announcement_id', 'institution_id');
    }

    public function lifecycleStatus(): RecruitmentStatus
    {
        return RecruitmentStatus::tryFrom((string) $this->status) ?? RecruitmentStatus::Draft;
    }

    /** Effective stage (Upcoming / Open / Closed / …) — see RecruitmentTimelineService. */
    public function stage(): RecruitmentStage
    {
        return app(RecruitmentTimelineService::class)->stage($this);
    }

    /**
     * Released to the public: any post-draft, non-cancelled status whose publish
     * time has arrived. Closed and later announcements stay visible so applicants
     * can still see what they applied to.
     */
    public function isPublished(): bool
    {
        return ! $this->trashed() && $this->lifecycleStatus()->isPubliclyVisible()
            && $this->published_at !== null && $this->published_at->lte(now());
    }

    /** @param  Builder<RecruitmentAnnouncement>  $query */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', RecruitmentStatus::publicValues())
            ->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * Announcements currently accepting applications: published, released, and
     * today within [opening_date, closing_date] (date-only values, so the whole
     * closing day is included). Mirrors RecruitmentTimelineService::isOpen().
     *
     * @param  Builder<RecruitmentAnnouncement>  $query
     */
    public function scopeAcceptingApplications(Builder $query): Builder
    {
        $today = app(RecruitmentTimelineService::class)->now()->toDateString();

        return $query->where('status', RecruitmentStatus::Published->value)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today);
    }

    public function renderableHtml(): string
    {
        return app(SanitizeHtml::class)->clean($this->content);
    }
}
