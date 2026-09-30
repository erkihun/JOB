<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

test('saving one section preserves unrelated configuration and rate limits', function (): void {
    Setting::set('app.available_locales', ['en', 'am'], 'json');
    Setting::set('app.fallback_locale', 'am');
    Setting::set('localization.default_locale', 'am');
    Setting::set('recruitment.allowed_file_types', ['pdf'], 'json');
    Setting::set('security.mfa_required_roles', ['admin'], 'json');
    Cache::put('unrelated-setting-check', 'keep', 60);
    RateLimiter::hit('settings-save-lockout-check', 60);

    $this->put(route('admin.settings.update'), [
        '_section' => 'appearance', 'appearance' => ['primary_color' => '#059669'],
    ])->assertSessionHasNoErrors()->assertSessionHas('settings_section', 'appearance');

    expect(Setting::get('app.available_locales'))->toBe(['en', 'am'])
        ->and(Setting::get('app.fallback_locale'))->toBe('am')
        ->and(Setting::get('localization.default_locale'))->toBe('am')
        ->and(Setting::get('recruitment.allowed_file_types'))->toBe(['pdf'])
        ->and(Setting::get('security.mfa_required_roles'))->toBe(['admin'])
        ->and(Cache::get('unrelated-setting-check'))->toBe('keep')
        ->and(RateLimiter::attempts('settings-save-lockout-check'))->toBe(1);
});

test('saving security does not clear role requirements unless explicitly submitted', function (): void {
    Setting::set('security.mfa_required_roles', ['admin'], 'json');
    $this->put(route('admin.settings.update'), ['security' => ['session_timeout' => 45]])->assertSessionHasNoErrors();
    expect(Setting::get('security.mfa_required_roles'))->toBe(['admin']);
    $this->put(route('admin.settings.update'), ['security' => ['mfa_required_roles' => ['']]])->assertSessionHasNoErrors();
    expect(Setting::get('security.mfa_required_roles'))->toBe([]);
});

test('empty checkbox groups are rejected instead of silently restoring defaults', function (string $section, string $key): void {
    $this->from(route('admin.settings.index'))->put(route('admin.settings.update'), [
        '_section' => $section, '_present' => [str_replace('.', '_', $key) => 1],
    ])->assertSessionHasErrors($key)->assertSessionHasInput('_section', $section);
})->with([
    ['recruitment', 'recruitment.allowed_file_types'],
    ['localization', 'app.available_locales'],
    ['security', 'security.mfa_methods_allowed'],
]);

test('weights preserve zero and decimal values and reject totals other than one hundred', function (): void {
    $this->put(route('admin.settings.update'), ['results' => ['exam_weight' => 0, 'interview_weight' => 100]])
        ->assertSessionHasNoErrors();
    expect(Setting::get('results.exam_weight'))->toBe(0.0);
    $this->get(route('admin.settings.index'))->assertOk()->assertSee('exam: 0', false);
    $this->put(route('admin.settings.update'), ['results' => ['exam_weight' => 60.5, 'interview_weight' => 39.5]])
        ->assertSessionHasNoErrors();
    expect(Setting::get('results.exam_weight'))->toBe(60.5)->and(Setting::get('results.interview_weight'))->toBe(39.5);
    $this->put(route('admin.settings.update'), ['results' => ['exam_weight' => 80, 'interview_weight' => 80]])
        ->assertSessionHasErrors('results.exam_weight');
    expect(Setting::get('results.exam_weight'))->toBe(60.5);
});

test('date formats containing commas can actually be saved', function (): void {
    $this->put(route('admin.settings.update'), ['app' => ['date_format' => 'M d, Y']])->assertSessionHasNoErrors();
    expect(Setting::get('app.date_format'))->toBe('M d, Y');
});

test('unsupported or nonsequential code formats are rejected', function (string $format): void {
    $this->put(route('admin.settings.update'), ['codes' => ['application' => ['format' => $format]]])
        ->assertSessionHasErrors('codes.application.format');
})->with(['{PREFIX}-{YEAR}', '{UNKNOWN}-{SEQ}']);

test('code generation uses the configured format prefix and padding', function (): void {
    $this->put(route('admin.settings.update'), [
        'codes' => ['application' => ['format' => '{PREFIX}-{YY}-{SEQ}', 'prefix' => 'REC', 'padding' => 3]],
    ])->assertSessionHasNoErrors();
    expect(app(CodeGeneratorService::class)->forApplication())->toBe('REC-'.now()->format('y').'-001');
});

test('disabled languages disappear from public and admin menus', function (): void {
    $this->put(route('admin.settings.update'), [
        'app' => ['available_locales' => ['en']], 'localization' => ['show_language_switcher' => true],
    ])->assertSessionHasNoErrors();
    foreach ([route('home'), route('admin.settings.index')] as $url) {
        $this->get($url)->assertOk()->assertDontSee('href="'.route('lang.switch', 'am').'"', false)
            ->assertSee('href="'.route('lang.switch', 'en').'"', false);
    }
});

test('settings page keeps the selected section and labels inactive controls honestly', function (): void {
    $this->withSession(['settings_section' => 'recruitment'])->get(route('admin.settings.index'))
        ->assertOk()->assertSee("tab: 'recruitment'", false)
        ->assertSee(':disabled="tab !==', false)
        ->assertSee(__('settings.save_section'))
        ->assertSee(__('settings.archive_unavailable'))
        ->assertSee(__('settings.reference_format_location'))
        ->assertDontSee('name="recruitment[reference_format]"', false)
        ->assertDontSee('name="security[admin_password_expiry_days]"', false);
});

test('settings page renders localized configuration guidance', function (): void {
    auth()->user()->update(['preferred_locale' => 'am']);
    $this->withSession(['locale' => 'am'])->get(route('admin.settings.index'))
        ->assertOk()->assertSee(__('settings.save_section', [], 'am'))
        ->assertSee(__('settings.mail_transport_hint', [], 'am'));
});
