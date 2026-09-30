<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin vacancy form treats these fields as optional, but the columns were
 * NOT NULL, so saving a vacancy without them failed with a database error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('department')->nullable()->change();
            $table->string('employment_type')->nullable()->change();
            $table->json('description')->nullable()->change();
            $table->json('qualification_requirements')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('department')->nullable(false)->change();
            $table->string('employment_type')->nullable(false)->change();
            $table->json('description')->nullable(false)->change();
            $table->json('qualification_requirements')->nullable(false)->change();
        });
    }
};
