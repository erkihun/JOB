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
        'practical_score',
        'exam_weight',
        'interview_weight',
        'practical_weight',
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
            'practical_score' => 'decimal:2',
            'exam_weight' => 'decimal:2',
            'interview_weight' => 'decimal:2',
            'practical_weight' => 'decimal:2',
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

    /**
     * Weighted final score out of 100 from exam, interview and practical test scores.
     * Components without a score are left out and the remaining weights are scaled
     * back up to 100, so a missing component never counts as zero.
     */
    public static function computeFinalScore(
        ?float $examScore,
        ?float $interviewScore,
        float $examWeight,
        float $interviewWeight,
        ?float $practicalScore = null,
        float $practicalWeight = 0.0,
    ): ?float {
        $total = 0.0;
        $usedWeight = 0.0;

        foreach ([[$examScore, $examWeight], [$interviewScore, $interviewWeight], [$practicalScore, $practicalWeight]] as [$score, $weight]) {
            if ($score === null || $weight <= 0) {
                continue;
            }
            $total += $score * ($weight / 100);
            $usedWeight += $weight;
        }

        if ($usedWeight === 0.0) {
            return null;
        }

        return round($total * (100 / $usedWeight), 2);
    }
}
