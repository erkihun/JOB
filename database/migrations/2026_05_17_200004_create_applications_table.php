<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('applicant_id');
            $table->uuid('vacancy_id');
            $table->string('reference_number')->unique();
            $table->string('field_of_study');
            $table->date('graduation_date');
            $table->decimal('cgpa', 4, 2)->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('locked_at')->nullable();

            // Screening fields
            $table->string('screening_status')->nullable(); // pending, passed, failed, correction_required
            $table->text('screening_remark')->nullable();
            $table->uuid('screened_by')->nullable();
            $table->timestamp('screened_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['applicant_id', 'vacancy_id']); // one application per vacancy

            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('restrict');
            $table->foreign('vacancy_id')->references('id')->on('vacancies')->onDelete('restrict');
            $table->foreign('screened_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
