<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 20);
            $table->string('destination', 20);
            $table->string('disk', 40);
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checksum', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
