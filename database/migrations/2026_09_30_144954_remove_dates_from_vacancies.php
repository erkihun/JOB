<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            // Never discard a period until its exact values exist on the parent.
            $unmigrated = DB::table('vacancies as v')
                ->leftJoin('recruitment_announcements as a', 'a.id', '=', 'v.announcement_id')
                ->where(function ($query): void {
                    $query->whereNull('a.opening_date')->orWhereNull('a.closing_date')
                        ->orWhereRaw('DATE(v.opening_date) <> DATE(a.opening_date)')
                        ->orWhereRaw('DATE(v.closing_date) <> DATE(a.closing_date)');
                })->exists();
            if ($unmigrated) {
                throw new RuntimeException('Vacancy dates have not been fully migrated to announcements.');
            }
            Schema::table('vacancies', function (Blueprint $table): void {
                $table->dropIndex('idx_vacancies_opening_date');
                $table->dropIndex('idx_vacancies_closing_date');
                $table->dropColumn(['opening_date', 'closing_date']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            Schema::table('vacancies', function (Blueprint $table): void {
                $table->date('opening_date')->nullable();
                $table->date('closing_date')->nullable();
                $table->index('opening_date', 'idx_vacancies_opening_date');
                $table->index('closing_date', 'idx_vacancies_closing_date');
            });
            DB::table('vacancies')->orderBy('id')->chunkById(200, function ($vacancies): void {
                foreach ($vacancies as $vacancy) {
                    $announcement = DB::table('recruitment_announcements')->find($vacancy->announcement_id);
                    DB::table('vacancies')->where('id', $vacancy->id)->update([
                        'opening_date' => $announcement->opening_date,
                        'closing_date' => $announcement->closing_date,
                    ]);
                }
            });
        });
    }
};
