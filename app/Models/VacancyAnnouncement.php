<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SanitizeHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacancyAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject',
        'content',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
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

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function renderableHtml(): string
    {
        return app(SanitizeHtml::class)->clean($this->content);
    }
}
