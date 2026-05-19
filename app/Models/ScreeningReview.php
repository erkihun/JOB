<?php

namespace App\Models;

use App\Enums\ScreeningDecision;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningReview extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $fillable = [
        'application_id',
        'reviewer_id',
        'previous_status',
        'new_status',
        'decision',
        'remark',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ScreeningDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
