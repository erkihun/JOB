<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancy_announcements', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published'])->default('draft')->after('content');
        });

        // Back-fill: rows that already have a past published_at are published.
        DB::table('vacancy_announcements')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('vacancy_announcements', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
