<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app.name', 'value' => 'Job Vacancy System', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app.default_locale', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app.available_locales', 'value' => '["en","am"]', 'type' => 'json', 'group' => 'general'],
            ['key' => 'app.fallback_locale', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.name', 'value' => 'Your Organization', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.logo', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.favicon', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.address', 'value' => 'Addis Ababa, Ethiopia', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.phone', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.email', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.website', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.footer_text', 'value' => 'All rights reserved.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.facebook', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.twitter', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.linkedin', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'org.youtube', 'value' => '', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app.date_format', 'value' => 'Y-m-d', 'type' => 'string', 'group' => 'general'],

            // Recruitment
            ['key' => 'recruitment.max_file_size_mb', 'value' => '2', 'type' => 'integer', 'group' => 'recruitment'],
            ['key' => 'recruitment.allowed_file_types', 'value' => '["pdf","jpg","jpeg","png"]', 'type' => 'json', 'group' => 'recruitment'],
            ['key' => 'recruitment.allow_applicant_edit', 'value' => 'true', 'type' => 'boolean', 'group' => 'recruitment'],
            ['key' => 'recruitment.show_archived_vacancies', 'value' => 'true', 'type' => 'boolean', 'group' => 'recruitment'],
            ['key' => 'recruitment.allow_registration', 'value' => 'true', 'type' => 'boolean', 'group' => 'recruitment'],
            ['key' => 'recruitment.reference_prefix', 'value' => 'APP', 'type' => 'string', 'group' => 'recruitment'],
            ['key' => 'recruitment.reference_format', 'value' => 'APP-{YEAR}-{SEQUENCE}', 'type' => 'string', 'group' => 'recruitment'],

            // Code generation
            ['key' => 'codes.application.prefix',  'value' => 'APP',                   'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.application.format',  'value' => '{PREFIX}-{YEAR}-{SEQ}',  'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.application.padding', 'value' => '6',                      'type' => 'integer', 'group' => 'codes'],
            ['key' => 'codes.vacancy.prefix',      'value' => 'VAC',                    'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.vacancy.format',      'value' => '{PREFIX}-{YEAR}-{SEQ}',  'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.vacancy.padding',     'value' => '4',                      'type' => 'integer', 'group' => 'codes'],
            ['key' => 'codes.vacancy.auto',        'value' => 'true',                   'type' => 'boolean', 'group' => 'codes'],
            ['key' => 'codes.applicant.prefix',    'value' => 'APL',                    'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.applicant.format',    'value' => '{PREFIX}-{YEAR}-{SEQ}',  'type' => 'string',  'group' => 'codes'],
            ['key' => 'codes.applicant.padding',   'value' => '5',                      'type' => 'integer', 'group' => 'codes'],

            // Localization
            ['key' => 'localization.default_locale',        'value' => 'en',  'type' => 'string',  'group' => 'localization'],
            ['key' => 'localization.show_language_switcher', 'value' => 'true', 'type' => 'boolean', 'group' => 'localization'],

            // Appearance (color theme)
            ['key' => 'appearance.primary_color', 'value' => '#1A56DB', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'appearance.sidebar_color', 'value' => '#1E3A8A', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'appearance.accent_color',  'value' => '#FF6B2B', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'appearance.logo_size', 'value' => '36', 'type' => 'integer', 'group' => 'appearance'],

            // Email
            ['key' => 'mail.from_name', 'value' => 'Job Vacancy System', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'mail.from_address', 'value' => 'noreply@example.com', 'type' => 'string', 'group' => 'notifications'],

            // Security
            ['key' => 'security.login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'security.session_timeout', 'value' => '120', 'type' => 'integer', 'group' => 'security'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                ]
            );
        }
    }
}
