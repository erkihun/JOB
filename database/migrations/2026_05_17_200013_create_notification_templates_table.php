<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // matches NotificationType enum values
            $table->string('locale', 5)->default('en'); // en, am
            $table->string('subject');
            $table->text('body'); // supports placeholders: {{applicant_name}}, {{vacancy_title}}, etc.
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
