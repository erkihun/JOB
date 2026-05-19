<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('applicant_code')->nullable()->unique()->after('user_id');
        });

        // Backfill existing rows with sequential codes
        $seq = 1;
        $year = date('Y');
        DB::table('applicants')
            ->whereNull('applicant_code')
            ->orderBy('created_at')
            ->each(function ($row) use (&$seq, $year) {
                DB::table('applicants')
                    ->where('id', $row->id)
                    ->update(['applicant_code' => 'APL-'.$year.'-'.str_pad((string) $seq++, 5, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('applicant_code');
        });
    }
};
