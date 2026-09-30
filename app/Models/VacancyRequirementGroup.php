<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One alternative way to qualify for a vacancy ("Requirement Option N").
 * Every requirement in the group must be met; any one active group is enough.
 */
class VacancyRequirementGroup extends Model
{
    use HasOrderedUuid;

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'vacancy_id',
        'title',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VacancyRequirement::class)->orderBy('id'); // ordered UUIDs sort by creation time
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
