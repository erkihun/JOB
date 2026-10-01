<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ethnicity is no longer collected (not needed for recruitment decisions and
     * sensitive personal data), so the column and any stored values are removed.
     */
    public function up(): void
    {
        if (Schema::hasColumn('applicants', 'ethnicity')) {
            Schema::table('applicants', function (Blueprint $table): void {
                $table->dropColumn('ethnicity');
            });
        }
    }

    public function down(): void
    {
        // Restores the column only; removed values cannot be recovered.
        Schema::table('applicants', function (Blueprint $table): void {
            $table->string('ethnicity')->nullable()->after('disability_status');
        });
    }
};
