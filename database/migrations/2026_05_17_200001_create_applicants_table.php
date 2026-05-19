<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('national_id')->unique();
            $table->string('gender'); // male, female, other
            $table->boolean('disability_status')->default(false);
            $table->string('ethnicity')->nullable();
            $table->text('address')->nullable();
            $table->integer('work_experience_months')->nullable();
            $table->string('preferred_locale')->default('en');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
