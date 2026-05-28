<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private array $keys = [
        'org.name', 'org.logo', 'org.favicon', 'org.address', 'org.phone', 'org.email', 'org.website', 'org.footer_text',
        'org.facebook', 'org.twitter', 'org.linkedin', 'org.youtube',
        'recruitment.max_file_size_mb', 'recruitment.allow_registration', 'recruitment.reference_format',
        'localization.default_locale', 'localization.show_language_switcher',
        'notifications.mail_from_name', 'notifications.mail_from_address',
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
            fn ($key) => [$key => Setting::get($key, '')]
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
            'recruitment.max_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:10'],
            'recruitment.allow_registration' => ['nullable', 'boolean'],
            'recruitment.reference_format' => ['nullable', 'string', 'max:100'],
            'localization.default_locale' => ['nullable', 'string', 'in:en,am'],
            'localization.show_language_switcher' => ['nullable', 'boolean'],
            'notifications.mail_from_name' => ['nullable', 'string', 'max:255'],
            'notifications.mail_from_address' => ['nullable', 'email'],
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

        // Flatten all nested arrays to dot notation and persist each key.
        foreach (Arr::dot($data) as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        Cache::flush();

        return back()->with('success', __('messages.settings_saved'));
    }
}
