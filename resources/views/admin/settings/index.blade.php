@extends('layouts.admin')
@section('title', __('menus.settings'))
@section('content')
<div class="space-y-5" x-data="{ tab: 'org' }">
    <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.settings') }}</h1>

    {{-- Tab bar --}}
    <div class="flex flex-wrap gap-1 border-b border-gray-200">
        @foreach(['org' => __('messages.organization'), 'social' => __('messages.social_media'), 'recruitment' => __('menus.recruitment'), 'codes' => __('settings.codes'), 'results' => __('messages.result_weights'), 'localization' => __('messages.localization'), 'notifications' => __('menus.notifications'), 'security' => __('messages.security'), 'appearance' => __('settings.appearance')] as $key => $label)
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Organization --}}
        <div x-show="tab === 'org'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.organization') }}</h2>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('messages.org_name') }}</label>
                <input type="text" name="org[name]" value="{{ old('org.name', $settings['org.name']) }}"
                       class="form-input mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('messages.logo') }}</label>
                @php $logo = \App\Models\Setting::get('org.logo',''); @endphp
                @if($logo)
                <div class="mb-2">
                    <img src="{{ Storage::url($logo) }}" alt="" class="h-16 w-auto rounded-lg border border-gray-200">
                </div>
                @endif
                <input type="file" name="org[logo]" accept=".jpg,.jpeg,.png,.webp"
                       class="mt-1 block text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-muted file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand hover:file:bg-blue-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('settings.favicon') }}</label>
                @php $favicon = \App\Models\Setting::get('org.favicon',''); @endphp
                @if($favicon)
                <div class="mb-2">
                    <img src="{{ Storage::url($favicon) }}" alt="" class="h-10 w-10 rounded-lg border border-gray-200 object-contain p-1">
                </div>
                @endif
                <input type="file" name="org[favicon]" accept=".ico,.jpg,.jpeg,.png,.webp"
                       class="mt-1 block text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-muted file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-400">{{ __('settings.favicon_hint') }}</p>
                @error('org.favicon')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('fields.address') }}</label>
                <textarea name="org[address]" rows="2" class="form-textarea mt-1">{{ old('org.address', $settings['org.address']) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('fields.phone') }}</label>
                    <input type="text" name="org[phone]" value="{{ old('org.phone', $settings['org.phone']) }}" class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('fields.email') }}</label>
                    <input type="email" name="org[email]" value="{{ old('org.email', $settings['org.email']) }}" class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.website') }}</label>
                    <input type="url" name="org[website]" value="{{ old('org.website', $settings['org.website']) }}" class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.footer_text') }}</label>
                    <input type="text" name="org[footer_text]" value="{{ old('org.footer_text', $settings['org.footer_text']) }}" class="form-input mt-1">
                </div>
            </div>
        </div>

        {{-- Social --}}
        <div x-show="tab === 'social'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.social_media') }}</h2>
            </div>
            @foreach(['facebook'=>'Facebook','twitter'=>'Twitter / X','linkedin'=>'LinkedIn','youtube'=>'YouTube'] as $key => $label)
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                <input type="url" name="org[{{ $key }}]" value="{{ old("org.$key", $settings["org.$key"]) }}"
                       class="form-input mt-1">
            </div>
            @endforeach
        </div>

        {{-- Recruitment --}}
        <div x-show="tab === 'recruitment'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('menus.recruitment') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Max File Size (MB)</label>
                    <input type="number" name="recruitment[max_file_size_mb]" min="1" max="10" value="{{ old('recruitment.max_file_size_mb', $settings['recruitment.max_file_size_mb']) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reference Format</label>
                    <input type="text" name="recruitment[reference_format]" value="{{ old('recruitment.reference_format', $settings['recruitment.reference_format']) }}"
                           class="form-input mt-1">
                </div>
            </div>
            @php $allowedTypes = (array) old('recruitment.allowed_file_types', $settings['recruitment.allowed_file_types'] ?: ['pdf', 'jpg', 'jpeg', 'png']); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700">Allowed Upload File Types</label>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach(['pdf' => 'PDF', 'jpg' => 'JPG', 'jpeg' => 'JPEG', 'png' => 'PNG'] as $type => $label)
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="recruitment[allowed_file_types][]" value="{{ $type }}"
                                   {{ in_array($type, $allowedTypes, true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('recruitment.allowed_file_types')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="recruitment[allow_registration]" value="0">
                <input type="checkbox" id="allow_reg" name="recruitment[allow_registration]" value="1"
                       {{ $settings['recruitment.allow_registration'] ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                <label for="allow_reg" class="text-sm font-medium text-gray-700">Allow Public Registration</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="recruitment[show_archived_vacancies]" value="0">
                <input type="checkbox" id="show_archived_vacancies" name="recruitment[show_archived_vacancies]" value="1"
                       {{ $settings['recruitment.show_archived_vacancies'] ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                <label for="show_archived_vacancies" class="text-sm font-medium text-gray-700">Enable Public Vacancy Archive</label>
            </div>
        </div>

        {{-- Codes --}}
        <div x-show="tab === 'codes'" class="space-y-6" style="display:none">
            @php
            $placeholders = '{PREFIX}, {YEAR}, {YY}, {MONTH}, {DAY}, {SEQ}';
            @endphp

            {{-- Application Code --}}
            <div class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('settings.code_application') }}</h2>
                </div>
                <p class="text-xs text-gray-400">{{ __('settings.code_placeholders') }}: <code class="rounded bg-gray-100 px-1 py-0.5">{{ $placeholders }}</code></p>
                <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_prefix') }}</label>
                        <input type="text" name="codes[application][prefix]" value="{{ old('codes.application.prefix', $settings['codes.application.prefix']) }}"
                               class="form-input mt-1 uppercase" placeholder="APP">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_format') }}</label>
                        <input type="text" name="codes[application][format]" value="{{ old('codes.application.format', $settings['codes.application.format']) }}"
                               class="form-input mt-1 font-mono" placeholder="{PREFIX}-{YEAR}-{SEQ}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_padding') }}</label>
                        <input type="number" name="codes[application][padding]" min="1" max="10"
                               value="{{ old('codes.application.padding', $settings['codes.application.padding']) }}"
                               class="form-input mt-1">
                    </div>
                </div>
                @php
                $appPrefix  = $settings['codes.application.prefix']  ?: 'APP';
                $appFormat  = $settings['codes.application.format']   ?: '{PREFIX}-{YEAR}-{SEQ}';
                $appPadding = (int) ($settings['codes.application.padding'] ?: 6);
                $appPreview = strtr($appFormat, ['{PREFIX}'=>strtoupper($appPrefix),'{YEAR}'=>date('Y'),'{YY}'=>date('y'),'{MONTH}'=>date('m'),'{DAY}'=>date('d'),'{SEQ}'=>str_pad('1',$appPadding,'0',STR_PAD_LEFT)]);
                @endphp
                <p class="text-xs text-gray-500">{{ __('settings.code_preview') }}: <span class="font-mono font-medium text-brand">{{ $appPreview }}</span></p>
            </div>

            {{-- Vacancy Code --}}
            <div class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('settings.code_vacancy') }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="codes[vacancy][auto]" value="0">
                    <input type="checkbox" id="vacancy_auto" name="codes[vacancy][auto]" value="1"
                           {{ $settings['codes.vacancy.auto'] ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                    <label for="vacancy_auto" class="text-sm font-medium text-gray-700">{{ __('settings.code_auto_generate') }}</label>
                </div>
                <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_prefix') }}</label>
                        <input type="text" name="codes[vacancy][prefix]" value="{{ old('codes.vacancy.prefix', $settings['codes.vacancy.prefix']) }}"
                               class="form-input mt-1 uppercase" placeholder="VAC">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_format') }}</label>
                        <input type="text" name="codes[vacancy][format]" value="{{ old('codes.vacancy.format', $settings['codes.vacancy.format']) }}"
                               class="form-input mt-1 font-mono" placeholder="{PREFIX}-{YEAR}-{SEQ}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_padding') }}</label>
                        <input type="number" name="codes[vacancy][padding]" min="1" max="10"
                               value="{{ old('codes.vacancy.padding', $settings['codes.vacancy.padding']) }}"
                               class="form-input mt-1">
                    </div>
                </div>
                @php
                $vacPrefix  = $settings['codes.vacancy.prefix']  ?: 'VAC';
                $vacFormat  = $settings['codes.vacancy.format']   ?: '{PREFIX}-{YEAR}-{SEQ}';
                $vacPadding = (int) ($settings['codes.vacancy.padding'] ?: 4);
                $vacPreview = strtr($vacFormat, ['{PREFIX}'=>strtoupper($vacPrefix),'{YEAR}'=>date('Y'),'{YY}'=>date('y'),'{MONTH}'=>date('m'),'{DAY}'=>date('d'),'{SEQ}'=>str_pad('1',$vacPadding,'0',STR_PAD_LEFT)]);
                @endphp
                <p class="text-xs text-gray-500">{{ __('settings.code_preview') }}: <span class="font-mono font-medium text-accent">{{ $vacPreview }}</span></p>
            </div>

            {{-- Applicant Code --}}
            <div class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-navy"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('settings.code_applicant') }}</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_prefix') }}</label>
                        <input type="text" name="codes[applicant][prefix]" value="{{ old('codes.applicant.prefix', $settings['codes.applicant.prefix']) }}"
                               class="form-input mt-1 uppercase" placeholder="APL">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_format') }}</label>
                        <input type="text" name="codes[applicant][format]" value="{{ old('codes.applicant.format', $settings['codes.applicant.format']) }}"
                               class="form-input mt-1 font-mono" placeholder="{PREFIX}-{YEAR}-{SEQ}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.code_padding') }}</label>
                        <input type="number" name="codes[applicant][padding]" min="1" max="10"
                               value="{{ old('codes.applicant.padding', $settings['codes.applicant.padding']) }}"
                               class="form-input mt-1">
                    </div>
                </div>
                @php
                $aplPrefix  = $settings['codes.applicant.prefix']  ?: 'APL';
                $aplFormat  = $settings['codes.applicant.format']   ?: '{PREFIX}-{YEAR}-{SEQ}';
                $aplPadding = (int) ($settings['codes.applicant.padding'] ?: 5);
                $aplPreview = strtr($aplFormat, ['{PREFIX}'=>strtoupper($aplPrefix),'{YEAR}'=>date('Y'),'{YY}'=>date('y'),'{MONTH}'=>date('m'),'{DAY}'=>date('d'),'{SEQ}'=>str_pad('1',$aplPadding,'0',STR_PAD_LEFT)]);
                @endphp
                <p class="text-xs text-gray-500">{{ __('settings.code_preview') }}: <span class="font-mono font-medium text-navy">{{ $aplPreview }}</span></p>
            </div>
        </div>

        {{-- Result Weights --}}
        <div x-show="tab === 'results'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none"
             x-data="{
                 examW: {{ $settings['results.exam_weight'] ?: 60 }},
                 intW: {{ $settings['results.interview_weight'] ?: 40 }},
                 syncExam() { this.intW = Math.max(0, 100 - (parseFloat(this.examW) || 0)); },
                 syncInt()  { this.examW = Math.max(0, 100 - (parseFloat(this.intW) || 0)); }
             }">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.result_weights') }}</h2>
            </div>
            <p class="text-sm text-gray-500">{{ __('messages.result_weights_hint') }}</p>
            <div class="grid gap-4 sm:grid-cols-2 max-w-sm">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.exam_weight') }} (%)</label>
                    <input type="number" name="results[exam_weight]" x-model="examW" @input="syncExam()"
                           min="0" max="100" step="1"
                           value="{{ old('results.exam_weight', $settings['results.exam_weight'] ?: 60) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.interview_weight') }} (%)</label>
                    <input type="number" name="results[interview_weight]" x-model="intW" @input="syncInt()"
                           min="0" max="100" step="1"
                           value="{{ old('results.interview_weight', $settings['results.interview_weight'] ?: 40) }}"
                           class="form-input mt-1">
                </div>
            </div>
            <p class="text-xs text-gray-400">{{ __('messages.weight_hint') }}</p>
        </div>

        {{-- Localization --}}
        <div x-show="tab === 'localization'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.localization') }}</h2>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Default Locale</label>
                <select name="localization[default_locale]" class="form-select mt-1 max-w-xs">
                    <option value="en" {{ ($settings['localization.default_locale'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                    <option value="am" {{ ($settings['localization.default_locale'] ?? '') === 'am' ? 'selected' : '' }}>አማርኛ</option>
                </select>
            </div>
            @php $availableLocales = (array) old('app.available_locales', $settings['app.available_locales'] ?: ['en', 'am']); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700">Available Languages</label>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach(['en' => 'English', 'am' => 'አማርኛ'] as $locale => $label)
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="app[available_locales][]" value="{{ $locale }}"
                                   {{ in_array($locale, $availableLocales, true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('app.available_locales')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fallback Language</label>
                    <select name="app[fallback_locale]" class="form-select mt-1">
                        <option value="en" {{ ($settings['app.fallback_locale'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="am" {{ ($settings['app.fallback_locale'] ?? '') === 'am' ? 'selected' : '' }}>አማርኛ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date Format</label>
                    <select name="app[date_format]" class="form-select mt-1">
                        @foreach(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d, Y'] as $format)
                            <option value="{{ $format }}" {{ ($settings['app.date_format'] ?? 'Y-m-d') === $format ? 'selected' : '' }}>
                                {{ now()->format($format) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-start gap-3 pt-2">
                <div class="flex h-5 items-center">
                    <input type="hidden" name="localization[show_language_switcher]" value="0">
                    <input type="checkbox" id="show_language_switcher" name="localization[show_language_switcher]" value="1"
                           {{ ($settings['localization.show_language_switcher'] ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </div>
                <div>
                    <label for="show_language_switcher" class="block text-sm font-medium text-gray-700">Show Language Switcher</label>
                    <p class="text-xs text-gray-500 mt-0.5">Display the EN / አማ toggle on the public site, applicant portal, and admin panel.</p>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div x-show="tab === 'notifications'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('menus.notifications') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mail From Name</label>
                    <input type="text" name="mail[from_name]" value="{{ old('mail.from_name', $settings['mail.from_name']) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mail From Address</label>
                    <input type="email" name="mail[from_address]" value="{{ old('mail.from_address', $settings['mail.from_address']) }}"
                           class="form-input mt-1">
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div x-show="tab === 'security'" class="space-y-5 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.security') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('settings.session_timeout') }}</label>
                    <input type="number" name="security[session_timeout]" min="5" value="{{ old('security.session_timeout', $settings['security.session_timeout']) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('settings.login_attempts') }}</label>
                    <input type="number" name="security[login_attempts]" min="3" value="{{ old('security.login_attempts', $settings['security.login_attempts']) }}"
                           class="form-input mt-1">
                </div>
            </div>

            @php
                $mfaMethods = (array) old('security.mfa_methods_allowed', $settings['security.mfa_methods_allowed'] ?: ['totp']);
            @endphp
            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-4 w-0.5 rounded bg-navy"></div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('settings.mfa_management') }}</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ __('settings.mfa_management_hint') }}</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'mfa_enabled' => __('settings.mfa_enabled'),
                        'mfa_required_for_admins' => __('settings.mfa_required_for_admins'),
                        'mfa_required_for_applicants' => __('settings.mfa_required_for_applicants'),
                    ] as $key => $label)
                        <label for="security_{{ $key }}" class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2">
                            <input type="hidden" name="security[{{ $key }}]" value="0">
                            <input type="checkbox"
                                   id="security_{{ $key }}"
                                   name="security[{{ $key }}]"
                                   value="1"
                                   {{ old("security.{$key}", $settings["security.{$key}"]) ? 'checked' : '' }}
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-medium text-gray-700">{{ $label }}</span>
                                <span class="block text-xs text-gray-400">{{ __("settings.{$key}_hint") }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- Per-role MFA enforcement --}}
                @php
                    $mfaRequiredRoles = (array) old('security.mfa_required_roles', $settings['security.mfa_required_roles'] ?: []);
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <label class="block text-sm font-medium text-gray-700">{{ __('settings.mfa_required_roles') }}</label>
                    <p class="mt-1 text-xs text-gray-400">{{ __('settings.mfa_required_roles_hint') }}</p>
                    {{-- Submit an empty value so unchecking every role persists an empty list. --}}
                    <input type="hidden" name="security[mfa_required_roles][]" value="">
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($assignableRoles as $roleName)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox"
                                       name="security[mfa_required_roles][]"
                                       value="{{ $roleName }}"
                                       {{ in_array($roleName, $mfaRequiredRoles, true) ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>{{ \Illuminate\Support\Str::headline($roleName) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.mfa_methods_allowed') }}</label>
                        <label class="mt-2 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="security[mfa_methods_allowed][]" value="totp"
                                   {{ in_array('totp', $mfaMethods, true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>{{ __('settings.mfa_method_totp') }}</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.mfa_methods_allowed_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.mfa_remember_device_days') }}</label>
                        <input type="number"
                               name="security[mfa_remember_device_days]"
                               min="0"
                               max="365"
                               value="{{ old('security.mfa_remember_device_days', $settings['security.mfa_remember_device_days']) }}"
                               class="form-input mt-1">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.mfa_remember_device_days_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('settings.mfa_issuer_name') }}</label>
                        <input type="text"
                               name="security[mfa_issuer_name]"
                               value="{{ old('security.mfa_issuer_name', $settings['security.mfa_issuer_name']) }}"
                               class="form-input mt-1">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.mfa_issuer_name_hint') }}</p>
                    </div>
                </div>
            </div>

            @foreach([
                'admin' => [
                    'title' => __('settings.admin_password_policy'),
                    'description' => __('settings.admin_password_policy_hint'),
                    'accent' => 'bg-brand',
                ],
                'applicant' => [
                    'title' => __('settings.applicant_password_policy'),
                    'description' => __('settings.applicant_password_policy_hint'),
                    'accent' => 'bg-accent',
                ],
            ] as $scope => $policyCard)
                @php
                    $prefix = "{$scope}_password";
                    $toggles = [
                        'require_uppercase' => __('settings.require_uppercase'),
                        'require_lowercase' => __('settings.require_lowercase'),
                        'require_number' => __('settings.require_number'),
                        'require_symbol' => __('settings.require_symbol'),
                        'prevent_common_passwords' => __('settings.prevent_common_passwords'),
                    ];
                @endphp
                <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 h-4 w-0.5 rounded {{ $policyCard['accent'] }}"></div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $policyCard['title'] }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ $policyCard['description'] }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('settings.minimum_password_length') }}</label>
                            <input type="number"
                                   name="security[{{ $prefix }}_min_length]"
                                   min="8"
                                   max="128"
                                   value="{{ old("security.{$prefix}_min_length", $settings["security.{$prefix}_min_length"]) }}"
                                   class="form-input mt-1">
                            <p class="mt-1 text-xs text-gray-400">{{ __('settings.minimum_password_length_hint') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('settings.password_expiry_days') }}</label>
                            <input type="number"
                                   name="security[{{ $prefix }}_expiry_days]"
                                   min="1"
                                   max="3650"
                                   value="{{ old("security.{$prefix}_expiry_days", $settings["security.{$prefix}_expiry_days"]) }}"
                                   placeholder="{{ __('settings.optional') }}"
                                   class="form-input mt-1">
                            <p class="mt-1 text-xs text-gray-400">{{ __('settings.password_expiry_days_hint') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('settings.password_history_count') }}</label>
                            <input type="number"
                                   name="security[{{ $prefix }}_history_count]"
                                   min="1"
                                   max="24"
                                   value="{{ old("security.{$prefix}_history_count", $settings["security.{$prefix}_history_count"]) }}"
                                   placeholder="{{ __('settings.optional') }}"
                                   class="form-input mt-1">
                            <p class="mt-1 text-xs text-gray-400">{{ __('settings.password_history_count_hint') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($toggles as $key => $label)
                            @php
                                $settingKey = "security.{$prefix}_{$key}";
                                $inputId = "security_{$prefix}_{$key}";
                            @endphp
                            <label for="{{ $inputId }}" class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2">
                                <input type="hidden" name="security[{{ $prefix }}_{{ $key }}]" value="0">
                                <input type="checkbox"
                                       id="{{ $inputId }}"
                                       name="security[{{ $prefix }}_{{ $key }}]"
                                       value="1"
                                       {{ old("security.{$prefix}_{$key}", $settings[$settingKey]) ? 'checked' : '' }}
                                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>
                                    <span class="block text-sm font-medium text-gray-700">{{ $label }}</span>
                                    <span class="block text-xs text-gray-400">{{ __("settings.{$key}_hint") }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Appearance --}}
        @php
            $safeAppearanceColor = static function (mixed $value, string $fallback): string {
                $value = (string) $value;

                return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
            };

            $appearancePrimary = $safeAppearanceColor(old('appearance.primary_color', $settings['appearance.primary_color'] ?? '#1A56DB'), '#1A56DB');
            $appearanceSidebar = $safeAppearanceColor(old('appearance.sidebar_color', $settings['appearance.sidebar_color'] ?? '#1E3A8A'), '#1E3A8A');
            $appearanceAccent = $safeAppearanceColor(old('appearance.accent_color', $settings['appearance.accent_color'] ?? '#FF6B2B'), '#FF6B2B');
            $appearanceLogoSize = min(max((int) old('appearance.logo_size', $settings['appearance.logo_size'] ?: 36), 24), 72);
        @endphp
        <div x-show="tab === 'appearance'" class="space-y-6" style="display:none"
             x-data="{
                 primary: {{ Js::from($appearancePrimary) }},
                 sidebar: {{ Js::from($appearanceSidebar) }},
                 accent:  {{ Js::from($appearanceAccent) }},
                 logoSize: {{ $appearanceLogoSize }},
                 presets: [
                     { name: 'Blue',    primary: '#1A56DB', sidebar: '#1E3A8A', accent: '#FF6B2B' },
                     { name: 'Emerald', primary: '#059669', sidebar: '#065F46', accent: '#F59E0B' },
                     { name: 'Purple',  primary: '#7C3AED', sidebar: '#4C1D95', accent: '#EC4899' },
                     { name: 'Rose',    primary: '#E11D48', sidebar: '#881337', accent: '#FB923C' },
                     { name: 'Teal',    primary: '#0D9488', sidebar: '#134E4A', accent: '#F97316' },
                     { name: 'Slate',   primary: '#475569', sidebar: '#1E293B', accent: '#06B6D4' },
                 ],
                 applyPreset(p) { this.primary = p.primary; this.sidebar = p.sidebar; this.accent = p.accent; },
                 resetDefault() { this.primary = '#1A56DB'; this.sidebar = '#1E3A8A'; this.accent = '#FF6B2B'; this.logoSize = 36; this.preview(); },
                 preview() {
                     document.documentElement.style.setProperty('--color-brand', this.primary);
                     document.documentElement.style.setProperty('--color-navy', this.sidebar);
                     document.documentElement.style.setProperty('--color-accent', this.accent);
                 }
             }">

            {{-- Preset swatches --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('settings.appearance_presets') }}</h2>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <p class="text-sm text-gray-500">{{ __('settings.appearance_hint') }}</p>
                    <button type="button"
                            @click="resetDefault()"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">
                        {{ __('settings.appearance_reset_defaults') }}
                    </button>
                </div>
                <div class="flex flex-wrap gap-3">
                    <template x-for="p in presets" :key="p.name">
                        <button type="button"
                                @click="applyPreset(p); preview()"
                                :title="p.name"
                                class="group flex flex-col items-center gap-1.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full ring-2 ring-offset-2 transition"
                                  :style="'background:'+p.primary"
                                  :class="primary === p.primary ? 'ring-gray-500' : 'ring-transparent group-hover:ring-gray-300'">
                                <span class="h-4 w-4 rounded-full" :style="'background:'+p.accent"></span>
                            </span>
                            <span class="text-xs text-gray-500" x-text="p.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Custom color pickers --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('settings.appearance') }}</h2>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.appearance_primary_color') }}</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="primary" @input="preview()"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 p-0.5">
                            <input type="text" x-model="primary" @input="preview()"
                                   name="appearance[primary_color]"
                                   maxlength="7" placeholder="#1A56DB"
                                   class="form-input font-mono uppercase w-28">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.appearance_primary_color_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.appearance_sidebar_color') }}</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="sidebar" @input="preview()"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 p-0.5">
                            <input type="text" x-model="sidebar" @input="preview()"
                                   name="appearance[sidebar_color]"
                                   maxlength="7" placeholder="#1E3A8A"
                                   class="form-input font-mono uppercase w-28">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.appearance_sidebar_color_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.appearance_accent_color') }}</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="accent" @input="preview()"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 p-0.5">
                            <input type="text" x-model="accent" @input="preview()"
                                   name="appearance[accent_color]"
                                   maxlength="7" placeholder="#FF6B2B"
                                   class="form-input font-mono uppercase w-28">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.appearance_accent_color_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.logo_size') }}</label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="24" max="72" step="1" x-model.number="logoSize"
                                   class="w-full accent-blue-600">
                            <input type="number" name="appearance[logo_size]" min="24" max="72" x-model.number="logoSize"
                                   class="form-input w-20">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.logo_size_hint') }}</p>
                    </div>
                </div>

                {{-- Live preview bar --}}
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                    <div class="flex h-10 items-center gap-4 px-4" :style="'background:'+sidebar">
                        <div class="flex items-center justify-center rounded text-xs font-bold text-white" :style="'width:'+logoSize+'px;height:'+logoSize+'px;background:'+accent">LG</div>
                        <div class="h-2 w-24 rounded-full opacity-60 bg-white"></div>
                    </div>
                    <div class="flex items-center gap-3 bg-white px-4 py-3">
                        <span class="rounded-lg px-3 py-1.5 text-sm font-medium text-white" :style="'background:'+primary">{{ __('settings.preview_primary_button') }}</span>
                        <span class="rounded-lg px-3 py-1.5 text-sm font-medium text-white" :style="'background:'+accent">{{ __('settings.preview_accent_badge') }}</span>
                        <span class="text-sm" :style="'color:'+primary">{{ __('settings.preview_link_color') }}</span>
                    </div>
                </div>
            </div>

        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
