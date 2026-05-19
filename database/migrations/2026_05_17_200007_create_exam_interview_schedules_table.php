<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_interview_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->string('title');
            $table->string('type'); // exam, interview
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('venue');
            $table->text('instruction')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('vacancy_id')->references('id')->on('vacancies')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('exam_interview_applicants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id');
            $table->uuid('application_id');
            $table->string('status')->default('invited'); // invited, attended, absent, passed, failed
            $table->decimal('score', 5, 2)->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'application_id']);
            $table->foreign('schedule_id')->references('id')->on('exam_interview_schedules')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_interview_applicants');
        Schema::dropIfExists('exam_interview_schedules');
    }
};
