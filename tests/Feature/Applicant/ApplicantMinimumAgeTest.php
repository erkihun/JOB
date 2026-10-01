<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * Applicants must be at least 18 (Applicant::MINIMUM_AGE): enforced on the
 * server for registration and profile edits, and the date pickers do not offer
 * later birth dates.
 */

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    $this->travelTo('2026-10-01 12:00:00');
    openRecruitmentWindow();
});

function minAgeRegistration(string $dateOfBirth): array
{
    return [
        'first_name' => 'Liya', 'middle_name' => 'Tesfaye', 'last_name' => 'Worku',
        'gender' => 'female', 'date_of_birth' => $dateOfBirth, 'nationality' => 'Ethiopian',
        'national_id' => fake()->unique()->numerify('################'), 'disability_status' => '0',
        'phone' => '+2519'.fake()->unique()->numerify('########'),
        'email' => 'minage_'.uniqid().'@example.com',
        'password' => 'Password@123', 'password_confirmation' => 'Password@123',
        'preferred_locale' => 'en',
        'documents' => UploadedFile::fake()->create('documents.pdf', 100, 'application/pdf'),
    ];
}

test('registration rejects an applicant younger than 18', function (string $dateOfBirth): void {
    $data = minAgeRegistration($dateOfBirth);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors(['date_of_birth' => __('applicant.v_min_age', ['age' => 18])]);

    expect(User::where('email', $data['email'])->exists())->toBeFalse();
})->with([
    'one day short of 18' => ['2008-10-02'],
    'a child' => ['2015-05-10'],
    'in the future' => ['2027-01-01'],
]);

test('registration accepts an applicant who turns 18 today or is older', function (string $dateOfBirth): void {
    $data = minAgeRegistration($dateOfBirth);

    $this->post(route('applicant.register'), $data)->assertSessionHasNoErrors()->assertRedirect(route('applicant.verify-email'));

    expect(User::where('email', $data['email'])->exists())->toBeTrue();
})->with([
    '18th birthday today' => ['2008-10-01'],
    'adult' => ['1990-03-15'],
]);

test('the registration date pickers do not offer birth dates under 18', function (string $locale): void {
    app()->setLocale($locale);
    $response = $this->withSession(['locale' => $locale])->get(route('applicant.register'))->assertOk();

    if ($locale === 'en') {
        $response->assertSee('max="2008-10-01"', false);
    } else {
        // Ethiopian picker: max passed to the component, and the year list stops at the latest allowed year.
        // 2008-10-01 is 21 Meskerem 2001 in the Ethiopian calendar.
        $response->assertSee("ethiopianDatepicker('date_of_birth', '', '2008-10-01'", false)
            ->assertSee('<option value="2001">', false)
            ->assertDontSee('<option value="2002">', false);
    }
})->with(['en', 'am']);

test('a profile edit cannot change the date of birth to under 18', function (): void {
    $applicant = Applicant::factory()->create(['date_of_birth' => '1995-01-01']);

    $this->actingAs($applicant->user)->put(route('applicant.profile.update'), [
        'first_name' => 'Liya', 'middle_name' => 'T', 'last_name' => 'Worku',
        'phone' => $applicant->phone, 'email' => $applicant->email, 'national_id' => $applicant->national_id,
        'gender' => 'female', 'date_of_birth' => '2010-01-01', 'preferred_locale' => 'en',
    ])->assertSessionHasErrors('date_of_birth');

    expect($applicant->refresh()->date_of_birth->toDateString())->toBe('1995-01-01');
});
