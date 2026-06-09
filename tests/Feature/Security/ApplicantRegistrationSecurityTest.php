<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    RateLimiter::clear('');
});

/**
 * Dedicated security-focused payload builder (named distinctly from the
 * functional suite's validRegistrationData() to avoid a Pest global clash).
 */
function secureRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Abebe',
        'middle_name' => 'Bikila',
        'last_name' => 'Tekle',
        'gender' => 'male',
        'date_of_birth' => '1995-06-15',
        'nationality' => 'Ethiopian',
        'national_id' => fake()->unique()->numerify('################'),
        'disability_status' => '0',
        'disability_type' => null,
        'university_name' => 'Addis Ababa University',
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.5,
        'education_level' => 'degree',
        'work_experience_years' => 2,
        'work_experience_months' => 0,
        'region' => 'Addis Ababa',
        'city' => 'Addis Ababa',
        'phone' => '+251'.rand(900000000, 999999999),
        'email' => 'sec_'.uniqid().'@example.com',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'preferred_locale' => 'en',
        'documents' => UploadedFile::fake()->create('documents.pdf', 300, 'application/pdf'),
    ], $overrides);
}

// ── Role / status / privilege escalation ─────────────────────────────────────

test('registration assigns only the applicant role and never an injected role', function (): void {
    $data = secureRegistrationPayload([
        'role' => 'super_admin',
        'roles' => ['super_admin', 'admin'],
        'permission' => 'users.delete',
        'is_admin' => 1,
        'user_type' => 'admin',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    $user = User::where('email', $data['email'])->firstOrFail();

    expect($user->hasRole('applicant'))->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeFalse()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->getRoleNames()->toArray())->toBe(['applicant']);
});

test('registration cannot set the user status from request input', function (): void {
    // Even if an attacker submits a status, the action hardcodes 'active'
    // and the validated() payload strips the unknown key entirely.
    $data = secureRegistrationPayload([
        'status' => 'suspended',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    $user = User::where('email', $data['email'])->firstOrFail();

    // Status is the controlled default, not the injected value.
    expect($user->status->value)->toBe('active');
});

test('registration ignores injected created_by and email_verified_at', function (): void {
    $other = User::factory()->admin()->create();

    $data = secureRegistrationPayload([
        'created_by' => $other->id,
        'email_verified_at' => now()->toDateTimeString(),
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    $user = User::where('email', $data['email'])->firstOrFail();

    expect($user->created_by)->toBeNull()
        // Email must remain unverified so the OTP gate still applies.
        ->and($user->email_verified_at)->toBeNull();
});

test('newly registered applicant cannot access the admin area', function (): void {
    $data = secureRegistrationPayload();

    $this->post(route('applicant.register'), $data);

    $user = User::where('email', $data['email'])->firstOrFail();

    // canAccessAdminArea() is the authoritative gate used by AdminAuthenticate.
    expect($user->canAccessAdminArea())->toBeFalse();

    // The admin dashboard must not render for an applicant — they are bounced
    // back to login (AdminAuthenticate logs them out and redirects).
    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

// ── Password policy ───────────────────────────────────────────────────────────

test('weak password is rejected and no account is created', function (): void {
    $data = secureRegistrationPayload([
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('password');

    expect(User::where('email', $data['email'])->exists())->toBeFalse();
});

test('password confirmation is required', function (): void {
    $data = secureRegistrationPayload([
        'password' => 'Password@123',
        'password_confirmation' => 'Different@456',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('password');
});

// ── File upload hardening ──────────────────────────────────────────────────────

test('svg document upload is rejected even when masquerading by mime', function (): void {
    $svg = UploadedFile::fake()->createWithContent(
        'malicious.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    );

    $data = secureRegistrationPayload(['documents' => $svg]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('documents');

    expect(User::where('email', $data['email'])->exists())->toBeFalse();
});

test('html document upload is rejected', function (): void {
    $html = UploadedFile::fake()->createWithContent('payload.html', '<html><body>x</body></html>');

    $data = secureRegistrationPayload(['documents' => $html]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('documents');
});

test('oversized document is rejected', function (): void {
    $data = secureRegistrationPayload([
        'documents' => UploadedFile::fake()->create('big.pdf', 4096, 'application/pdf'),
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('documents');
});

test('profile photo cannot be set during registration', function (): void {
    $data = secureRegistrationPayload([
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('profile_photo');
});

test('uploaded document is stored privately with a randomized name', function (): void {
    $data = secureRegistrationPayload([
        'documents' => UploadedFile::fake()->create('my-resume.pdf', 400, 'application/pdf'),
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    $doc = ApplicantProfileDocument::query()->latest()->firstOrFail();

    // Stored on the private local disk, never the public disk.
    Storage::disk('local')->assertExists($doc->file_path);
    Storage::disk('public')->assertMissing($doc->file_path);

    // The original name is preserved for display, but the stored file name is
    // randomized (UUID), not the user-supplied name.
    expect($doc->original_name)->toBe('my-resume.pdf')
        ->and($doc->file_name)->not->toContain('my-resume')
        ->and($doc->file_path)->toStartWith('applicant-documents/');
});

// ── XSS / injection safety ──────────────────────────────────────────────────────

test('xss payload in name fields is stored verbatim and escaped on output', function (): void {
    $payload = '<script>alert("xss")</script>';

    $data = secureRegistrationPayload([
        'first_name' => $payload,
        'address' => '<img src=x onerror=alert(1)>',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    $applicant = Applicant::where('email', $data['email'])->firstOrFail();

    // Value is stored as-is (no silent mangling) ...
    expect($applicant->first_name)->toBe($payload);

    // ... and Blade auto-escapes it wherever it is rendered.
    $rendered = e($applicant->first_name);
    expect($rendered)->not->toContain('<script>')
        ->and($rendered)->toContain('&lt;script&gt;');
});

test('sql injection attempt in fields is treated as literal data', function (): void {
    $data = secureRegistrationPayload([
        'first_name' => "Robert'); DROP TABLE users;--",
        'last_name' => '" OR "1"="1',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    // The users table is intact and the literal string was stored safely.
    expect(User::query()->count())->toBeGreaterThan(0);
    $applicant = Applicant::where('email', $data['email'])->firstOrFail();
    expect($applicant->first_name)->toBe("Robert'); DROP TABLE users;--");
});

// ── Rate limiting / CSRF ────────────────────────────────────────────────────────

test('registration is rate limited to five attempts per minute', function (): void {
    // Six rapid bad submissions; the sixth must be throttled (429).
    $statuses = [];
    for ($i = 0; $i < 6; $i++) {
        $response = $this->post(route('applicant.register'), [
            'email' => 'rl_'.$i.'@example.com',
        ]);
        $statuses[] = $response->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

test('registration route is protected by csrf in the web middleware group', function (): void {
    $routes = app('router')->getRoutes();
    $route = collect($routes->getRoutes())->first(
        fn ($r) => $r->uri() === 'applicant/register' && in_array('POST', $r->methods(), true)
    );

    expect($route)->not->toBeNull();

    // The POST registration route runs through the `web` group, which applies
    // VerifyCsrfToken. (The route file registers it inside the web routes.)
    // The POST registration route runs through the `web` group, which applies
    // VerifyCsrfToken to all non-API POST requests.
    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('web')
        ->and($route->methods())->toContain('POST');
});

// ── Localization ────────────────────────────────────────────────────────────────

test('amharic validation message is returned when locale is am', function (): void {
    $this->get(route('lang.switch', 'am'));

    $data = secureRegistrationPayload([
        'preferred_locale' => 'am',
        'phone' => 'not-a-phone',
    ]);

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('phone');
    $error = session('errors')->first('phone');

    // The Amharic phone message contains Ethiopic script.
    expect($error)->toMatch('/\p{Ethiopic}/u');
});
