<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite rebuilds the table to drop the enum CHECK constraint; that must run
    // outside a transaction so foreign keys can be toggled.
    public $withinTransaction = false;

    public function up(): void
    {
        // `status` was enum('draft','published'); the lifecycle needs more values
        // (closed, screening, exam, interview, finalized, cancelled).
        Schema::table('recruitment_announcements', function (Blueprint $table): void {
            $table->string('status', 20)->default('draft')->change();
        });

        Schema::table('recruitment_announcements', function (Blueprint $table): void {
            // Whether applicants must pass a written exam before an interview.
            $table->boolean('exam_required')->default(true)->after('status');
            // Set once by `recruitment:sync-statuses` when the window first opens / closes.
            $table->timestamp('opened_at')->nullable()->after('published_at');
            $table->timestamp('closed_at')->nullable()->after('opened_at');
            $table->timestamp('finalized_at')->nullable()->after('closed_at');
            $table->timestamp('cancelled_at')->nullable()->after('finalized_at');
            $table->index('status', 'idx_recruitment_announcements_status');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_announcements', function (Blueprint $table): void {
            $table->dropIndex('idx_recruitment_announcements_status');
            $table->dropColumn(['exam_required', 'opened_at', 'closed_at', 'finalized_at', 'cancelled_at']);
        });
    }
};
