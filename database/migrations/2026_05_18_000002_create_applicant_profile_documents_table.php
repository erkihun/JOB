<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_profile_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('applicant_id');
            // cv | national_id_doc | education_certificate | transcript
            // | work_experience_letter | disability_doc | other
            $table->string('document_type');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedInteger('file_size');
            $table->timestamps();

            $table->foreign('applicant_id')
                ->references('id')
                ->on('applicants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_profile_documents');
    }
};
