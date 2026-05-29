<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private array $keys = [
        'org.name', 'org.logo', 'org.favicon', 'org.address', 'org.phone', 'org.email', 'org.website', 'org.footer_text',
        'org.facebook', 'org.twitter', 'org.linkedin', 'org.youtube',
        'app.available_locales', 'app.fallback_locale', 'app.date_format',
        'recruitment.max_file_size_mb', 'recruitment.allowed_file_types', 'recruitment.allow_registration',
        'recruitment.show_archived_vacancies', 'recruitment.reference_format',
        'localization.default_locale', 'localization.show_language_switcher',
        'mail.from_name', 'mail.from_address',
        'security.session_timeout', 'security.login_attempts',
        // Code generation
        'codes.application.prefix', 'codes.application.format', 'codes.application.padding',
        'codes.vacancy.prefix', 'codes.vacancy.format', 'codes.vacancy.padding', 'codes.vacancy.auto',
        'codes.applicant.prefix', 'codes.applicant.format', 'codes.applicant.padding',
        'results.exam_weight', 'results.interview_weight',
        'appearance.primary_color', 'appearance.sidebar_color', 'appearance.accent_color', 'appearance.logo_size',
    ];

    public function index(): View
    {
        $settings = collect($this->keys)->mapWithKeys(
            fn ($key) => [$key => Setting::get($key, $this->defaultFor($key))]
        );

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'org.name' => ['nullable', 'string', 'max:255'],
            'org.address' => ['nullable', 'string', 'max:500'],
            'org.phone' => ['nullable', 'string', 'max:50'],
            'org.email' => ['nullable', 'email'],
            'org.website' => ['nullable', 'url'],
            'org.footer_text' => ['nullable', 'string', 'max:255'],
            'org.facebook' => ['nullable', 'url'],
            'org.twitter' => ['nullable', 'url'],
            'org.linkedin' => ['nullable', 'url'],
            'org.youtube' => ['nullable', 'url'],
            'org.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'org.favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:512'],
            'app.available_locales' => ['nullable', 'array', 'min:1'],
            'app.available_locales.*' => ['string', 'in:en,am'],
            'app.fallback_locale' => ['nullable', 'string', 'in:en,am'],
            'app.date_format' => ['nullable', 'string', 'in:Y-m-d,d/m/Y,m/d/Y,d M Y,M d, Y'],
            'recruitment.max_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:10'],
            'recruitment.allowed_file_types' => ['nullable', 'array', 'min:1'],
            'recruitment.allowed_file_types.*' => ['string', 'in:pdf,jpg,jpeg,png'],
            'recruitment.allow_registration' => ['nullable', 'boolean'],
            'recruitment.show_archived_vacancies' => ['nullable', 'boolean'],
            'recruitment.reference_format' => ['nullable', 'string', 'max:100'],
            'localization.default_locale' => ['nullable', 'string', 'in:en,am'],
            'localization.show_language_switcher' => ['nullable', 'boolean'],
            'mail.from_name' => ['nullable', 'string', 'max:255'],
            'mail.from_address' => ['nullable', 'email'],
            'security.session_timeout' => ['nullable', 'integer', 'min:5'],
            'security.login_attempts' => ['nullable', 'integer', 'min:3'],
            // Code generation
            'codes.application.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.application.format' => ['nullable', 'string', 'max:100'],
            'codes.application.padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'codes.vacancy.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.vacancy.format' => ['nullable', 'string', 'max:100'],
            'codes.vacancy.padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'codes.vacancy.auto' => ['nullable', 'boolean'],
            'codes.applicant.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.applicant.format' => ['nullable', 'string', 'max:100'],
            'codes.applicant.padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'results.exam_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'results.interview_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'appearance.primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.sidebar_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.logo_size' => ['nullable', 'integer', 'min:24', 'max:72'],
        ]);

        // Handle logo upload separately to avoid overwriting its path in the loop.
        if ($request->hasFile('org.logo')) {
            $path = $request->file('org.logo')->store('org', 'public');
            Setting::set('org.logo', $path);
        }
        if ($request->hasFile('org.favicon')) {
            $path = $request->file('org.favicon')->store('org', 'public');
            Setting::set('org.favicon', $path);
        }
        Arr::forget($data, 'org.logo');
        Arr::forget($data, 'org.favicon');

        if (! isset($data['app']['available_locales'])) {
            $data['app']['available_locales'] = ['en'];
        }

        $availableLocales = array_values((array) $data['app']['available_locales']);
        $data['app']['fallback_locale'] = in_array($data['app']['fallback_locale'] ?? null, $availableLocales, true)
            ? $data['app']['fallback_locale']
            : $availableLocales[0];
        $data['localization']['default_locale'] = in_array($data['localization']['default_locale'] ?? null, $availableLocales, true)
            ? $data['localization']['default_locale']
            : $availableLocales[0];

        if (! isset($data['recruitment']['allowed_file_types'])) {
            $data['recruitment']['allowed_file_types'] = ['pdf', 'jpg', 'jpeg', 'png'];
        }

        $this->persist('app.available_locales', $availableLocales);
        $this->persist('recruitment.allowed_file_types', array_values((array) $data['recruitment']['allowed_file_types']));
        Arr::forget($data, 'app.available_locales');
        Arr::forget($data, 'recruitment.allowed_file_types');

        // Flatten all nested arrays to dot notation and persist each key.
        foreach (Arr::dot($data) as $key => $value) {
            $this->persist($key, $value ?? '');
        }

        Cache::flush();
        $this->applyRuntimeConfiguration();

        return back()->with('success', __('messages.settings_saved'));
    }

    private function persist(string $key, mixed $value): void
    {
        $types = [
            'app.available_locales' => 'json',
            'recruitment.allowed_file_types' => 'json',
            'recruitment.max_file_size_mb' => 'integer',
            'recruitment.allow_registration' => 'boolean',
            'recruitment.show_archived_vacancies' => 'boolean',
            'localization.show_language_switcher' => 'boolean',
            'security.session_timeout' => 'integer',
            'security.login_attempts' => 'integer',
            'codes.application.padding' => 'integer',
            'codes.vacancy.padding' => 'integer',
            'codes.vacancy.auto' => 'boolean',
            'codes.applicant.padding' => 'integer',
            'results.exam_weight' => 'integer',
            'results.interview_weight' => 'integer',
            'appearance.logo_size' => 'integer',
        ];

        $groups = [
            'app.' => 'general',
            'org.' => 'general',
            'recruitment.' => 'recruitment',
            'localization.' => 'localization',
            'mail.' => 'notifications',
            'security.' => 'security',
            'codes.' => 'codes',
            'results.' => 'results',
            'appearance.' => 'appearance',
        ];

        $group = collect($groups)->first(
            fn (string $candidate, string $prefix): bool => str_starts_with($key, $prefix),
            'general'
        );

        Setting::set($key, $value, $types[$key] ?? 'string', $group);
    }

    private function defaultFor(string $key): mixed
    {
        return match ($key) {
            'org.name' => config('app.name'),
            'app.available_locales' => ['en', 'am'],
            'app.fallback_locale' => 'en',
            'app.date_format' => 'Y-m-d',
            'recruitment.max_file_size_mb' => 2,
            'recruitment.allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'recruitment.allow_registration',
            'recruitment.show_archived_vacancies',
            'codes.vacancy.auto',
            'localization.show_language_switcher' => true,
            'localization.default_locale' => 'en',
            'mail.from_name' => config('mail.from.name'),
            'mail.from_address' => config('mail.from.address'),
            'security.session_timeout' => 120,
            'security.login_attempts' => 5,
            'codes.application.prefix' => 'APP',
            'codes.application.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.application.padding' => 6,
            'codes.vacancy.prefix' => 'VAC',
            'codes.vacancy.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.vacancy.padding' => 4,
            'codes.applicant.prefix' => 'APL',
            'codes.applicant.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.applicant.padding' => 5,
            'results.exam_weight' => 60,
            'results.interview_weight' => 40,
            'appearance.primary_color' => '#1A56DB',
            'appearance.sidebar_color' => '#1E3A8A',
            'appearance.accent_color' => '#FF6B2B',
            'appearance.logo_size' => 36,
            default => '',
        };
    }

    private function applyRuntimeConfiguration(): void
    {
        Config::set('mail.from.name', Setting::get('mail.from_name', config('mail.from.name')));
        Config::set('mail.from.address', Setting::get('mail.from_address', config('mail.from.address')));
        Config::set('app.fallback_locale', Setting::get('app.fallback_locale', config('app.fallback_locale', 'en')));
    }
}
