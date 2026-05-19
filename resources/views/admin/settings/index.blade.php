@extends('layouts.admin')
@section('title', __('menus.settings'))
@section('content')
<div class="space-y-5" x-data="{ tab: 'org' }">
    <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.settings') }}</h1>

    {{-- Tab bar --}}
    <div class="flex flex-wrap gap-1 border-b border-gray-200">
        @foreach(['org' => __('messages.organization'), 'social' => __('messages.social_media'), 'recruitment' => __('menus.recruitment'), 'codes' => __('settings.codes'), 'results' => __('messages.result_weights'), 'localization' => __('messages.localization'), 'notifications' => __('menus.notifications'), 'security' => __('messages.security')] as $key => $label)
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
            <div class="flex items-center gap-2">
                <input type="hidden" name="recruitment[allow_registration]" value="0">
                <input type="checkbox" id="allow_reg" name="recruitment[allow_registration]" value="1"
                       {{ $settings['recruitment.allow_registration'] ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                <label for="allow_reg" class="text-sm font-medium text-gray-700">Allow Public Registration</label>
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
                    <input type="text" name="notifications[mail_from_name]" value="{{ old('notifications.mail_from_name', $settings['notifications.mail_from_name']) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mail From Address</label>
                    <input type="email" name="notifications[mail_from_address]" value="{{ old('notifications.mail_from_address', $settings['notifications.mail_from_address']) }}"
                           class="form-input mt-1">
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div x-show="tab === 'security'" class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm" style="display:none">
            <div class="flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.security') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Session Timeout (min)</label>
                    <input type="number" name="security[session_timeout]" min="5" value="{{ old('security.session_timeout', $settings['security.session_timeout']) }}"
                           class="form-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Max Login Attempts</label>
                    <input type="number" name="security[login_attempts]" min="3" value="{{ old('security.login_attempts', $settings['security.login_attempts']) }}"
                           class="form-input mt-1">
                </div>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
