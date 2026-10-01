<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only history of every closing-date extension. The announcement's
     * closing_date always holds the *current* deadline; this table keeps every
     * previous one together with who changed it and why.
     */
    public function up(): void
    {
        Schema::create('recruitment_deadline_extensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained('recruitment_announcements')->restrictOnDelete();
            $table->date('old_closing_date');
            $table->date('new_closing_date');
            $table->text('reason');
            $table->string('reference', 255)->nullable();
            $table->uuid('extended_by')->nullable();
            $table->timestamp('extended_at');
            $table->unsignedInteger('notified_count')->default(0);
            $table->timestamps();

            $table->foreign('extended_by')->references('id')->on('users')->nullOnDelete();
            // Explicit name: the generated one exceeds MySQL's 64-character limit.
            $table->index(['announcement_id', 'extended_at'], 'idx_deadline_ext_announcement_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_deadline_extensions');
    }
};
