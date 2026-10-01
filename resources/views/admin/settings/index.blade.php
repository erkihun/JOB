@extends('layouts.admin')
@section('title', __('menus.settings'))
@section('content')
@php
    $tabs = ['org' => __('messages.organization'), 'social' => __('messages.social_media'), 'recruitment' => __('menus.recruitment'), 'codes' => __('settings.codes'), 'results' => __('messages.result_weights'), 'localization' => __('messages.localization'), 'notifications' => __('menus.notifications'), 'security' => __('messages.security'), 'appearance' => __('settings.appearance')];
    $initialTab = old('_section', session('settings_section', 'org'));
    $initialTab = is_string($initialTab) && array_key_exists($initialTab, $tabs) ? $initialTab : 'org';

    // Side-menu grouping and icons (presentation only; sections are the keys above).
    $groups = [
        __('settings.group_general') => ['org', 'social', 'appearance'],
        __('menus.recruitment')      => ['recruitment', 'codes', 'results'],
        __('settings.group_system')  => ['localization', 'notifications', 'security'],
    ];
    $icons = [
        'org' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
        'social' => 'M13.8 10.2a4 4 0 00-5.6 0l-4 4a4 4 0 105.6 5.6l1.1-1.1m-.8-4.9a4 4 0 005.6 0l4-4a4 4 0 00-5.6-5.6l-1.1 1.1',
        'appearance' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.3M11 7.3l1.7-1.6a2 2 0 012.8 0l2.8 2.8a2 2 0 010 2.8L9.8 19.8',
        'recruitment' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z',
        'codes' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14',
        'results' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'localization' => 'M3 5h12M9 3v2m1 9.5A18 18 0 016.4 9m6.1 9h7M11 21l5-10 5 10M12.8 5C11.8 10.8 8.1 15.6 3 18.1',
        'notifications' => 'M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'security' => 'M9 12l2 2 4-4m5.6-4A12 12 0 0112 2.9 12 12 0 013.4 6 12 12 0 003 9c0 5.6 3.8 10.3 9 11.6 5.2-1.3 9-6 9-11.6 0-1-.1-2-.4-3z',
    ];
    $hasErrors = $errors->any();
    $check = 'flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:border-gray-300';
