<?php

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamInterviewApplicant extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $table = 'exam_interview_applicants';

    protected $fillable = [
        'schedule_id',
        'application_id',
        'status',
        'score',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamInterviewSchedule::class, 'schedule_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
