<?php

namespace App\Models;

use App\Enums\ExamInterviewType;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamInterviewSchedule extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $table = 'exam_interview_schedules';

    protected $fillable = [
        'vacancy_id',
        'title',
        'type',
        'date',
        'start_time',
        'end_time',
        'venue',
        'instruction',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamInterviewType::class,
            'date' => 'date',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedApplicants(): HasMany
    {
        return $this->hasMany(ExamInterviewApplicant::class, 'schedule_id');
    }
}
