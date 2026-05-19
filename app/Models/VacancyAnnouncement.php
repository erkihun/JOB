<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SanitizeHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacancyAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject',
        'content',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function renderableHtml(): string
    {
        return app(SanitizeHtml::class)->clean($this->content);
    }
}
