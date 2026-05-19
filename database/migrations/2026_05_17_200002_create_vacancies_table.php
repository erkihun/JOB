<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title'); // translatable: {"en": "...", "am": "..."}
            $table->string('code')->unique();
            $table->string('department');
            $table->string('employment_type'); // permanent, contract, temporary, internship
            $table->json('location'); // translatable
            $table->integer('number_of_positions');
            $table->string('salary_grade')->nullable();
            $table->json('description'); // translatable
            $table->json('qualification_requirements'); // translatable
            $table->string('field_of_study')->nullable();
            $table->integer('minimum_experience')->nullable()->comment('in months');
            $table->date('opening_date');
            $table->date('closing_date');
            $table->string('status')->default('draft'); // draft, open, closed, screening, exam_stage, interview_stage, finalized, cancelled
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
