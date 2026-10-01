<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            // Copy of the recruitment-relevant profile data as it stood when the
            // application was submitted (refreshed while the window is open, frozen after).
            $table->json('profile_snapshot')->nullable()->after('cgpa');
            $table->timestamp('snapshot_taken_at')->nullable()->after('profile_snapshot');

            // Administrative lock (locked_at already exists) and time-boxed reopening.
            $table->uuid('locked_by')->nullable()->after('locked_at');
            $table->text('lock_reason')->nullable()->after('locked_by');
            $table->timestamp('reopened_until')->nullable()->after('lock_reason');
            $table->uuid('reopened_by')->nullable()->after('reopened_until');
            $table->text('reopen_reason')->nullable()->after('reopened_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_snapshot', 'snapshot_taken_at', 'locked_by', 'lock_reason',
                'reopened_until', 'reopened_by', 'reopen_reason',
            ]);
        });
    }
};
