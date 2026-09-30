<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured eligibility rules for vacancies.
 *
 * A vacancy has one or more requirement groups ("Requirement Option 1", "Option 2", …).
 * All requirements inside a group must be met together (AND); an applicant is eligible
 * when ANY active group is met (OR). E.g. "Degree + 0 yrs" OR "Diploma + 2 yrs".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_requirement_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('vacancy_id')->references('id')->on('vacancies')->onDelete('cascade');
            $table->index(['vacancy_id', 'sort_order']);
        });

        Schema::create('vacancy_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_requirement_group_id');
            $table->string('education_level')->nullable();
            $table->string('field_of_study')->nullable();
            $table->unsignedSmallInteger('min_experience_years')->default(0);
            $table->decimal('min_gpa', 3, 2)->nullable();
            $table->unsignedSmallInteger('graduation_year_from')->nullable();
            $table->unsignedSmallInteger('graduation_year_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('vacancy_requirement_group_id')
                ->references('id')->on('vacancy_requirement_groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_requirements');
        Schema::dropIfExists('vacancy_requirement_groups');
    }
};
