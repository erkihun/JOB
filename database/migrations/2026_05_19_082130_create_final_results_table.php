<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id')->unique();
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->decimal('exam_score', 5, 2)->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->decimal('exam_weight', 5, 2)->default(60);
            $table->decimal('interview_weight', 5, 2)->default(40);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('decision')->nullable();
            $table->text('remarks')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_results');
    }
};
