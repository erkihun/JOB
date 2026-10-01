<?php

namespace App\Models;

use App\Enums\ExamInterviewType;
use App\Models\Concerns\HasOrderedUuid;
use Carbon\CarbonImmutable;
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
        'starts_at',
        'ends_at',
        'venue',
        'instruction',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamInterviewType::class,
            'date' => 'date',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    /** Assumed length of a session whose end time was left blank (legacy rows). */
    public const DEFAULT_DURATION_MINUTES = 60;

    protected static function booted(): void
    {
        // Keep the derived time range in step with the editable date/time fields.
        static::saving(function (ExamInterviewSchedule $schedule): void {
            [$schedule->starts_at, $schedule->ends_at] = self::computeRange(
                $schedule->date?->toDateString() ?? (string) $schedule->getRawOriginal('date'),
                (string) $schedule->start_time,
                $schedule->end_time,
            );
        });
    }

    /**
     * Combine a date and wall-clock times (application timezone) into a range.
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public static function computeRange(string $date, string $startTime, ?string $endTime): array
    {
        if ($date === '' || $startTime === '') {
            return [null, null];
        }

        $day = substr($date, 0, 10);
        $tz = config('app.timezone');
        $start = CarbonImmutable::parse($day.' '.$startTime, $tz);
        $end = filled($endTime)
            ? CarbonImmutable::parse($day.' '.$endTime, $tz)
            : $start->addMinutes(self::DEFAULT_DURATION_MINUTES);

        return [$start, $end];
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
