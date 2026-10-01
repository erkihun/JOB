<?php

declare(strict_types=1);

use App\Models\ExamInterviewSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * starts_at / ends_at are derived from date + start_time + end_time (kept in
     * sync by the model) so overlap checks can be done with simple range queries.
     */
    public function up(): void
    {
        Schema::table('exam_interview_schedules', function (Blueprint $table): void {
            $table->dateTime('starts_at')->nullable()->after('end_time');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->index(['starts_at', 'ends_at'], 'idx_schedules_time_range');
        });

        DB::table('exam_interview_schedules')->orderBy('id')->chunk(200, function ($schedules): void {
            foreach ($schedules as $schedule) {
                [$startsAt, $endsAt] = ExamInterviewSchedule::computeRange(
                    (string) $schedule->date, (string) $schedule->start_time, $schedule->end_time,
                );
                DB::table('exam_interview_schedules')->where('id', $schedule->id)->update([
                    'starts_at' => $startsAt?->format('Y-m-d H:i:s'),
                    'ends_at' => $endsAt?->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_interview_schedules', function (Blueprint $table): void {
            $table->dropIndex('idx_schedules_time_range');
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
