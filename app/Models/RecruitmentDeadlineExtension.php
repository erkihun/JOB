<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One closing-date extension of a recruitment announcement. Append-only: rows
 * are written by ExtendRecruitmentDeadlineAction and never edited or removed.
 */
class RecruitmentDeadlineExtension extends Model
{
    protected $fillable = [
        'announcement_id',
        'old_closing_date',
        'new_closing_date',
        'reason',
        'reference',
        'extended_by',
        'extended_at',
        'notified_count',
    ];

    protected function casts(): array
    {
        return [
            'old_closing_date' => 'date',
            'new_closing_date' => 'date',
            'extended_at' => 'datetime',
            'notified_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (RecruitmentDeadlineExtension $extension): void {
            // Only the notification counter may be filled in after the fact.
            if (array_diff(array_keys($extension->getDirty()), ['notified_count', 'updated_at']) !== []) {
                throw new LogicException('Deadline extension history is immutable.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Deadline extension history is immutable.');
        });
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(RecruitmentAnnouncement::class, 'announcement_id')->withTrashed();
    }

    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