@endphp
<div class="space-y-5"
     x-data="{
        tab: @js($initialTab),
        changed: false,
        go(next) {
            if (next === this.tab) return;
            if (this.changed && !confirm(@js(__('settings.unsaved_switch_confirm')))) { this.$refs.sectionSelect.value = this.tab; return; }
            this.changed = false;
            this.tab = next;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
     }">

    <x-admin.page-header :title="__('menus.settings')" :description="__('settings.configuration_intro')" />

    <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">

        {{-- ── Section navigation ── --}}
        <nav aria-label="{{ __('menus.settings') }}" class="lg:sticky lg:top-20 lg:self-start">
            <label for="settings-section" class="sr-only">{{ __('menus.settings') }}</label>
            <select id="settings-section" x-ref="sectionSelect" :value="tab" @change="go($event.target.value)" class="form-select lg:hidden">
                @foreach($groups as $group => $keys)
                <optgroup label="{{ $group }}">
                    @foreach($keys as $key)
                    <option value="{{ $key }}" @selected($initialTab === $key)>{{ $tabs[$key] }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>

            <div class="hidden space-y-5 lg:block">
                @foreach($groups as $group => $keys)
                <div>
                    <p class="mb-1.5 px-3 text-xs font-bold uppercase tracking-wider text-gray-600">{{ $group }}</p>
                    <ul class="space-y-0.5">
                        @foreach($keys as $key)
                        <li>
                            <button type="button" @click="go('{{ $key }}')"
                                    :aria-current="tab === '{{ $key }}' ? 'page' : null"
                                    :class="tab === '{{ $key }}' ? 'bg-brand-muted font-semibold text-brand' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icons[$key] }}"/></svg>
                                <span class="flex-1">{{ $tabs[$key] }}</span>
                                @if($hasErrors && $initialTab === $key)
                                <span class="h-2 w-2 rounded-full bg-red-600" aria-hidden="true"></span>
                                <span class="sr-only">{{ __('settings.correct_errors') }}</span>
                                @endif
                            </button>
                        </li>
                        @endforeach
                        @if($group === __('settings.group_system'))
                        @can('backups.view')
                        <li>
                            <a href="{{ route('admin.backups.index') }}"
                               class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7v10c0 2.2 3.6 4 8 4s8-1.8 8-4V7M4 7c0 2.2 3.6 4 8 4s8-1.8 8-4M4 7c0-2.2 3.6-4 8-4s8 1.8 8 4m0 5c0 2.2-3.6 4-8 4s-8-1.8-8-4"/></svg>
                                <span class="flex-1">{{ __('menus.backups') }}</span>
                            </a>
                        </li>
                        @endcan
                        @endif
                    </ul>
                </div>
                @endforeach
            </div>
        </nav>

        {{-- ── Section content (only the open section is submitted) ── --}}
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
              class="min-w-0 space-y-5" @input="changed = true" @change="changed = true" @submit="changed = false">
            @csrf @method('PUT')
            <input type="hidden" name="_section" :value="tab" value="{{ $initialTab }}">
            @if($hasErrors)
                <div role="alert" class="alert alert-danger">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <div>
                        <p class="font-semibold">{{ __('settings.correct_errors') }}</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Organization --}}
            <fieldset x-show="tab === 'org'" :disabled="tab !== 'org'" class="card" @if($initialTab !== 'org') style="display:none" @endif>
                <legend class="sr-only">{{ __('messages.organization') }}</legend>
                <div class="card-header"><div><h2 class="card-title">{{ __('messages.organization') }}</h2><p class="card-description">{{ __('settings.org_hint') }}</p></div></div>
                <div class="card-body space-y-5">
                    <div>
                        <label for="org_name" class="form-label">{{ __('messages.org_name') }}</label>
                        <input type="text" id="org_name" name="org[name]" value="{{ old('org.name', $settings['org.name']) }}" class="form-input">
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach([
                            ['logo', __('messages.logo'), '.jpg,.jpeg,.png,.webp', __('settings.logo_hint'), 'h-12 w-auto'],
                            ['favicon', __('settings.favicon'), '.ico,.jpg,.jpeg,.png,.webp', __('settings.favicon_hint'), 'h-10 w-10 object-contain p-1'],
                        ] as [$fileKey, $fileLabel, $accept, $fileHint, $previewClass])
                        @php $current = \App\Models\Setting::get("org.$fileKey", ''); @endphp
                        <div>
                            <label for="org_{{ $fileKey }}" class="form-label">{{ $fileLabel }}</label>
                            <div class="flex items-center gap-4 rounded-lg border border-dashed border-gray-300 p-3">
                                @if($current)
                                <img src="{{ Storage::url($current) }}" alt="" class="{{ $previewClass }} rounded-md border border-gray-200 bg-white">
                                @endif
                                <input type="file" id="org_{{ $fileKey }}" name="org[{{ $fileKey }}]" accept="{{ $accept }}"
                                       class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-muted file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand hover:file:bg-blue-100">
                            </div>
                            <p class="form-hint">{{ $fileHint }}</p>
                            @error("org.$fileKey")<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <label for="org_address" class="form-label">{{ __('fields.address') }}</label>
                        <textarea id="org_address" name="org[address]" rows="2" class="form-textarea">{{ old('org.address', $settings['org.address']) }}</textarea>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="org_phone" class="form-label">{{ __('fields.phone') }}</label>
                            <input type="text" id="org_phone" name="org[phone]" value="{{ old('org.phone', $settings['org.phone']) }}" class="form-input" placeholder="+251 …">
                        </div>
                        <div>
                            <label for="org_email" class="form-label">{{ __('fields.email') }}</label>
                            <input type="email" id="org_email" name="org[email]" value="{{ old('org.email', $settings['org.email']) }}" class="form-input">
                        </div>
                        <div>
                            <label for="org_website" class="form-label">{{ __('messages.website') }}</label>
                            <input type="url" id="org_website" name="org[website]" value="{{ old('org.website', $settings['org.website']) }}" class="form-input" placeholder="https://">
                        </div>
                        <div>
                            <label for="org_footer" class="form-label">{{ __('messages.footer_text') }}</label>
                            <input type="text" id="org_footer" name="org[footer_text]" value="{{ old('org.footer_text', $settings['org.footer_text']) }}" class="form-input">
                            <p class="form-hint">{{ __('settings.footer_text_hint') }}</p>
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- Social --}}
            <fieldset x-show="tab === 'social'" :disabled="tab !== 'social'" class="card" @if($initialTab !== 'social') style="display:none" @endif>
                <legend class="sr-only">{{ __('messages.social_media') }}</legend>
                <div class="card-header"><div><h2 class="card-title">{{ __('messages.social_media') }}</h2><p class="card-description">{{ __('settings.social_hint') }}</p></div></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    @foreach(['facebook' => 'Facebook', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $key => $label)
                    <div>
                        <label for="org_{{ $key }}" class="form-label">{{ $label }}</label>
                        <input type="url" id="org_{{ $key }}" name="org[{{ $key }}]" value="{{ old("org.$key", $settings["org.$key"]) }}" class="form-input" placeholder="https://">
                        @error("org.$key")<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Recruitment --}}
            <fieldset x-show="tab === 'recruitment'" :disabled="tab !== 'recruitment'" class="card" @if($initialTab !== 'recruitment') style="display:none" @endif>
                <legend class="sr-only">{{ __('menus.recruitment') }}</legend>
                <div class="card-header"><div><h2 class="card-title">{{ __('menus.recruitment') }}</h2><p class="card-description">{{ __('settings.recruitment_hint') }}</p></div></div>
                <div class="card-body space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="max_file_size" class="form-label">{{ __('settings.max_file_size') }}</label>
                            <input type="number" id="max_file_size" name="recruitment[max_file_size_mb]" min="1" max="10"
                                   value="{{ old('recruitment.max_file_size_mb', $settings['recruitment.max_file_size_mb']) }}" class="form-input">
                            <p class="form-hint">{{ __('settings.max_file_size_hint') }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                            <p class="text-gray-700">{{ __('settings.reference_format_location') }}</p>
                            <button type="button" @click="go('codes')" class="link-action mt-1.5">{{ __('settings.codes') }} →</button>
                        </div>
                    </div>
                    <input type="hidden" name="_present[recruitment_allowed_file_types]" value="1">
                    @php $allowedTypes = (array) old('recruitment.allowed_file_types', $settings['recruitment.allowed_file_types'] ?: ['pdf', 'jpg', 'jpeg', 'png']); @endphp
                    <div>
                        <p class="form-label" id="allowed-types-label">{{ __('settings.allowed_file_types') }}</p>
                        <div class="flex flex-wrap gap-2" role="group" aria-labelledby="allowed-types-label">
                            @foreach(['pdf' => 'PDF', 'jpg' => 'JPG', 'jpeg' => 'JPEG', 'png' => 'PNG'] as $type => $label)
                            <label class="{{ $check }} items-center">
                                <input type="checkbox" name="recruitment[allowed_file_types][]" value="{{ $type }}" @checked(in_array($type, $allowedTypes, true)) class="form-check">
                                <span class="text-sm font-medium text-gray-800">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('recruitment.allowed_file_types')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label for="allow_reg" class="{{ $check }}">
                            <input type="hidden" name="recruitment[allow_registration]" value="0">
                            <input type="checkbox" id="allow_reg" name="recruitment[allow_registration]" value="1"
                                   @checked(old('recruitment.allow_registration', $settings['recruitment.allow_registration'])) class="form-check mt-0.5">
                            <span><span class="block text-sm font-semibold text-gray-800">{{ __('settings.allow_registration') }}</span><span class="block text-[13px] text-gray-600">{{ __('settings.allow_registration_hint') }}</span></span>
                        </label>
                        <label for="show_archived_vacancies" class="{{ $check }} cursor-not-allowed bg-gray-50 opacity-80">
                            <input type="checkbox" id="show_archived_vacancies" disabled aria-describedby="archive-setting-note" value="1"
                                   @checked($settings['recruitment.show_archived_vacancies']) class="form-check mt-0.5">
                            <span><span class="block text-sm font-semibold text-gray-800">{{ __('settings.show_archived_vacancies') }}</span><span id="archive-setting-note" class="block text-[13px] text-gray-600">{{ __('settings.archive_unavailable') }}</span></span>
                        </label>
                    </div>
                </div>
            </fieldset>

            {{-- Codes (live preview while typing) --}}
            <fieldset x-show="tab === 'codes'" :disabled="tab !== 'codes'" class="space-y-5" @if($initialTab !== 'codes') style="display:none" @endif>
                <legend class="sr-only">{{ __('settings.codes') }}</legend>
                <div class="alert alert-info">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p>{{ __('settings.code_placeholders') }}: <code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-[13px]">{PREFIX} {YEAR} {YY} {MONTH} {DAY} {SEQ}</code></p>
                </div>
                @foreach([
                    'application' => [__('settings.code_application'), 'APP', 6],
                    'vacancy'     => [__('settings.code_vacancy'), 'VAC', 4],
                    'applicant'   => [__('settings.code_applicant'), 'APL', 5],
                ] as $type => [$codeTitle, $defPrefix, $defPadding])
                @php
                    $cPrefix  = old("codes.$type.prefix", $settings["codes.$type.prefix"]);
                    $cFormat  = old("codes.$type.format", $settings["codes.$type.format"]);
                    $cPadding = old("codes.$type.padding", $settings["codes.$type.padding"]);
                @endphp
                <section class="card"
                         x-data="{
                            prefix: @js((string) $cPrefix), format: @js((string) $cFormat), padding: @js((string) $cPadding),
                            get preview() {
                                const d = new Date(), pad = (n, l) => String(n).padStart(l, '0');
                                const len = Math.min(Math.max(parseInt(this.padding) || {{ $defPadding }}, 1), 10);
                                return (this.format || '{PREFIX}-{YEAR}-{SEQ}')
                                    .replaceAll('{PREFIX}', (this.prefix || @js($defPrefix)).toUpperCase())
                                    .replaceAll('{YEAR}', d.getFullYear()).replaceAll('{YY}', String(d.getFullYear()).slice(-2))
                                    .replaceAll('{MONTH}', pad(d.getMonth() + 1, 2)).replaceAll('{DAY}', pad(d.getDate(), 2))
                                    .replaceAll('{SEQ}', pad(1, len));
                            }
                         }">
                    <div class="card-header">
                        <h2 class="card-title">{{ $codeTitle }}</h2>
                        <p class="text-[13px] text-gray-600">{{ __('settings.code_preview') }}:
                            <span class="ml-1 rounded-md bg-gray-100 px-2 py-1 font-mono text-sm font-semibold text-gray-900" x-text="preview"></span>
                        </p>
                    </div>
                    <div class="card-body space-y-4">
                        @if($type === 'vacancy')
                        <label for="vacancy_auto" class="{{ $check }} max-w-md">
                            <input type="hidden" name="codes[vacancy][auto]" value="0">
                            <input type="checkbox" id="vacancy_auto" name="codes[vacancy][auto]" value="1"
                                   @checked(old('codes.vacancy.auto', $settings['codes.vacancy.auto'])) class="form-check mt-0.5">
                            <span class="text-sm font-semibold text-gray-800">{{ __('settings.code_auto_generate') }}</span>
                        </label>
                        @endif
                        <div class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <label for="code_{{ $type }}_prefix" class="form-label">{{ __('settings.code_prefix') }}</label>
                                <input type="text" id="code_{{ $type }}_prefix" name="codes[{{ $type }}][prefix]" x-model="prefix"
                                       value="{{ $cPrefix }}" class="form-input font-mono uppercase" placeholder="{{ $defPrefix }}">
                            </div>
                            <div>
                                <label for="code_{{ $type }}_format" class="form-label">{{ __('settings.code_format') }}</label>
                                <input type="text" id="code_{{ $type }}_format" name="codes[{{ $type }}][format]" x-model="format"
                                       value="{{ $cFormat }}" class="form-input font-mono" placeholder="{PREFIX}-{YEAR}-{SEQ}">
                            </div>
                            <div>
                                <label for="code_{{ $type }}_padding" class="form-label">{{ __('settings.code_padding') }}</label>
                                <input type="number" id="code_{{ $type }}_padding" name="codes[{{ $type }}][padding]" min="1" max="10" x-model="padding"
                                       value="{{ $cPadding }}" class="form-input">
                                <p class="form-hint">{{ __('settings.code_padding_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </section>
                @endforeach
            </fieldset>

            {{-- Result weights: exam + interview + practical test = 100% --}}
            @php
                $weightParts = [
                    'exam'      => ['exam_weight', __('messages.exam_weight'), 60, 'bg-brand'],
                    'interview' => ['interview_weight', __('messages.interview_weight'), 40, 'bg-accent'],
                    'practical' => ['practical_weight', __('messages.practical_weight'), 0, 'bg-emerald-600'],
                ];
            @endphp
            <fieldset x-show="tab === 'results'" :disabled="tab !== 'results'" class="card" @if($initialTab !== 'results') style="display:none" @endif
                      x-data="{
                          @foreach($weightParts as $part => [$key, $label, $default])
                          {{ $part }}: {{ \Illuminate\Support\Js::from((float) old('results.'.$key, $settings['results.'.$key] ?? $default)) }},
                          @endforeach
                          get total() { return Math.round(((parseFloat(this.exam) || 0) + (parseFloat(this.interview) || 0) + (parseFloat(this.practical) || 0)) * 100) / 100; },
                          pct(v) { return Math.max(0, parseFloat(v) || 0); }
                      }">
                <legend class="sr-only">{{ __('messages.result_weights') }}</legend>
                <div class="card-header">
                    <div><h2 class="card-title">{{ __('messages.result_weights') }}</h2><p class="card-description">{{ __('messages.result_weights_hint') }}</p></div>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold" role="status"
                          :class="total === 100 ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900'"
                          x-text="@js(__('messages.weights_total')).replace(':total', total)"></span>
                </div>
                <div class="card-body space-y-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        @foreach($weightParts as $part => [$key, $label, $default, $bar])
                        <div>
                            <label for="{{ $key }}" class="form-label flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full {{ $bar }}" aria-hidden="true"></span>
                                {{ $label }} (%)
                            </label>
                            <input type="number" id="{{ $key }}" name="results[{{ $key }}]" x-model="{{ $part }}"
                                   min="0" max="100" step="0.01" value="{{ old('results.'.$key, $settings['results.'.$key] ?? $default) }}"
                                   class="form-input @error('results.'.$key) form-input-error @enderror">
                            @error('results.'.$key)<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                    </div>
                    <div aria-hidden="true">
                        <div class="flex h-3 overflow-hidden rounded-full bg-gray-100">
                            @foreach($weightParts as $part => [$key, $label, $default, $bar])
                            <div class="{{ $bar }} transition-all" :style="'width:' + Math.min(pct({{ $part }}), 100) + '%'"></div>
                            @endforeach
                        </div>
                    </div>
                    <p class="form-hint" x-show="total !== 100" x-cloak>{{ __('messages.weights_should_total') }}</p>
                    <p class="form-hint">{{ __('messages.practical_weight_hint') }}</p>
                </div>
            </fieldset>

            {{-- Localization --}}
            <fieldset x-show="tab === 'localization'" :disabled="tab !== 'localization'" class="card" @if($initialTab !== 'localization') style="display:none" @endif>
                <legend class="sr-only">{{ __('messages.localization') }}</legend>
                <div class="card-header"><div><h2 class="card-title">{{ __('messages.localization') }}</h2><p class="card-description">{{ __('settings.localization_hint') }}</p></div></div>
                <div class="card-body space-y-5">
                    <input type="hidden" name="_present[app_available_locales]" value="1">
                    @php $availableLocales = (array) old('app.available_locales', $settings['app.available_locales'] ?: ['en', 'am']); @endphp
                    <div>
                        <p class="form-label" id="available-locales-label">{{ __('settings.available_languages') }}</p>
                        <div class="flex flex-wrap gap-2" role="group" aria-labelledby="available-locales-label">
                            @foreach(['en' => 'English', 'am' => 'አማርኛ'] as $locale => $label)
                            <label class="{{ $check }} items-center">
                                <input type="checkbox" name="app[available_locales][]" value="{{ $locale }}" @checked(in_array($locale, $availableLocales, true)) class="form-check">
                                <span class="text-sm font-medium text-gray-800" lang="{{ $locale }}">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('app.available_locales')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="default_locale" class="form-label">{{ __('settings.default_language') }}</label>
                            <select id="default_locale" name="localization[default_locale]" class="form-select">
                                <option value="en" @selected(old('localization.default_locale', $settings['localization.default_locale'] ?? 'en') === 'en')>English</option>
                                <option value="am" @selected(old('localization.default_locale', $settings['localization.default_locale'] ?? '') === 'am')>አማርኛ</option>
                            </select>
                            <p class="form-hint">{{ __('settings.default_language_hint') }}</p>
                        </div>
                        <div>
                            <label for="fallback_locale" class="form-label">{{ __('settings.fallback_language') }}</label>
                            <select id="fallback_locale" name="app[fallback_locale]" class="form-select">
                                <option value="en" @selected(old('app.fallback_locale', $settings['app.fallback_locale'] ?? 'en') === 'en')>English</option>
                                <option value="am" @selected(old('app.fallback_locale', $settings['app.fallback_locale'] ?? '') === 'am')>አማርኛ</option>
                            </select>
                            <p class="form-hint">{{ __('settings.fallback_language_hint') }}</p>
                        </div>
                        <div>
                            <label for="date_format" class="form-label">{{ __('settings.date_format') }}</label>
                            <select id="date_format" name="app[date_format]" class="form-select">
                                @foreach(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d, Y'] as $format)
                                <option value="{{ $format }}" @selected(old('app.date_format', $settings['app.date_format'] ?? 'Y-m-d') === $format)>{{ now()->format($format) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="show_language_switcher" class="{{ $check }} max-w-xl">
                        <input type="hidden" name="localization[show_language_switcher]" value="0">
                        <input type="checkbox" id="show_language_switcher" name="localization[show_language_switcher]" value="1"
                               @checked(old('localization.show_language_switcher', $settings['localization.show_language_switcher'] ?? true)) class="form-check mt-0.5">
                        <span><span class="block text-sm font-semibold text-gray-800">{{ __('settings.show_language_switcher') }}</span><span class="block text-[13px] text-gray-600">{{ __('settings.show_language_switcher_hint') }}</span></span>
                    </label>
                </div>
            </fieldset>

            {{-- Notifications --}}
            <fieldset x-show="tab === 'notifications'" :disabled="tab !== 'notifications'" class="card" @if($initialTab !== 'notifications') style="display:none" @endif>
                <legend class="sr-only">{{ __('menus.notifications') }}</legend>
                <div class="card-header"><div><h2 class="card-title">{{ __('menus.notifications') }}</h2><p class="card-description">{{ __('settings.mail_transport_hint') }}</p></div></div>
                <div class="card-body space-y-5">
                    @php $mailLocal = in_array(config('mail.default'), ['log', 'array', null], true); @endphp
                    <div class="alert {{ $mailLocal ? 'alert-warning' : 'alert-success' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>{{ $mailLocal ? __('settings.mail_delivery_local') : __('settings.mail_delivery_configured') }}</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="mail_from_name" class="form-label">{{ __('settings.email_sender_name') }}</label>
                            <input type="text" id="mail_from_name" name="mail[from_name]" value="{{ old('mail.from_name', $settings['mail.from_name']) }}" class="form-input">
                        </div>
                        <div>
                            <label for="mail_from_address" class="form-label">{{ __('settings.email_sender_address') }}</label>
                            <input type="email" id="mail_from_address" name="mail[from_address]" value="{{ old('mail.from_address', $settings['mail.from_address']) }}" class="form-input">
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- Security --}}
            <fieldset x-show="tab === 'security'" :disabled="tab !== 'security'" class="space-y-5" @if($initialTab !== 'security') style="display:none" @endif>
                <legend class="sr-only">{{ __('messages.security') }}</legend>
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">{{ __('messages.security') }}</h2><p class="card-description">{{ __('settings.security_hint') }}</p></div></div>
                    <div class="card-body grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="session_timeout" class="form-label">{{ __('settings.session_timeout') }}</label>
                            <input type="number" id="session_timeout" name="security[session_timeout]" min="5" value="{{ old('security.session_timeout', $settings['security.session_timeout']) }}" class="form-input">
                            <p class="form-hint">{{ __('settings.session_timeout_hint') }}</p>
                        </div>
                        <div>
                            <label for="login_attempts" class="form-label">{{ __('settings.login_attempts') }}</label>
                            <input type="number" id="login_attempts" name="security[login_attempts]" min="3" value="{{ old('security.login_attempts', $settings['security.login_attempts']) }}" class="form-input">
                            <p class="form-hint">{{ __('settings.login_attempts_hint') }}</p>
                        </div>
                    </div>
                </section>

                @php
                    $mfaMethods = (array) old('security.mfa_methods_allowed', $settings['security.mfa_methods_allowed'] ?: ['totp']);
                    $mfaRequiredRoles = (array) old('security.mfa_required_roles', $settings['security.mfa_required_roles'] ?: []);
                @endphp
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">{{ __('settings.mfa_management') }}</h2><p class="card-description">{{ __('settings.mfa_management_hint') }}</p></div></div>
                    <div class="card-body space-y-5">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach([
                                'mfa_enabled' => __('settings.mfa_enabled'),
                                'mfa_required_for_admins' => __('settings.mfa_required_for_admins'),
                                'mfa_required_for_applicants' => __('settings.mfa_required_for_applicants'),
                            ] as $key => $label)
                            <label for="security_{{ $key }}" class="{{ $check }}">
                                <input type="hidden" name="security[{{ $key }}]" value="0">
                                <input type="checkbox" id="security_{{ $key }}" name="security[{{ $key }}]" value="1"
                                       @checked(old("security.{$key}", $settings["security.{$key}"])) class="form-check mt-0.5">
                                <span><span class="block text-sm font-semibold text-gray-800">{{ $label }}</span><span class="block text-[13px] text-gray-600">{{ __("settings.{$key}_hint") }}</span></span>
                            </label>
                            @endforeach
                        </div>

                        <div>
                            <p class="form-label" id="mfa-roles-label">{{ __('settings.mfa_required_roles') }}</p>
                            <p class="mb-2 text-xs text-gray-600">{{ __('settings.mfa_required_roles_hint') }}</p>
                            {{-- Submit an empty value so unchecking every role persists an empty list. --}}
                            <input type="hidden" name="security[mfa_required_roles][]" value="">
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="group" aria-labelledby="mfa-roles-label">
                                @foreach($assignableRoles as $roleName)
                                <label class="{{ $check }} items-center">
                                    <input type="checkbox" name="security[mfa_required_roles][]" value="{{ $roleName }}" @checked(in_array($roleName, $mfaRequiredRoles, true)) class="form-check">
                                    <span class="text-sm font-medium text-gray-800">{{ \Illuminate\Support\Str::headline($roleName) }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <input type="hidden" name="_present[security_mfa_methods_allowed]" value="1">
                                <p class="form-label">{{ __('settings.mfa_methods_allowed') }}</p>
                                <label class="{{ $check }} items-center">
                                    <input type="checkbox" name="security[mfa_methods_allowed][]" value="totp" @checked(in_array('totp', $mfaMethods, true)) class="form-check">
                                    <span class="text-sm font-medium text-gray-800">{{ __('settings.mfa_method_totp') }}</span>
                                </label>
                                <p class="form-hint">{{ __('settings.mfa_methods_allowed_hint') }}</p>
                            </div>
                            <div>
                                <label for="mfa_remember" class="form-label">{{ __('settings.mfa_remember_device_days') }}</label>
                                <input type="number" id="mfa_remember" name="security[mfa_remember_device_days]" min="0" max="365"
                                       value="{{ old('security.mfa_remember_device_days', $settings['security.mfa_remember_device_days']) }}" class="form-input">
                                <p class="form-hint">{{ __('settings.mfa_remember_device_days_hint') }}</p>
                            </div>
                            <div>
                                <label for="mfa_issuer" class="form-label">{{ __('settings.mfa_issuer_name') }}</label>
                                <input type="text" id="mfa_issuer" name="security[mfa_issuer_name]"
                                       value="{{ old('security.mfa_issuer_name', $settings['security.mfa_issuer_name']) }}" class="form-input">
                                <p class="form-hint">{{ __('settings.mfa_issuer_name_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                @foreach([
                    'admin' => [__('settings.admin_password_policy'), __('settings.admin_password_policy_hint')],
                    'applicant' => [__('settings.applicant_password_policy'), __('settings.applicant_password_policy_hint')],
                ] as $scope => [$policyTitle, $policyHint])
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
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">{{ $policyTitle }}</h2><p class="card-description">{{ $policyHint }}</p></div></div>
                    <div class="card-body space-y-5">
                        <div class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <label for="security_{{ $prefix }}_min_length" class="form-label">{{ __('settings.minimum_password_length') }}</label>
                                <input type="number" id="security_{{ $prefix }}_min_length" name="security[{{ $prefix }}_min_length]" min="8" max="128"
                                       value="{{ old("security.{$prefix}_min_length", $settings["security.{$prefix}_min_length"]) }}" class="form-input">
                                <p class="form-hint">{{ __('settings.minimum_password_length_hint') }}</p>
                            </div>
                            {{-- Not enforced yet: shown read-only so admins aren't misled --}}
                            @foreach([
                                'expiry_days' => [__('settings.password_expiry_days'), __('settings.password_expiry_days_hint'), 3650],
                                'history_count' => [__('settings.password_history_count'), __('settings.password_history_count_hint'), 24],
                            ] as $field => [$fieldLabel, $fieldHint, $max])
                            <div>
                                <label for="security_{{ $prefix }}_{{ $field }}" class="form-label">{{ $fieldLabel }}</label>
                                <input type="number" id="security_{{ $prefix }}_{{ $field }}" disabled aria-readonly="true" min="1" max="{{ $max }}"
                                       value="{{ old("security.{$prefix}_{$field}", $settings["security.{$prefix}_{$field}"]) }}"
                                       placeholder="{{ __('settings.optional') }}" class="form-input">
                                <p class="form-hint">{{ $fieldHint }}</p>
                            </div>
                            @endforeach
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($toggles as $key => $label)
                            @php $inputId = "security_{$prefix}_{$key}"; @endphp
                            <label for="{{ $inputId }}" class="{{ $check }}">
                                <input type="hidden" name="security[{{ $prefix }}_{{ $key }}]" value="0">
                                <input type="checkbox" id="{{ $inputId }}" name="security[{{ $prefix }}_{{ $key }}]" value="1"
                                       @checked(old("security.{$prefix}_{$key}", $settings["security.{$prefix}_{$key}"])) class="form-check mt-0.5">
                                <span><span class="block text-sm font-semibold text-gray-800">{{ $label }}</span><span class="block text-[13px] text-gray-600">{{ __("settings.{$key}_hint") }}</span></span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </fieldset>

            {{-- Appearance --}}
            @php
                $safeAppearanceColor = static function (mixed $value, string $fallback): string {
                    $value = (string) $value;
                    return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
                };
                $savedAppearance = [
                    'primary' => $safeAppearanceColor($settings['appearance.primary_color'] ?? '#1A56DB', '#1A56DB'),
                    'sidebar' => $safeAppearanceColor($settings['appearance.sidebar_color'] ?? '#1E3A8A', '#1E3A8A'),
                    'accent'  => $safeAppearanceColor($settings['appearance.accent_color'] ?? '#FF6B2B', '#FF6B2B'),
                    'logoSize' => min(max((int) ($settings['appearance.logo_size'] ?: 36), 24), 72),
                ];
                $appearancePrimary = $safeAppearanceColor(old('appearance.primary_color', $savedAppearance['primary']), '#1A56DB');
                $appearanceSidebar = $safeAppearanceColor(old('appearance.sidebar_color', $savedAppearance['sidebar']), '#1E3A8A');
                $appearanceAccent = $safeAppearanceColor(old('appearance.accent_color', $savedAppearance['accent']), '#FF6B2B');
                $appearanceLogoSize = min(max((int) old('appearance.logo_size', $savedAppearance['logoSize']), 24), 72);
                $appearanceLogo = \App\Models\Setting::get('org.logo', '');
                $appearanceOrg = \App\Models\Setting::get('org.name', config('app.name'));
                $colorFields = [
                    'primary' => ['primary_color', __('settings.appearance_primary_color'), __('settings.appearance_used_primary'), '#1A56DB'],
                    'sidebar' => ['sidebar_color', __('settings.appearance_sidebar_color'), __('settings.appearance_used_sidebar'), '#1E3A8A'],
                    'accent'  => ['accent_color', __('settings.appearance_accent_color'), __('settings.appearance_used_accent'), '#FF6B2B'],
                ];
            @endphp
            <fieldset x-show="tab === 'appearance'" :disabled="tab !== 'appearance'" class="space-y-5" @if($initialTab !== 'appearance') style="display:none" @endif
                      x-data="{
                          primary: @js($appearancePrimary),
                          sidebar: @js($appearanceSidebar),
                          accent:  @js($appearanceAccent),
                          logoSize: {{ $appearanceLogoSize }},
                          saved: @js($savedAppearance),
                          defaults: { primary: '#1A56DB', sidebar: '#1E3A8A', accent: '#FF6B2B', logoSize: 36 },
                          presets: [
                              { name: 'Blue',    primary: '#1A56DB', sidebar: '#1E3A8A', accent: '#FF6B2B' },
                              { name: 'Emerald', primary: '#047857', sidebar: '#064E3B', accent: '#D97706' },
                              { name: 'Purple',  primary: '#6D28D9', sidebar: '#3B0764', accent: '#DB2777' },
                              { name: 'Rose',    primary: '#BE123C', sidebar: '#4C0519', accent: '#EA580C' },
                              { name: 'Teal',    primary: '#0F766E', sidebar: '#134E4A', accent: '#EA580C' },
                              { name: 'Slate',   primary: '#334155', sidebar: '#0F172A', accent: '#0891B2' },
                          ],
                          isHex(v) { return /^#[0-9A-Fa-f]{6}$/.test(v || ''); },
                          safe(v, fallback) { return this.isHex(v) ? v : fallback; },
                          same(a, b) { return String(a).toUpperCase() === String(b).toUpperCase(); },
                          isPreset(p) { return this.same(p.primary, this.primary) && this.same(p.sidebar, this.sidebar) && this.same(p.accent, this.accent); },
                          isSaved(p) { return this.same(p.primary, this.saved.primary) && this.same(p.sidebar, this.saved.sidebar) && this.same(p.accent, this.saved.accent); },
                          get isCustom() { return !this.presets.some(p => this.isPreset(p)); },
                          get dirtyTheme() { return !(this.same(this.primary, this.saved.primary) && this.same(this.sidebar, this.saved.sidebar) && this.same(this.accent, this.saved.accent) && this.logoSize == this.saved.logoSize); },
                          // WCAG contrast of white text on the colour
                          luminance(hex) {
                              const ch = (i) => { const c = parseInt(hex.substr(i, 2), 16) / 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
                              return 0.2126 * ch(1) + 0.7152 * ch(3) + 0.0722 * ch(5);
                          },
                          contrast(hex) {
                              if (!this.isHex(hex)) return 0;
                              return Math.round((1.05 / (this.luminance(hex) + 0.05)) * 10) / 10;
                          },
                          // Bubbles to the form, which marks the section as having unsaved changes.
                          apply(values) { Object.assign(this, values); this.preview(); this.$dispatch('input'); },
                          preview() {
                              const root = document.documentElement.style;
                              const set = (name, hex) => {
                                  if (!this.isHex(hex)) return;
                                  root.setProperty('--color-' + name, hex);
                                  root.setProperty('--color-' + name + '-dark', 'color-mix(in srgb, ' + hex + ' 80%, black)');
                                  root.setProperty('--color-' + name + '-muted', 'color-mix(in srgb, ' + hex + ' 12%, white)');
                              };
                              set('brand', this.primary); set('navy', this.sidebar); set('accent', this.accent);
                              // Same rule as partials.admin-theme: light sidebars get dark text.
                              if (this.isHex(this.sidebar)) {
                                  document.documentElement.classList.toggle('sidebar-light', this.luminance(this.sidebar) > 0.45);
                              }
                          },
                          contrastText(hex) {
                              const r = this.contrast(hex);
                              return (r >= 4.5 ? @js(__('settings.contrast_good')) : @js(__('settings.contrast_low'))).replace(':ratio', r.toFixed(1));
                          }
                      }">
                <legend class="sr-only">{{ __('settings.appearance') }}</legend>

                {{-- Themes --}}
                <section class="card">
                    <div class="card-header">
                        <div><h2 class="card-title">{{ __('settings.appearance_presets') }}</h2><p class="card-description">{{ __('settings.appearance_hint') }}</p></div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-show="dirtyTheme" x-cloak @click="apply({ ...saved })" class="btn btn-ghost btn-sm">{{ __('settings.appearance_undo') }}</button>
                            <button type="button" @click="apply({ ...defaults })" class="btn btn-secondary btn-sm">{{ __('settings.appearance_reset_defaults') }}</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-6" role="radiogroup" aria-label="{{ __('settings.appearance_presets') }}">
                            <template x-for="p in presets" :key="p.name">
                                <button type="button" role="radio" :aria-checked="isPreset(p).toString()" @click="apply({ primary: p.primary, sidebar: p.sidebar, accent: p.accent })"
                                        :class="isPreset(p) ? 'border-brand ring-2 ring-brand/25' : 'border-gray-200 hover:border-gray-300'"
                                        class="relative overflow-hidden rounded-xl border bg-white text-left transition">
                                    <span class="flex h-12">
                                        <span class="w-1/2" :style="'background:' + p.sidebar"></span>
                                        <span class="w-1/3" :style="'background:' + p.primary"></span>
                                        <span class="flex-1" :style="'background:' + p.accent"></span>
                                    </span>
                                    <span class="flex items-center justify-between gap-2 px-3 py-2">
                                        <span class="text-sm font-semibold text-gray-900" x-text="p.name"></span>
                                        <span x-show="isSaved(p)" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-700">{{ __('settings.appearance_saved') }}</span>
                                    </span>
                                    <span x-show="isPreset(p)" class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-white text-brand shadow" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                        <p x-show="isCustom" x-cloak class="form-hint">{{ __('settings.appearance_custom_theme') }}</p>
                    </div>
                </section>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                    {{-- Colours & logo --}}
                    <section class="card">
                        <div class="card-header"><div><h2 class="card-title">{{ __('settings.appearance_colors') }}</h2><p class="card-description">{{ __('settings.appearance_colors_hint') }}</p></div></div>
                        <div class="card-body divide-y divide-gray-100">
                            @foreach($colorFields as $model => [$field, $label, $usedFor, $ph])
                            <div class="grid gap-3 py-4 first:pt-0 sm:grid-cols-[minmax(0,1fr)_13rem] sm:items-start">
                                <div>
                                    <label for="appearance_{{ $field }}" class="text-sm font-semibold text-gray-900">{{ $label }}</label>
                                    <p class="mt-0.5 text-[13px] text-gray-600">{{ $usedFor }}</p>
                                    <p class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold"
                                       :class="contrast({{ $model }}) >= 4.5 ? 'text-green-800' : 'text-amber-900'" x-show="isHex({{ $model }})">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                        <span x-text="contrastText({{ $model }})"></span>
                                    </p>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="safe({{ $model }}, @js($ph))" @input="{{ $model }} = $event.target.value.toUpperCase(); preview()"
                                               aria-label="{{ $label }}" class="h-10 w-11 shrink-0 cursor-pointer rounded-lg border border-gray-300 bg-white p-0.5">
                                        <input type="text" id="appearance_{{ $field }}" name="appearance[{{ $field }}]" x-model="{{ $model }}" @input="preview()"
                                               maxlength="7" placeholder="{{ $ph }}" spellcheck="false" autocomplete="off"
                                               :class="isHex({{ $model }}) ? '' : 'form-input-error'" :aria-invalid="(!isHex({{ $model }})).toString()"
                                               class="form-input font-mono uppercase">
                                    </div>
                                    <p x-show="!isHex({{ $model }})" x-cloak class="form-error">{{ __('settings.appearance_invalid_hex') }}</p>
                                    @error("appearance.$field")<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            @endforeach

                            <div class="grid gap-3 pt-4 sm:grid-cols-[minmax(0,1fr)_13rem] sm:items-start">
                                <div>
                                    <label for="appearance_logo_size" class="text-sm font-semibold text-gray-900">{{ __('settings.logo_size') }}</label>
                                    <p class="mt-0.5 text-[13px] text-gray-600">{{ __('settings.logo_size_hint') }}</p>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <input type="range" min="24" max="72" step="1" x-model.number="logoSize" aria-label="{{ __('settings.logo_size') }}" class="w-full accent-brand">
                                        <input type="number" id="appearance_logo_size" name="appearance[logo_size]" min="24" max="72" x-model.number="logoSize" class="form-input w-20">
                                    </div>
                                    <p class="form-hint"><span x-text="logoSize"></span> px</p>
                                    @error('appearance.logo_size')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Live preview --}}
                    <section class="card" aria-labelledby="appearance-preview-title">
                        <div class="card-header"><div><h2 id="appearance-preview-title" class="card-title">{{ __('settings.appearance_preview') }}</h2><p class="card-description">{{ __('settings.appearance_preview_hint') }}</p></div></div>
                        <div class="card-body space-y-4" aria-hidden="true">
                            {{-- Admin panel --}}
                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-600">{{ __('settings.appearance_preview_admin') }}</p>
                                <div class="flex overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                    {{-- Uses the same --sb-* variables as the real sidebar --}}
                                    <div class="w-40 shrink-0 space-y-1 p-2" style="background: linear-gradient(180deg, color-mix(in srgb, var(--color-navy) 90%, white), var(--color-navy) 38%, var(--color-navy-dark));">
                                        <div class="mb-2 flex items-center gap-2 px-1">
                                            @if($appearanceLogo)
                                            <img src="{{ Storage::url($appearanceLogo) }}" alt="" class="shrink-0 object-contain" :style="'width:' + Math.min(logoSize, 40) + 'px;height:' + Math.min(logoSize, 40) + 'px'">
                                            @else
                                            <span class="flex shrink-0 items-center justify-center rounded text-[10px] font-bold" style="color: var(--sb-text); border: 1px solid var(--sb-line);" :style="'width:' + Math.min(logoSize, 40) + 'px;height:' + Math.min(logoSize, 40) + 'px'">{{ mb_substr($appearanceOrg, 0, 2) }}</span>
                                            @endif
                                            <span class="truncate text-[11px] font-bold" style="color: var(--sb-text)">{{ $appearanceOrg }}</span>
                                        </div>
                                        <span class="relative flex items-center rounded-md px-2 py-1.5 text-[11px] font-semibold" style="color: var(--sb-active-text); background: var(--sb-active-bg);">
                                            <span class="absolute inset-y-1 left-0 w-0.5 rounded-full" style="background: var(--color-accent)"></span>
                                            {{ __('menus.vacancies') }}
                                        </span>
                                        <span class="block px-2 py-1.5 text-[11px]" style="color: var(--sb-muted)">{{ __('menus.applications') }}</span>
                                        <span class="block px-2 py-1.5 text-[11px]" style="color: var(--sb-muted)">{{ __('menus.screening') }}</span>
                                    </div>
                                    <div class="flex-1 space-y-2.5 p-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[13px] font-bold text-gray-900">{{ __('menus.vacancies') }}</span>
                                            <span class="rounded-md px-2.5 py-1 text-[11px] font-semibold text-white" :style="'background:' + safe(primary, '#1A56DB')">{{ __('settings.preview_primary_button') }}</span>
                                        </div>
                                        <div class="rounded-md border border-gray-200 bg-white p-2.5 text-[11px]">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold underline" :style="'color:' + safe(primary, '#1A56DB')">{{ __('settings.preview_link_color') }}</span>
                                                <span class="rounded-full bg-green-50 px-2 py-0.5 font-semibold text-green-800">{{ __('settings.appearance_preview_status') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Public site --}}
                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-600">{{ __('settings.appearance_preview_public') }}</p>
                                <div class="overflow-hidden rounded-lg border border-gray-200">
                                    <div class="px-4 py-5 text-white" :style="'background:' + safe(sidebar, '#1E3A8A')">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80">{{ $appearanceOrg }}</p>
                                        <p class="mt-1 text-base font-bold">{{ __('public.hero_subtitle') }}</p>
                                        <div class="mt-3 flex gap-2 rounded-lg bg-white p-1.5">
                                            <span class="flex-1 rounded-md bg-gray-100 px-2 py-1.5 text-[11px] text-gray-600">{{ __('public.search') }}…</span>
                                            <span class="rounded-md px-3 py-1.5 text-[11px] font-bold text-white" :style="'background:' + safe(accent, '#FF6B2B')">{{ __('public.search_jobs') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between bg-white px-4 py-3 text-[11px]">
                                        <span class="font-semibold text-gray-900">{{ __('public.open_vacancies') }}</span>
                                        <span class="font-semibold" :style="'color:' + safe(primary, '#1A56DB')">{{ __('public.view_all_vacancies') }} →</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </fieldset>

            {{-- Pinned save bar: saves the open section only --}}
            <div class="sticky bottom-0 z-20 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
                <p class="flex items-center gap-2 text-sm" role="status">
                    <span class="h-2 w-2 shrink-0 rounded-full" :class="changed ? 'bg-amber-500' : 'bg-gray-300'" aria-hidden="true"></span>
                    <span x-show="changed" x-cloak class="font-semibold text-amber-900">{{ __('settings.unsaved_changes') }}</span>
                    <span x-show="!changed" class="text-gray-600">{{ __('settings.section_save_hint') }}</span>
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.settings.index') }}" data-admin-no-spa x-show="changed" x-cloak class="btn btn-ghost">{{ __('settings.discard_changes') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('settings.save_section') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
