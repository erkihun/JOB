<?php

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VacancyDocument extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $attributes = [
        'max_size_mb' => 2,
    ];

    protected $fillable = [
        'vacancy_id',
        'document_name',
        'is_required',
        'allowed_types',
        'max_size_mb',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'allowed_types' => 'array',
            'max_size_mb' => 'integer',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function getAllowedTypesAttribute(): array
    {
        return $this->attributes['allowed_types']
            ? json_decode($this->attributes['allowed_types'], true)
            : ['pdf', 'jpg', 'jpeg', 'png'];
    }
}
