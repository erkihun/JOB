<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\Concerns\HasOrderedUuid;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Vacancy extends Model
{
    use HasFactory, HasOrderedUuid, HasTranslations, SoftDeletes;

    public array $translatable = [
        'title',
        'location',
        'description',
        'qualification_requirements',
    ];

    protected $with = ['announcement'];

    protected $fillable = [
        'institution_id',
        'title',
        'code',
        'department',
        'employment_type',
        'location',
        'number_of_positions',
        'salary_grade',
        'description',
        'qualification_requirements',
        'field_of_study',
        'minimum_experience',
        'announcement_id',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => VacancyStatus::class,
            'employment_type' => EmploymentType::class,
            'published_at' => 'datetime',
            'number_of_positions' => 'integer',
            'minimum_experience' => 'integer',
        ];
    }

    public function announcement(): BelongsTo
    {
        // Keep historical dates available to applications and reports after archival.
        return $this->belongsTo(RecruitmentAnnouncement::class, 'announcement_id')->withTrashed();
    }

    public function scopeAcceptingApplications(Builder $query): Builder
    {
        return $query->where('vacancies.status', VacancyStatus::Open)
            ->whereHas('announcement', fn (Builder $announcement) => $announcement
                ->whereNull('deleted_at')->acceptingApplications());
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(VacancyDocument::class);
    }

    /** Alternative ways to qualify ("Requirement Option 1", "Option 2", …). */
    public function requirementGroups(): HasMany
    {
        return $this->hasMany(VacancyRequirementGroup::class)->orderBy('sort_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamInterviewSchedule::class);
    }

    /** Open position under an announcement that is accepting applications right now. */
    public function isOpen(): bool
    {
        return $this->status === VacancyStatus::Open
            && $this->announcement !== null
            && ! $this->announcement->trashed()
            && app(RecruitmentTimelineService::class)->isOpen($this->announcement);
    }

    /** The parent announcement's closing day has ended (or it has no period). */
    public function isPastDeadline(): bool
    {
        return $this->announcement?->closing_date === null
            || app(RecruitmentTimelineService::class)->hasApplicationPeriodEnded($this->announcement);
    }

    public function canAcceptApplications(): bool
    {
        return app(RecruitmentTimelineService::class)->canSubmitApplication($this);
    }

    /** Translation key explaining why applications are refused (null when accepted). */
    public function applicationBlockReason(): ?string
    {
        return app(RecruitmentTimelineService::class)->applicationSubmissionViolation($this);
    }
}
