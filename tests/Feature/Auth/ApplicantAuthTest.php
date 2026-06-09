<?php

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

test('applicant can register with valid data', function () {
    $response = $this->post('/applicant/register', [
        'first_name' => 'Test',
        'middle_name' => 'Middle',
        'last_name' => 'Applicant',
        'email' => 'applicant@test.com',
        'phone' => '+251911111111',
        'gender' => 'male',
        'date_of_birth' => '1995-01-01',
        'nationality' => 'Ethiopian',
        'national_id' => '1111222233334444',
        'disability_status' => '0',
        'university_name' => 'Addis Ababa University',
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.5,
        'education_level' => 'degree',
        'work_experience_years' => 0,
        'work_experience_months' => 0,
        'region' => 'Addis Ababa',
        'city' => 'Addis Ababa',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'preferred_locale' => 'en',
        'documents' => UploadedFile::fake()->create('documents.pdf', 500, 'application/pdf'),
    ]);

    $response->assertRedirect(route('applicant.verify-email'));

    $this->assertDatabaseHas('users', ['email' => 'applicant@test.com']);
    $this->assertDatabaseHas('applicants', ['email' => 'applicant@test.com']);

    $user = User::where('email', 'applicant@test.com')->first();
    expect($user->hasRole('applicant'))->toBeTrue();
});

test('applicant registration fails with weak password', function () {
    $response = $this->post('/applicant/register', [
        'name' => 'Test Applicant',
        'email' => 'applicant2@test.com',
        'phone' => '+251911111112',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertDatabaseMissing('users', ['email' => 'applicant2@test.com']);
});

test('applicant registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'existing@test.com']);

    $response = $this->post('/applicant/register', [
        'name' => 'Another Applicant',
        'email' => 'existing@test.com',
        'phone' => '+251911111113',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('admin user can log in through unified login and is sent to admin dashboard', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('Password@123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'Password@123',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('applicant can login with valid credentials', function () {
    $user = User::factory()->asApplicant()->create([
        'email' => 'login@test.com',
        'password' => bcrypt('Password@123'),
        'status' => 'active',
    ]);

    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->post('/login', [
        'email' => 'login@test.com',
        'password' => 'Password@123',
    ]);

    $response->assertRedirect(route('applicant.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('inactive applicant cannot login', function () {
    $user = User::factory()->asApplicant()->inactive()->create([
        'email' => 'inactive@test.com',
        'password' => bcrypt('Password@123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'inactive@test.com',
        'password' => 'Password@123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('admin cannot access applicant dashboard route', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/applicant/dashboard');

    $response->assertForbidden();
});

test('applicant can access applicant dashboard', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/applicant/dashboard');

    $response->assertOk();
});
