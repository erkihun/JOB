<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Name breakdown (nullable so existing rows survive)
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');

            // Personal details
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('date_of_birth');
            $table->string('disability_type')->nullable()->after('disability_status');

            // Education
            $table->string('university_name')->nullable()->after('ethnicity');
            $table->string('field_of_study')->nullable()->after('university_name');
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('field_of_study');
            $table->date('graduation_date')->nullable()->after('graduation_year');
            $table->decimal('gpa', 4, 2)->nullable()->after('graduation_date');
            $table->string('education_level')->nullable()->after('gpa');

            // Work experience
            $table->unsignedSmallInteger('work_experience_years')->default(0)->after('work_experience_months');
            $table->string('current_employer')->nullable()->after('work_experience_years');
            $table->string('current_position')->nullable()->after('current_employer');
            $table->text('work_experience_summary')->nullable()->after('current_position');

            // Address breakdown
            $table->string('region')->nullable()->after('address');
            $table->string('city')->nullable()->after('region');
            $table->string('woreda')->nullable()->after('city');
            $table->string('alternative_phone')->nullable()->after('phone');

            // Profile photo (stored on local disk, served via authenticated route)
            $table->string('profile_photo_path')->nullable()->after('preferred_locale');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name',
                'date_of_birth', 'nationality', 'disability_type',
                'university_name', 'field_of_study', 'graduation_year',
                'graduation_date', 'gpa', 'education_level',
                'work_experience_years', 'current_employer', 'current_position',
                'work_experience_summary',
                'region', 'city', 'woreda', 'alternative_phone',
                'profile_photo_path',
            ]);
        });
    }
};
