<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\VacancyStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Local-only load test seeder.
 *
 * Usage:
 *   php artisan recruitment:seed-load-test --applicants=30000 --vacancies=20 --applications=30000
 *
 * NEVER runs in production (APP_ENV check).
 */
class SeedLoadTestCommand extends Command
{
    protected $signature = 'recruitment:seed-load-test
                            {--applicants=1000 : Number of applicants to create}
                            {--vacancies=10    : Number of open vacancies to create}
                            {--applications=1000 : Number of applications to create}
                            {--chunk=500       : Insert chunk size}
                            {--force           : Skip environment guard (use with caution)}';

    protected $description = 'Seed large dataset for load testing (LOCAL / STAGING only)';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('This command is disabled in production. Use --force to override (dangerous).');

            return self::FAILURE;
        }

        $applicantCount = (int) $this->option('applicants');
        $vacancyCount = (int) $this->option('vacancies');
        $appCount = (int) $this->option('applications');
        $chunk = (int) $this->option('chunk');

        $this->warn('Environment: '.app()->environment());
        $this->info("Seeding {$applicantCount} applicants, {$vacancyCount} vacancies, {$appCount} applications...");

        // Ensure roles exist
        if (! DB::table('roles')->exists()) {
            $this->call(RolesAndPermissionsSeeder::class);
        }

        // 1. Vacancies
        $this->info('Creating vacancies...');
        $vacancyIds = $this->seedVacancies($vacancyCount);
        $this->line("  ✓ {$vacancyCount} vacancies created");

        // 2. Applicants (chunked)
        $this->info('Creating applicants...');
        $applicantIds = $this->seedApplicants($applicantCount, $chunk);
        $this->line("  ✓ {$applicantCount} applicants created");

        // 3. Applications (chunked, respects unique constraint)
        $this->info('Creating applications...');
        $created = $this->seedApplications($applicantIds, $vacancyIds, $appCount, $chunk);
        $this->line("  ✓ {$created} applications created");

        // Summary
        $this->newLine();
        $this->info('Load test dataset ready.');
        $this->table(
            ['Item', 'Count'],
            [
                ['Applicants', Applicant::count()],
                ['Open vacancies', Vacancy::where('status', VacancyStatus::Open)->count()],
                ['Applications', Application::count()],
            ],
        );

        $sampleVacancy = Vacancy::where('status', VacancyStatus::Open)->value('id');
        $this->info("Sample open vacancy UUID: {$sampleVacancy}");
        $this->info('Applicant login pattern: loadtest_applicant_{N}@testmail.invalid / Password123!');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function seedVacancies(int $count): array
    {
        $ids = [];
        $now = now();
        $rows = [];
        $hash = fn () => Str::orderedUuid()->toString();

        for ($i = 0; $i < $count; $i++) {
            $id = $hash();
            $ids[] = $id;

            $rows[] = [
                'id' => $id,
                'title' => json_encode(['en' => "Load Test Vacancy {$i}", 'am' => "ቦታ {$i}"]),
                'code' => 'LT-VAC-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'department' => 'Load Test Department',
                'employment_type' => 'permanent',
                'location' => json_encode(['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ']),
                'number_of_positions' => 10,
                'salary_grade' => null,
                'description' => json_encode(['en' => 'Load test vacancy', 'am' => 'ፈተና']),
                'qualification_requirements' => json_encode(['en' => 'N/A', 'am' => 'N/A']),
                'field_of_study' => null,
                'minimum_experience' => null,
                'opening_date' => $now->toDateString(),
                'closing_date' => $now->addDays(60)->toDateString(),
                'status' => VacancyStatus::Open->value,
                'published_at' => $now,
                'created_by' => $this->getOrCreateAdminId(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('vacancies')->insert($rows);

        return $ids;
    }

    private ?string $adminId = null;

    private function getOrCreateAdminId(): string
    {
        if ($this->adminId) {
            return $this->adminId;
        }

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first()
            ?? User::factory()->create(['name' => 'Load Test Admin', 'email' => 'loadtest_admin@testmail.invalid']);

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        return $this->adminId = $admin->id;
    }

    /** @return list<string> */
    private function seedApplicants(int $count, int $chunk): array
    {
        $allIds = [];
        $now = now();
        $hashedPw = Hash::make('Password123!');
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($offset = 0; $offset < $count; $offset += $chunk) {
            $batchSize = min($chunk, $count - $offset);
            $userRows = [];
            $appRows = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $n = $offset + $i + 1;
                $uid = Str::orderedUuid()->toString();
                $aid = Str::orderedUuid()->toString();
                $email = "loadtest_applicant_{$n}@testmail.invalid";

                $userRows[] = [
                    'id' => $uid,
                    'name' => "LoadTest User{$n}",
                    'email' => $email,
                    'password' => $hashedPw,
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $appRows[] = [
                    'id' => $aid,
                    'user_id' => $uid,
                    'applicant_code' => 'LT-APL-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'first_name' => 'Load',
                    'last_name' => "Test{$n}",
                    'full_name' => "Load Test{$n}",
                    'phone' => '+251911'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'alternative_phone' => null,
                    'email' => $email,
                    'national_id' => 'LTID'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
                    'gender' => $n % 2 === 0 ? 'male' : 'female',
                    'date_of_birth' => null,
                    'disability_status' => 0,
                    'preferred_locale' => 'en',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $allIds[] = $aid;
            }

            DB::table('users')->insert($userRows);
            DB::table('applicants')->insert($appRows);

            // Assign applicant role
            $role = DB::table('roles')->where('name', 'applicant')->first();
            if ($role) {
                $pivotRows = array_map(fn ($row) => [
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $row['id'],
                ], $userRows);
                DB::table('model_has_roles')->insert($pivotRows);
            }

            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();

        return $allIds;
    }

    private function seedApplications(array $applicantIds, array $vacancyIds, int $count, int $chunk): int
    {
        $now = now();
        $created = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Track (applicant, vacancy) pairs to honour unique constraint
        $used = [];
        $rows = [];
        $seq = Application::count();

        shuffle($applicantIds);

        for ($i = 0; $i < $count; $i++) {
            $applicantId = $applicantIds[$i % count($applicantIds)];
            $vacancyId = $vacancyIds[array_rand($vacancyIds)];
            $key = $applicantId.'|'.$vacancyId;

            if (isset($used[$key])) {
                continue; // skip duplicate
            }

            $used[$key] = true;
            $seq++;

            $rows[] = [
                'id' => Str::orderedUuid()->toString(),
                'applicant_id' => $applicantId,
                'vacancy_id' => $vacancyId,
                'reference_number' => 'LT-APP-'.now()->year.'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
                'field_of_study' => 'Computer Science',
                'graduation_date' => '2022-06-01',
                'status' => ApplicationStatus::Submitted->value,
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= $chunk) {
                DB::table('applications')->insert($rows);
                $created += count($rows);
                $rows = [];
            }

            $bar->advance();
        }

        if ($rows) {
            DB::table('applications')->insert($rows);
            $created += count($rows);
        }

        $bar->finish();
        $this->newLine();

        return $created;
    }
}
