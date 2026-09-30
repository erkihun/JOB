<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite table rebuilds must toggle foreign keys outside a transaction.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recruitment_announcements', function (Blueprint $table) {
            Schema::rename('vacancy_announcements', 'recruitment_announcements');
            Schema::table('recruitment_announcements', function (Blueprint $table): void {
                $table->string('code')->nullable()->unique();
                // Existing informational announcements have no recruitment period.
                $table->date('opening_date')->nullable()->index();
                $table->date('closing_date')->nullable()->index();
            });
            Schema::create('announcement_institution', function (Blueprint $table): void {
                $table->foreignId('announcement_id')->constrained('recruitment_announcements')->restrictOnDelete();
                $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
                $table->primary(['announcement_id', 'institution_id']);
            });
            Schema::table('vacancies', function (Blueprint $table): void {
                $table->foreignId('announcement_id')->nullable()->constrained('recruitment_announcements')->restrictOnDelete();
            });

            // There is no historical relationship to infer. Keep every exact period,
            // institution, publication state and application ID, including deleted rows.
            DB::transaction(function (): void {
                DB::table('vacancies')->orderBy('id')->chunkById(200, function ($vacancies): void {
                    foreach ($vacancies as $vacancy) {
                        $titles = json_decode($vacancy->title, true);
                        $id = DB::table('recruitment_announcements')->insertGetId([
                            'subject' => $titles['en'] ?? $vacancy->code,
                            'code' => 'LEGACY-'.$vacancy->id,
                            'content' => '',
                            'opening_date' => $vacancy->opening_date,
                            'closing_date' => $vacancy->closing_date,
                            'status' => $vacancy->status === 'draft' ? 'draft' : 'published',
                            'published_at' => $vacancy->published_at ?? ($vacancy->status === 'draft' ? null : $vacancy->created_at),
                            'created_by' => $vacancy->created_by,
                            'created_at' => $vacancy->created_at,
                            'updated_at' => $vacancy->updated_at,
                        ]);
                        DB::table('vacancies')->where('id', $vacancy->id)->update(['announcement_id' => $id]);
                        if ($vacancy->institution_id !== null) {
                            DB::table('announcement_institution')->insert([
                                'announcement_id' => $id, 'institution_id' => $vacancy->institution_id,
                            ]);
                        }
                    }
                });
            });
            Schema::table('vacancies', function (Blueprint $table): void {
                $table->unsignedBigInteger('announcement_id')->nullable(false)->change();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_announcements', function (Blueprint $table) {
            Schema::table('vacancies', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('announcement_id');
            });
            Schema::dropIfExists('announcement_institution');
            Schema::table('recruitment_announcements', function (Blueprint $table): void {
                $table->dropUnique(['code']);
                $table->dropIndex(['opening_date']);
                $table->dropIndex(['closing_date']);
                $table->dropColumn(['code', 'opening_date', 'closing_date']);
            });
            Schema::rename('recruitment_announcements', 'vacancy_announcements');
        });
    }
};
