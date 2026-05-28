<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure a default institution exists for existing vacancies
        $defaultId = \Illuminate\Support\Str::orderedUuid()->toString();

        DB::table('institutions')->insert([
            'id'         => $defaultId,
            'name'       => config('app.name', 'Default Institution'),
            'short_name' => null,
            'code'       => 'DEFAULT',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('vacancies', function (Blueprint $table) use ($defaultId) {
            $table->uuid('institution_id')->nullable()->after('id');
            $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
            $table->index('institution_id');
        });

        // Assign all existing vacancies to the default institution
        DB::table('vacancies')->whereNull('institution_id')->update(['institution_id' => $defaultId]);
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['institution_id']);
            $table->dropColumn('institution_id');
        });

        DB::table('institutions')->where('code', 'DEFAULT')->delete();
    }
};
