<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalResult extends Model
{
    use HasOrderedUuid;

    protected $fillable = [
        'application_id',
        'exam_score',
        'interview_score',
        'exam_weight',
        'interview_weight',
        'final_score',
        'decision',
        'remarks',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_score' => 'decimal:2',
            'interview_score' => 'decimal:2',
            'exam_weight' => 'decimal:2',
            'interview_weight' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function computeFinalScore(
        ?float $examScore,
        ?float $interviewScore,
        float $examWeight,
        float $interviewWeight,
    ): ?float {
        if ($examScore === null && $interviewScore === null) {
            return null;
        }

        $total = 0.0;
        $usedWeight = 0.0;

        if ($examScore !== null) {
            $total += $examScore * ($examWeight / 100);
            $usedWeight += $examWeight;
        }

        if ($interviewScore !== null) {
            $total += $interviewScore * ($interviewWeight / 100);
            $usedWeight += $interviewWeight;
        }

        if ($usedWeight === 0.0) {
            return null;
        }

        // Scale to full 100 if only one component is present
        return round($total * (100 / $usedWeight), 2);
    }
}
