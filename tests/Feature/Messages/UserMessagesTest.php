<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function flattenTranslations(array $lines, string $prefix = ''): array
{
    $flat = [];

    foreach ($lines as $key => $value) {
        if (is_array($value)) {
            $flat += flattenTranslations($value, "{$prefix}{$key}.");
        } else {
            $flat["{$prefix}{$key}"] = (string) $value;
        }
    }

    return $flat;
}

function sessionErrors(array $messages): ViewErrorBag
{
    return (new ViewErrorBag)->put('default', new MessageBag($messages));
}

function superAdmin(): User
{
    test()->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->admin()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('every translation key referenced in code exists in both languages', function (): void {
    $missing = [];

    foreach (['app', 'resources/views', 'routes'] as $directory) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory), FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Requiring , or ) after the string skips prefixes completed at runtime, e.g. 'statuses.gender.'.$value.
            preg_match_all("/(?:__|trans|trans_choice|@lang)\(\s*'([a-z_\-]+\.[A-Za-z0-9_.\-]+)'\s*[,)]/", file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $key) {
                foreach (['en', 'am'] as $locale) {
                    if (! Lang::hasForLocale($key, $locale)) {
                        $missing[] = "{$locale}: {$key}";
                    }
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

test('english and amharic files define the same keys and placeholders', function (): void {
    $problems = [];
    $placeholders = function (string $line): array {
        preg_match_all('/:([a-zA-Z_]+)/', $line, $matches);
        $names = array_unique(array_map('strtolower', $matches[1]));
        sort($names);

        return $names;
    };

    foreach (glob(lang_path('en/*.php')) as $file) {
        $group = basename($file, '.php');
        $english = flattenTranslations(require $file);
        $amharic = flattenTranslations(require lang_path("am/{$group}.php"));

        // English validation rules not overridden in lang/en come from the framework's own file.
        $englishWithFramework = $group === 'validation'
            ? $english + flattenTranslations(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'))
            : $english;

        foreach (array_diff_key($english, $amharic) as $key => $line) {
            $problems[] = "am missing {$group}.{$key}";
        }

        foreach (array_diff_key($amharic, $englishWithFramework) as $key => $line) {
            $problems[] = "en missing {$group}.{$key}";
        }

        foreach (array_intersect_key($englishWithFramework, $amharic) as $key => $line) {
            if ($placeholders($line) !== $placeholders($amharic[$key])) {
                $problems[] = "placeholder mismatch {$group}.{$key}";
            }
        }
    }

    expect($problems)->toBe([]);
});

test('validation errors are shown in amharic for common rules', function (string $key): void {
    expect(Lang::hasForLocale("validation.{$key}", 'am'))->toBeTrue();
})->with(['in', 'exists', 'regex', 'digits', 'after', 'current_password', 'max.numeric', 'uploaded', 'password.mixed']);

test('warnings are visible on public pages', function (): void {
    $this->withSession(['warning' => 'Careful now'])
        ->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('Careful now');
});

test('login page shows a warning and a success message exactly once', function (string $key): void {
    $html = $this->withSession([$key => 'Unique flash text'])->get(route('login'))->assertOk()->getContent();

    expect(substr_count($html, 'Unique flash text'))->toBe(1);
})->with(['warning', 'success', 'error']);

test('mfa page shows flash messages exactly once', function (string $key): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Setting::set('security.mfa_enabled', true, 'boolean', 'security');

    $html = $this->actingAs(User::factory()->admin()->create())
        ->withSession([$key => 'Unique flash text'])
        ->get(route('mfa.show'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'Unique flash text'))->toBe(1);
})->with(['warning', 'success']);

test('admin error summary has a translated heading and appears once', function (string $route): void {
    $html = $this->actingAs(superAdmin())
        ->withSession(['errors' => sessionErrors(['field' => ['Unique error text']])])
        ->get(route($route))
        ->assertOk()
        ->assertSee(__('messages.fix_errors'))
        ->assertDontSee('messages.fix_errors')
        ->getContent();

    expect(substr_count($html, 'Unique error text'))->toBe(1);
})->with(['admin.settings.index', 'admin.announcements.create']);

test('expired password reset explains why the user was sent back', function (string $verifyRoute, string $requestRoute): void {
    $this->post(route($verifyRoute), ['otp' => '123456'])
        ->assertRedirect(route($requestRoute))
        ->assertSessionHasErrors(['email' => __('auth.reset_expired')]);
})->with([
    ['applicant.password.verify-otp', 'applicant.password.request'],
    ['admin.password.verify-otp', 'admin.password.request'],
]);

test('stale form as a guest goes to login with a session expired warning', function (): void {
    Route::middleware('web')->post('/_test/stale', fn () => throw new TokenMismatchException);

    $this->post('/_test/stale')
        ->assertRedirect(route('login'))
        ->assertSessionHas('warning', __('auth.session_expired'));
});

test('stale form while signed in returns to the form without keeping secrets', function (): void {
    Route::middleware('web')->post('/_test/stale', fn () => throw new TokenMismatchException);
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(User::factory()->admin()->create())
        ->from('/vacancies')
        ->post('/_test/stale', ['title' => 'Kept', 'password' => 'secret-value'])
        ->assertRedirect('/vacancies')
        ->assertSessionHas('warning', __('auth.page_expired'))
        ->assertSessionHasInput('title', 'Kept')
        ->assertSessionMissing('_old_input.password');
});

test('stale ajax request still receives a json 419', function (): void {
    Route::middleware('web')->post('/_test/stale', fn () => throw new TokenMismatchException);

    $this->postJson('/_test/stale')->assertStatus(419);
});

test('error pages are bilingual and reveal no internal detail', function (): void {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee(trans('errors.404.title', [], 'en'))
        ->assertSee(trans('errors.404.title', [], 'am'));

    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('applicant.dashboard'))
        ->assertForbidden()
        ->assertSee(trans('errors.403.message', [], 'en'))
        ->assertSee(trans('errors.403.message', [], 'am'));
});
