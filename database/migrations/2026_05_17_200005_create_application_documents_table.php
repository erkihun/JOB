<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->uuid('vacancy_document_id');
            $table->string('file_name'); // system-generated secure name
            $table->string('original_name');
            $table->string('file_path'); // private storage path
            $table->string('file_type'); // pdf, jpg, jpeg, png
            $table->bigInteger('file_size'); // bytes
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->text('verification_remark')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('vacancy_document_id')->references('id')->on('vacancy_documents')->onDelete('restrict');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
