<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->string('document_name');
            $table->boolean('is_required')->default(true);
            $table->json('allowed_types')->nullable(); // ["pdf","jpg","jpeg","png"]
            $table->integer('max_size_mb')->default(2);
            $table->timestamps();

            $table->foreign('vacancy_id')->references('id')->on('vacancies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_documents');
    }
};
