<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a third scored component, the practical test, to final results.
 * Existing results keep a 0% practical weight, so their final scores are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_results', function (Blueprint $table) {
            $table->decimal('practical_score', 5, 2)->nullable()->after('interview_score');
            $table->decimal('practical_weight', 5, 2)->default(0)->after('interview_weight');
        });
    }

    public function down(): void
    {
        Schema::table('final_results', function (Blueprint $table) {
            $table->dropColumn(['practical_score', 'practical_weight']);
        });
    }
};
