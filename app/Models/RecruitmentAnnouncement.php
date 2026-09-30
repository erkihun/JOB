<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SanitizeHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'opening_date' => 'date',
            'closing_date' => 'date',
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

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'announcement_institution', 'announcement_id', 'institution_id');
    }

    public function isPublished(): bool
    {
        return ! $this->trashed() && $this->status === 'published'
            && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function renderableHtml(): string
    {
        return app(SanitizeHtml::class)->clean($this->content);
    }
}
