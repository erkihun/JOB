<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->seedProductionSuperAdmin();

            return;
        }

        $this->seedLocalUsers();
    }

    private function seedProductionSuperAdmin(): void
    {
        $name = env('ADMIN_NAME');
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', ...app(PasswordPolicyService::class)->adminRules()],
            ],
        );

        if ($validator->fails()) {
            throw new RuntimeException(
                'Production admin seeding requires valid ADMIN_NAME, ADMIN_EMAIL, and strong ADMIN_PASSWORD environment values.'
            );
        }

        $data = $validator->validated();

        $superAdmin = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'email_verified_at' => now(),
                'preferred_locale' => 'en',
            ],
        );

        $superAdmin->assignRole('super_admin');
    }

    private function seedLocalUsers(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@jobs.local'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@jobs.local',
                'phone' => '+251900000001',
                'password' => Hash::make('SuperAdmin@123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'preferred_locale' => 'en',
            ]
        );

        $superAdmin->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@jobs.local'],
            [
                'name' => 'HR Admin',
                'email' => 'admin@jobs.local',
                'phone' => '+251900000002',
                'password' => Hash::make('HrAdmin@123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'preferred_locale' => 'en',
                'created_by' => $superAdmin->id,
            ]
        );

        $admin->assignRole('admin');

        $screeningOfficer = User::firstOrCreate(
            ['email' => 'screening@jobs.local'],
            [
                'name' => 'Screening Officer',
                'email' => 'screening@jobs.local',
                'phone' => '+251900000003',
                'password' => Hash::make('Screening@123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'preferred_locale' => 'en',
                'created_by' => $superAdmin->id,
            ]
        );

        $screeningOfficer->assignRole('screening_officer');
    }
}
