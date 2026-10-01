@extends('layouts.public')

@section('title', __('applicant.register_heading'))

@section('content')
@php
    $orgName  = \App\Models\Setting::get('org.name', config('app.name'));
    $orgPhone = \App\Models\Setting::get('org.phone', '');
    $orgEmail = \App\Models\Setting::get('org.email', '');
    $maxMb    = (int) \App\Models\Setting::get('recruitment.max_file_size_mb', 2);
    $docTypes = array_values(array_map('strtolower', (array) \App\Models\Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png'])));
    $docTypesLabel = implode(', ', array_map('strtoupper', array_unique(array_map(fn ($t) => $t === 'jpeg' ? 'jpg' : $t, $docTypes))));

    $steps = [
        1 => ['label' => __('applicant.step_1_heading'), 'desc' => __('applicant.step_1_desc'), 'icon' => 'user'],
        2 => ['label' => __('applicant.step_2_heading'), 'desc' => __('applicant.step_2_desc'), 'icon' => 'academic'],
        3 => ['label' => __('applicant.step_3_heading'), 'desc' => __('applicant.step_3_desc'), 'icon' => 'briefcase'],
        4 => ['label' => __('applicant.step_4_heading'), 'desc' => __('applicant.step_4_desc'), 'icon' => 'mail'],
        5 => ['label' => __('applicant.step_5_heading'), 'desc' => __('applicant.step_5_desc'), 'icon' => 'paperclip'],
        6 => ['label' => __('applicant.step_6_heading'), 'desc' => __('applicant.step_6_desc'), 'icon' => 'check-circle'],
    ];

    $errorStep = 1;
    if ($errors->any()) {
        if ($errors->hasAny(['phone','email','password','password_confirmation','preferred_locale'])) {
            $errorStep = 4;
        } elseif ($errors->hasAny(['documents','profile_photo'])) {
            $errorStep = 5;
        } elseif ($errors->hasAny(['work_experience_years','work_experience_months','current_employer','current_position','work_experience_summary'])) {
            $errorStep = 3;
        } elseif ($errors->hasAny(['university_name','field_of_study','graduation_year','gpa','education_level'])) {
            $errorStep = 2;
        }
    }

    $input  = 'mt-1.5 h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-base text-gray-900 transition placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20';
    $label  = 'block text-sm font-semibold text-gray-800';
    $req    = '<span class="text-red-600" aria-hidden="true">*</span>';
    $optTag = '<span class="ml-1 text-xs font-medium text-gray-500">('.e(__('applicant.optional')).')</span>';
    $err    = 'mt-1.5 text-sm text-red-600';
    // Nobody younger than Applicant::MINIMUM_AGE can be picked or submitted.
    $latestDob = \App\Models\Applicant::latestBirthDate()->toDateString();
@endphp

{{-- ── Title band ── --}}
<x-public.page-header :title="__('applicant.register_heading')"
                      :subtitle="__('applicant.register_subheading')"
                      :crumbs="[['label' => __('public.create_account')]]"
                      width="max-w-6xl">
    <p class="mt-3 text-[15px] text-gray-700">
        {{ __('applicant.already_have_account') }}
        <a href="{{ route('login') }}" class="font-bold text-brand hover:underline">{{ __('applicant.sign_in_link') }}</a>
    </p>
</x-public.page-header>

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8" x-data="registrationForm()" x-cloak>
    <div class="lg:grid lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start lg:gap-10">

        {{-- ── Sidebar: steps + what you need ── --}}
        <aside class="hidden lg:sticky lg:top-24 lg:block">
            <nav aria-label="{{ __('applicant.step_progress_label') }}">
                <ol class="space-y-1">
                    @foreach($steps as $n => $s)
                    <li>
                        <button type="button" @click="goToStep({{ $n }})"
                                :disabled="{{ $n }} > maxStep"
                                :aria-current="step === {{ $n }} ? 'step' : false"
                                class="group flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left transition disabled:cursor-default"
                                :class="step === {{ $n }} ? 'bg-white ring-1 ring-gray-200' : ({{ $n }} <= maxStep ? 'hover:bg-white/70' : '')">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-extrabold transition"
                                  :class="step === {{ $n }} ? 'bg-brand text-white' : ({{ $n }} < maxStep || ({{ $n }} < step) ? 'bg-brand-muted text-brand-dark' : 'bg-gray-200 text-gray-600')">
                                <template x-if="{{ $n }} < step"><x-public.icon name="check" class="h-4 w-4" /></template>
                                <template x-if="{{ $n }} >= step"><span>{{ $n }}</span></template>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[15px] font-bold" :class="step === {{ $n }} ? 'text-gray-900' : 'text-gray-700'">{{ $s['label'] }}</span>
                                <span class="block text-[13px] leading-snug text-gray-500">{{ $s['desc'] }}</span>
                            </span>
                        </button>
                    </li>
                    @endforeach
                </ol>
            </nav>

            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-[15px] font-extrabold text-gray-900">{{ __('applicant.before_you_start') }}</h2>
                <ul class="mt-3 space-y-2.5 text-sm text-gray-700">
                    <li class="flex gap-2.5"><x-public.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />{{ __('applicant.need_national_id') }}</li>
                    <li class="flex gap-2.5"><x-public.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />{{ __('applicant.need_contact') }}</li>
                    <li class="flex gap-2.5"><x-public.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />{{ __('applicant.need_document', ['types' => $docTypesLabel, 'size' => $maxMb]) }}</li>
                </ul>
                <p class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-3 text-sm text-gray-600">
                    <x-public.icon name="clock" class="h-4 w-4 text-gray-400" />
                    {{ __('applicant.takes_minutes') }}
                </p>
            </div>

            @if($orgPhone || $orgEmail)
            <div class="mt-4 px-1 text-sm text-gray-600">
                <p class="font-bold text-gray-800">{{ __('applicant.need_help') }}</p>
                @if($orgPhone)<p class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgPhone) }}" class="text-brand hover:underline">{{ $orgPhone }}</a></p>@endif
                @if($orgEmail)<p class="mt-0.5 break-all"><a href="mailto:{{ $orgEmail }}" class="text-brand hover:underline">{{ $orgEmail }}</a></p>@endif
            </div>
            @endif
        </aside>

        {{-- ── Form column ── --}}
        <div class="min-w-0">

            {{-- Mobile progress --}}
            <div class="mb-5 lg:hidden" role="progressbar" aria-valuemin="1" aria-valuemax="6" :aria-valuenow="step"
                 aria-label="{{ __('applicant.step_progress_label') }}">
                <div class="mb-2 flex items-baseline justify-between gap-3 text-sm">
                    <span class="font-bold text-gray-900" x-text="stepLabels[step]"></span>
                    <span class="text-gray-600">{{ __('applicant.step_short') }} <span x-text="step"></span>/6</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                    <div class="h-full rounded-full bg-brand transition-all duration-300" :style="`width: ${Math.round(step / 6 * 100)}%`"></div>
                </div>
            </div>

            {{-- Validation errors (server-side) --}}
            @if($errors->any())
            <div id="reg-error-summary" role="alert" tabindex="-1" x-init="$el.focus()"
                 class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="mb-1 flex items-center gap-2 font-bold"><x-public.icon name="alert" class="h-4 w-4" />{{ __('applicant.fix_errors_heading') }}</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- "submitting" is set on the form's submit event, i.e. after the browser has
                 started the submission. Setting it in the button's click handler disabled the
                 button before its default action ran, which cancelled the submit. --}}
            <form method="POST" action="{{ route('applicant.register') }}" enctype="multipart/form-data" novalidate
                  @submit="submitting = true" @pageshow.window="submitting = false"
                  class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                @csrf

                {{-- Step header (shared) --}}
                <div class="border-b border-gray-100 px-5 pb-5 pt-6 sm:px-8">
                    <p class="text-[13px] font-extrabold uppercase tracking-wider text-brand">
                        {{ __('applicant.step_short') }} <span x-text="step"></span> {{ __('applicant.of') }} 6
                    </p>
                    @foreach($steps as $n => $s)
                    <div x-show="step === {{ $n }}" @if($n !== $errorStep) style="display:none" @endif>
                        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900">{{ $s['label'] }}</h2>
                        <p class="mt-1 text-[15px] text-gray-600">{{ $s['desc'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- ─────────────── STEP 1 · Personal ─────────────── --}}
                <div x-show="step === 1">
                    <div class="space-y-6 px-5 py-6 sm:px-8">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-reg-field name="first_name" :label="__('fields.first_name')" required autocomplete="given-name"/>
                            <x-reg-field name="middle_name" :label="__('fields.middle_name')" required autocomplete="additional-name"/>
                            <x-reg-field name="last_name" :label="__('fields.last_name')" required autocomplete="family-name"/>
                        </div>
                        <input type="hidden" name="full_name" :value="fullName || '{{ old('full_name') }}'">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="gender" class="{{ $label }}">{{ __('fields.gender') }} {!! $req !!}</label>
                                <select id="gender" name="gender"
                                        @change="validateField('gender', $event.target.value)"
                                        :class="(touched['gender'] ? !!fieldErrors['gender'] : {{ $errors->has('gender') ? 'true' : 'false' }}) ? 'border-red-400 bg-red-50' : ''"
                                        class="{{ $input }}">
                                    <option value="">{{ __('applicant.select_placeholder') }}</option>
                                    <option value="male"   @selected(old('gender') === 'male')>{{ __('statuses.gender.male') }}</option>
                                    <option value="female" @selected(old('gender') === 'female')>{{ __('statuses.gender.female') }}</option>
                                </select>
                                @if($errors->has('gender'))
                                <p x-show="!touched['gender']" class="{{ $err }}">{{ $errors->first('gender') }}</p>
                                @endif
                                <p x-show="touched['gender'] && !!fieldErrors['gender']" x-text="fieldErrors['gender'] || ''" class="{{ $err }}"></p>
                            </div>
                            <div>
                                @if(app()->getLocale() === 'am')
                                    {{-- Amharic: Ethiopian calendar picker (submits a hidden Gregorian YYYY-MM-DD) --}}
                                    <x-ethiopian-datepicker name="date_of_birth" :label="__('fields.date_of_birth')" :max="$latestDob" required />
                                @else
                                    <x-reg-field name="date_of_birth" :label="__('fields.date_of_birth')" type="date" required max="{{ $latestDob }}" />
                                @endif
                                <p x-show="touched['date_of_birth'] && !!fieldErrors['date_of_birth']"
                                   x-text="fieldErrors['date_of_birth'] || ''" class="{{ $err }}"></p>
                            </div>
                            <x-reg-field name="national_id" :label="__('fields.national_id')" required
                                         inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456"
                                         :hint="__('applicant.national_id_hint')"
                                         @input="formatNationalId($event.target)"/>
                            <x-reg-field name="nationality" :label="__('fields.nationality').' '.$optTag" :placeholder="__('applicant.nationality_placeholder')"/>
                        </div>

                        {{-- Disability status --}}
                        <fieldset class="rounded-xl border border-gray-200 p-4 sm:p-5">
                            <legend class="px-1 text-sm font-semibold text-gray-800">{{ __('fields.disability_status') }} {!! $req !!}</legend>
                            <div class="mt-1 grid gap-3 sm:grid-cols-2">
                                @foreach(['0' => __('applicant.disability_no'), '1' => __('applicant.disability_yes')] as $val => $text)
                                <label class="flex h-12 cursor-pointer items-center gap-3 rounded-xl border px-4 text-[15px] font-semibold transition"
                                       :class="disabilityStatus === '{{ $val }}' ? 'border-brand bg-brand-muted text-brand-dark' : 'border-gray-300 text-gray-700 hover:border-gray-400'">
                                    <input type="radio" name="disability_status" value="{{ $val }}" x-model="disabilityStatus"
                                           @checked(old('disability_status', '0') === $val) class="h-[18px] w-[18px] border-gray-400 text-brand focus:ring-brand">
                                    {{ $text }}
                                </label>
                                @endforeach
                            </div>
                            @error('disability_status')<p class="{{ $err }}">{{ $message }}</p>@enderror

                            <div x-show="disabilityStatus === '1'" x-transition class="mt-4">
                                <label for="disability_type" class="{{ $label }}">{{ __('fields.disability_type') }} {!! $req !!}</label>
                                <input type="text" id="disability_type" name="disability_type" value="{{ old('disability_type') }}"
                                       placeholder="{{ __('applicant.disability_type_hint') }}"
                                       @blur="validateField('disability_type', $event.target.value)"
                                       @input="if(touched['disability_type']) validateField('disability_type', $event.target.value)"
                                       :class="(touched['disability_type'] ? !!fieldErrors['disability_type'] : {{ $errors->has('disability_type') ? 'true' : 'false' }}) ? 'border-red-400 bg-red-50' : ''"
                                       class="{{ $input }}">
                                @if($errors->has('disability_type'))
                                <p x-show="!touched['disability_type']" class="{{ $err }}">{{ $errors->first('disability_type') }}</p>
                                @endif
                                <p x-show="touched['disability_type'] && !!fieldErrors['disability_type']" x-text="fieldErrors['disability_type'] || ''" class="{{ $err }}"></p>
                                <p class="mt-1.5 text-sm text-gray-500">{{ __('applicant.disability_privacy') }}</p>
                            </div>
                        </fieldset>

                        <p class="flex items-start gap-2 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            <x-public.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                            {{ __('applicant.profile_photo_notice') }}
                        </p>
                    </div>
                    @include('applicant.auth._reg_nav', ['step' => 1])
                </div>

                {{-- ─────────────── STEP 2 · Education ─────────────── --}}
                <div x-show="step === 2" style="display:none">
                    <div class="grid gap-4 px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <div>
                            <label for="education_level" class="{{ $label }}">{{ __('fields.education_level') }}</label>
                            <select id="education_level" name="education_level" class="{{ $input }} @error('education_level') border-red-400 @enderror">
                                <option value="">{{ __('applicant.select_placeholder') }}</option>
                                @foreach(\App\Enums\EducationLevel::cases() as $level)
                                <option value="{{ $level->value }}" @selected(old('education_level') === $level->value)>{{ $level->getLabel() }}</option>
                                @endforeach
                            </select>
                            @error('education_level')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <x-reg-field name="field_of_study" :label="__('fields.field_of_study')" :placeholder="__('applicant.field_of_study_placeholder')"/>
                        <x-reg-field name="university_name" :label="__('fields.university_name')" class="sm:col-span-2"/>
                        <div>
                            <label for="graduation_year" class="{{ $label }}">{{ __('fields.graduation_year') }}</label>
                            <select id="graduation_year" name="graduation_year" class="{{ $input }} @error('graduation_year') border-red-400 @enderror">
                                <option value="">{{ __('applicant.select_placeholder') }}</option>
                                @for($y = now()->year; $y >= 1960; $y--)
                                <option value="{{ $y }}" @selected((string) old('graduation_year') === (string) $y)>{{ $y }}</option>
                                @endfor
                            </select>
                            @error('graduation_year')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="gpa" class="{{ $label }}">{{ __('fields.gpa') }} <span class="ml-1 text-xs font-medium text-gray-500">(0.00 – 4.00)</span></label>
                            <input type="number" id="gpa" name="gpa" step="0.01" min="0" max="4" inputmode="decimal"
                                   value="{{ old('gpa') }}" placeholder="3.50"
                                   @blur="validateField('gpa', $event.target.value)"
                                   :class="touched['gpa'] && fieldErrors['gpa'] ? 'border-red-400 bg-red-50' : ''"
                                   class="{{ $input }} @error('gpa') border-red-400 @enderror">
                            @error('gpa')<p class="{{ $err }}">{{ $message }}</p>@enderror
                            <p x-show="touched['gpa'] && !!fieldErrors['gpa']" x-text="fieldErrors['gpa'] || ''" class="{{ $err }}"></p>
                        </div>
                        <p class="text-sm text-gray-500 sm:col-span-2">{{ __('applicant.education_later_hint') }}</p>
                    </div>
                    @include('applicant.auth._reg_nav', ['step' => 2])
                </div>

                {{-- ─────────────── STEP 3 · Work experience ─────────────── --}}
                <div x-show="step === 3" style="display:none">
                    <div class="space-y-5 px-5 py-6 sm:px-8">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="work_experience_years" class="{{ $label }}">{{ __('fields.work_experience_years') }}</label>
                                <input type="number" id="work_experience_years" name="work_experience_years" min="0" inputmode="numeric"
                                       x-model.number="workYears" value="{{ old('work_experience_years', 0) }}"
                                       @blur="validateField('work_experience_years', $event.target.value)"
                                       :class="(touched['work_experience_years'] ? !!fieldErrors['work_experience_years'] : {{ $errors->has('work_experience_years') ? 'true' : 'false' }}) ? 'border-red-400 bg-red-50' : ''"
                                       class="{{ $input }}">
                                @if($errors->has('work_experience_years'))
                                <p x-show="!touched['work_experience_years']" class="{{ $err }}">{{ $errors->first('work_experience_years') }}</p>
                                @endif
                                <p x-show="touched['work_experience_years'] && !!fieldErrors['work_experience_years']" x-text="fieldErrors['work_experience_years'] || ''" class="{{ $err }}"></p>
                            </div>
                            <div x-show="workYears > 0" x-transition>
                                <label for="work_experience_months" class="{{ $label }}">{{ __('fields.work_experience_months') }} <span class="ml-1 text-xs font-medium text-gray-500">(0–11)</span></label>
                                <input type="number" id="work_experience_months" name="work_experience_months" min="0" max="11" inputmode="numeric"
                                       value="{{ old('work_experience_months', 0) }}"
                                       @blur="validateField('work_experience_months', $event.target.value)"
                                       :class="(touched['work_experience_months'] ? !!fieldErrors['work_experience_months'] : {{ $errors->has('work_experience_months') ? 'true' : 'false' }}) ? 'border-red-400 bg-red-50' : ''"
                                       class="{{ $input }}">
                                @if($errors->has('work_experience_months'))
                                <p x-show="!touched['work_experience_months']" class="{{ $err }}">{{ $errors->first('work_experience_months') }}</p>
                                @endif
                                <p x-show="touched['work_experience_months'] && !!fieldErrors['work_experience_months']" x-text="fieldErrors['work_experience_months'] || ''" class="{{ $err }}"></p>
                            </div>
                        </div>

                        <p x-show="!(workYears > 0)" class="flex items-start gap-2 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            <x-public.icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                            {{ __('applicant.no_experience_hint') }}
                        </p>

                        <div x-show="workYears > 0" x-transition class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-reg-field name="current_employer" :label="__('fields.current_employer')"/>
                                <x-reg-field name="current_position" :label="__('fields.current_position')"/>
                            </div>
                            <div>
                                <label for="work_experience_summary" class="{{ $label }}">{{ __('fields.work_experience_summary') }} {!! $optTag !!}</label>
                                <textarea id="work_experience_summary" name="work_experience_summary" rows="4" maxlength="2000"
                                          placeholder="{{ __('applicant.work_summary_placeholder') }}"
                                          class="mt-1.5 w-full rounded-xl border border-gray-300 px-4 py-3 text-base text-gray-900 placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">{{ old('work_experience_summary') }}</textarea>
                                @error('work_experience_summary')<p class="{{ $err }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    @include('applicant.auth._reg_nav', ['step' => 3])
                </div>

                {{-- ─────────────── STEP 4 · Contact & account ─────────────── --}}
                <div x-show="step === 4" style="display:none">
                    <div class="space-y-6 px-5 py-6 sm:px-8">
                        <input type="hidden" name="preferred_locale" value="{{ old('preferred_locale', app()->getLocale()) }}">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-reg-field name="phone" :label="__('fields.phone')" required type="tel" inputmode="tel" maxlength="17"
                                         placeholder="0911 234 567" autocomplete="tel" :hint="__('applicant.phone_hint')"
                                         @input="formatPhone($event.target)"/>
                            <x-reg-field name="alternative_phone" :label="__('fields.alternative_phone').' '.$optTag" type="tel" inputmode="tel" maxlength="17"
                                         placeholder="0911 234 567" @input="formatPhone($event.target)"/>
                            <x-reg-field name="email" :label="__('fields.email')" required type="email" autocomplete="email"
                                         placeholder="name@example.com" class="sm:col-span-2" :hint="__('applicant.email_hint')"/>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="text-base font-extrabold text-gray-900">{{ __('applicant.create_password') }}</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="password" class="{{ $label }}">{{ __('fields.password') }} {!! $req !!}</label>
                                    <div class="relative">
                                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" autocomplete="new-password"
                                               @blur="validateField('password', $event.target.value)"
                                               @input="password = $event.target.value; if(touched['password']) validateField('password', $event.target.value)"
                                               :class="(touched['password'] ? !!fieldErrors['password'] : {{ $errors->has('password') ? 'true' : 'false' }}) ? 'border-red-400 bg-red-50' : ''"
                                               class="{{ $input }} pr-20">
                                        <button type="button" @click="showPassword = !showPassword"
                                                class="absolute right-2 top-1/2 mt-[3px] h-9 -translate-y-1/2 rounded-lg px-3 text-sm font-bold text-brand hover:bg-brand-muted"
                                                :aria-pressed="showPassword.toString()"
                                                x-text="showPassword ? @js(__('applicant.hide')) : @js(__('applicant.show'))">{{ __('applicant.show') }}</button>
                                    </div>
                                    @if($errors->has('password'))
                                    <p x-show="!touched['password']" class="{{ $err }}">{{ $errors->first('password') }}</p>
                                    @endif
                                    <p x-show="touched['password'] && !!fieldErrors['password']" x-text="fieldErrors['password'] || ''" class="{{ $err }}"></p>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="{{ $label }}">{{ __('fields.password_confirmation') }} {!! $req !!}</label>
                                    <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                           @blur="validateField('password_confirmation', $event.target.value)"
                                           @input="if(touched['password_confirmation']) validateField('password_confirmation', $event.target.value)"
                                           :class="(touched['password_confirmation'] ? !!fieldErrors['password_confirmation'] : false) ? 'border-red-400 bg-red-50' : ''"
                                           class="{{ $input }}">
                                    <p x-show="touched['password_confirmation'] && !!fieldErrors['password_confirmation']" x-text="fieldErrors['password_confirmation'] || ''" class="{{ $err }}"></p>
                                </div>
                            </div>

                            {{-- Live password checklist --}}
                            <div class="mt-4 rounded-xl bg-gray-50 p-4">
                                <div class="mb-3 flex items-center gap-3">
                                    <div class="grid flex-1 grid-cols-5 gap-1.5" aria-hidden="true">
                                        <template x-for="i in 5" :key="i">
                                            <span class="h-1.5 rounded-full transition" :class="i <= passwordScore ? (passwordScore >= 5 ? 'bg-green-600' : (passwordScore >= 3 ? 'bg-amber-500' : 'bg-red-500')) : 'bg-gray-200'"></span>
                                        </template>
                                    </div>
                                    <span class="w-20 text-right text-sm font-bold" :class="passwordScore >= 5 ? 'text-green-700' : (passwordScore >= 3 ? 'text-amber-700' : 'text-gray-500')"
                                          x-text="passwordScore >= 5 ? @js(__('applicant.pw_strong')) : (passwordScore >= 3 ? @js(__('applicant.pw_fair')) : @js(__('applicant.pw_weak')))"></span>
                                </div>
                                <ul class="grid gap-1.5 text-sm sm:grid-cols-2">
                                    @foreach(['len' => __('applicant.pw_rule_length'), 'lower' => __('applicant.pw_rule_lower'), 'upper' => __('applicant.pw_rule_upper'), 'number' => __('applicant.pw_rule_number'), 'symbol' => __('applicant.pw_rule_symbol')] as $rule => $text)
                                    <li class="flex items-center gap-2" :class="passwordRules.{{ $rule }} ? 'text-green-700' : 'text-gray-600'">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full" :class="passwordRules.{{ $rule }} ? 'bg-green-600 text-white' : 'border border-gray-400'">
                                            <x-public.icon name="check" class="h-3 w-3" x-show="passwordRules.{{ $rule }}" />
                                        </span>
                                        {{ $text }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @include('applicant.auth._reg_nav', ['step' => 4])
                </div>

                {{-- ─────────────── STEP 5 · Documents ─────────────── --}}
                <div x-show="step === 5" style="display:none">
                    <div class="space-y-4 px-5 py-6 sm:px-8">
                        <label for="documents"
                               @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                               @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.docs.files = $event.dataTransfer.files; onDocumentChange({ target: $refs.docs }) }"
                               class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed px-6 py-10 text-center transition focus-within:ring-2 focus-within:ring-brand/30"
                               :class="touched['documents'] && fieldErrors['documents'] ? 'border-red-300 bg-red-50' : (dragging ? 'border-brand bg-brand-muted' : (docFileName ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-gray-50 hover:border-brand/50'))">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-brand ring-1 ring-gray-200">
                                <x-public.icon name="paperclip" class="h-7 w-7" x-show="!docFileName" />
                                <x-public.icon name="check-circle" class="h-7 w-7 text-green-600" x-show="docFileName" />
                            </span>
                            <span x-show="!docFileName">
                                <span class="block text-base font-bold text-gray-900">{{ __('applicant.upload_drop') }} <span class="text-brand underline">{{ __('applicant.upload_browse') }}</span></span>
                                <span class="mt-1 block text-sm text-gray-600">{{ __('applicant.upload_limits', ['types' => $docTypesLabel, 'size' => $maxMb]) }}</span>
                            </span>
                            <span x-show="docFileName" class="min-w-0">
                                <span class="block max-w-full truncate text-base font-bold text-gray-900" x-text="docFileName"></span>
                                <span class="mt-1 block text-sm text-gray-600"><span x-text="docFileSize"></span> · <span class="font-bold text-brand underline">{{ __('applicant.upload_replace') }}</span></span>
                            </span>
                            <input type="file" id="documents" name="documents" x-ref="docs" class="sr-only"
                                   accept="{{ implode(',', array_map(fn ($t) => '.'.$t, $docTypes)) }}"
                                   @change="onDocumentChange($event)">
                        </label>
                        @if($errors->has('documents'))
                        <p x-show="!touched['documents']" class="{{ $err }}">{{ $errors->first('documents') }}</p>
                        @endif
                        <p x-show="touched['documents'] && !!fieldErrors['documents']" x-text="fieldErrors['documents'] || ''" class="{{ $err }}"></p>

                        <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
                            <p class="font-bold text-gray-900">{{ __('applicant.doc_what_to_include') }}</p>
                            <p class="mt-1">{{ __('applicant.doc_include_list') }}</p>
                        </div>
                    </div>
                    @include('applicant.auth._reg_nav', ['step' => 5])
                </div>

                {{-- ─────────────── STEP 6 · Review ─────────────── --}}
                <div x-show="step === 6" style="display:none">
                    <div class="space-y-4 px-5 py-6 sm:px-8">
                        <p class="text-[15px] text-gray-700">{{ __('applicant.review_intro') }}</p>

                        @foreach([
                            1 => [__('applicant.step_1_heading'), [
                                [__('fields.full_name'), 'fullName'],
                                [__('fields.date_of_birth'), 'formatDobForReview(dateOfBirth)'],
                                [__('fields.gender'), 'genderLabel'],
                                [__('fields.national_id'), 'nationalId'],
                            ]],
                            2 => [__('applicant.step_2_heading'), [
                                [__('fields.education_level'), 'educationLabel'],
                                [__('fields.field_of_study'), 'fieldOfStudy'],
                            ]],
                            4 => [__('applicant.step_4_heading'), [
                                [__('fields.phone'), 'phone'],
                                [__('fields.email'), 'email'],
                            ]],
                            5 => [__('applicant.step_5_heading'), [
                                [__('documents.type_documents'), 'docFileName'],
                            ]],
                        ] as $target => [$heading, $rows])
                        <section class="rounded-xl border border-gray-200">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5">
                                <h3 class="text-[15px] font-extrabold text-gray-900">{{ $heading }}</h3>
                                <button type="button" @click="goToStep({{ $target }})" class="rounded-lg px-2 py-1 text-sm font-bold text-brand hover:bg-brand-muted">{{ __('applicant.edit') }}</button>
                            </div>
                            <dl class="grid gap-x-6 gap-y-3 px-4 py-3 sm:grid-cols-2">
                                @foreach($rows as [$rowLabel, $expr])
                                <div>
                                    <dt class="text-[13px] font-semibold text-gray-500">{{ $rowLabel }}</dt>
                                    <dd class="mt-0.5 break-words text-[15px] font-semibold text-gray-900" x-text="({{ $expr }}) || '—'"></dd>
                                </div>
                                @endforeach
                            </dl>
                        </section>
                        @endforeach

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3.5 text-[15px] transition"
                               :class="confirmed ? 'border-brand bg-brand-muted' : 'border-gray-300'">
                            <input type="checkbox" x-model="confirmed" class="mt-0.5 h-5 w-5 shrink-0 rounded border-gray-400 text-brand focus:ring-brand">
                            <span class="text-gray-800">{{ __('applicant.confirm_accurate', ['org' => $orgName]) }}</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50/70 px-5 py-4 sm:px-8">
                        <button type="button" @click="prevStep()"
                                class="inline-flex h-12 items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-[15px] font-bold text-gray-800 transition hover:bg-gray-50">
                            <x-public.icon name="arrow-left" class="h-4 w-4" />
                            {{ __('applicant.step_back') }}
                        </button>
                        <button type="submit" :disabled="!confirmed || submitting"
                                class="inline-flex h-12 items-center gap-2 rounded-xl bg-accent-dark px-6 text-[15px] font-extrabold text-white transition hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!submitting">{{ __('applicant.register_button') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('applicant.submitting') }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <p class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-600">
                <x-public.icon name="lock" class="h-4 w-4 text-gray-400" />
                {{ __('applicant.data_secure_note') }}
            </p>
        </div>
    </div>
</div>

<script>
function registrationForm() {
    const msg = @js([
        'required'         => __('applicant.v_required'),
        'max100'           => __('applicant.v_max', ['max' => 100]),
        'max255'           => __('applicant.v_max', ['max' => 255]),
        'max2000'          => __('applicant.v_max', ['max' => 2000]),
        'nationalId'       => __('applicant.v_national_id'),
        'gender'           => __('applicant.v_gender'),
        'disabilityType'   => __('applicant.v_disability_type'),
        'gpa'              => __('applicant.v_gpa'),
        'gradYear'         => __('applicant.v_grad_year'),
        'nonNegative'      => __('applicant.v_non_negative'),
        'months'           => __('applicant.v_months'),
        'phone'            => __('applicant.v_phone'),
        'email'            => __('applicant.v_email'),
        'password'         => __('applicant.v_password'),
        'passwordMismatch' => __('applicant.v_password_mismatch'),
        'passwordConfirm'  => __('applicant.v_password_confirm'),
        'dob'              => __('applicant.v_dob'),
        'minAge'           => __('applicant.v_min_age', ['age' => \App\Models\Applicant::MINIMUM_AGE]),
        'latestDob'        => $latestDob,
        'docType'          => __('applicant.v_doc_type', ['types' => $docTypesLabel]),
        'docSize'          => __('applicant.v_doc_size', ['size' => $maxMb]),
        'docMissing'       => __('applicant.v_doc_missing'),
    ]);
    const labels = @js([
        'gender'    => ['male' => __('statuses.gender.male'), 'female' => __('statuses.gender.female')],
        'education' => collect(\App\Enums\EducationLevel::cases())->mapWithKeys(fn ($l) => [$l->value => $l->getLabel()]),
        'steps'     => collect($steps)->map(fn ($s) => $s['label']),
    ]);
    const docTypes = @js($docTypes);
    const maxBytes = {{ $maxMb }} * 1024 * 1024;

    return {
        step: {{ $errorStep }},
        maxStep: {{ $errorStep }},
        totalSteps: 6,
        stepLabels: labels.steps,
        disabilityStatus: '{{ old('disability_status', '0') }}',
        workYears: {{ (int) old('work_experience_years', 0) }},
        firstName: '{{ addslashes(old('first_name', '')) }}',
        middleName: '{{ addslashes(old('middle_name', '')) }}',
        lastName: '{{ addslashes(old('last_name', '')) }}',
        dateOfBirth: '{{ old('date_of_birth', '') }}',
        nationalId: '{{ addslashes(old('national_id', '')) }}',
        phone: '{{ addslashes(old('phone', '')) }}',
        email: '{{ addslashes(old('email', '')) }}',
        genderLabel: '',
        educationLabel: '',
        fieldOfStudy: '',
        password: '',
        showPassword: false,
        confirmed: false,
        submitting: false,
        dragging: false,
        docFileName: @if($errors->any() && session('reg_temp_docs_name'))'{{ addslashes(session('reg_temp_docs_name')) }}'@else null @endif,
        docFileSize: '',
        fieldErrors: {},
        touched: {},
        _timers: {},

        get fullName() {
            return [this.firstName, this.middleName, this.lastName]
                .map(s => s.trim()).filter(Boolean).join(' ');
        },

        get passwordRules() {
            const v = this.password;
            return { len: v.length >= 8, lower: /[a-z]/.test(v), upper: /[A-Z]/.test(v), number: /[0-9]/.test(v), symbol: /[^a-zA-Z0-9]/.test(v) };
        },
        get passwordScore() { return Object.values(this.passwordRules).filter(Boolean).length; },

        onDocumentChange(event) {
            const file = event.target.files[0];
            if (!file) { this.docFileName = null; this.docFileSize = ''; return; }
            this.validateFile('documents', file);
            this.docFileName = file.name;
            this.docFileSize = file.size >= 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(file.size / 1024)) + ' KB';
        },

        syncReviewFields() {
            const val = (n) => document.querySelector(`[name="${n}"]`)?.value ?? '';
            this.firstName      = val('first_name')  || this.firstName;
            this.middleName     = val('middle_name') || this.middleName;
            this.lastName       = val('last_name')   || this.lastName;
            this.dateOfBirth    = val('date_of_birth');
            this.nationalId     = val('national_id').replace(/\s/g, '');
            this.phone          = val('phone');
            this.email          = val('email');
            this.genderLabel    = labels.gender[val('gender')] ?? '';
            this.educationLabel = labels.education[val('education_level')] ?? '';
            this.fieldOfStudy   = val('field_of_study');
        },

        // ── DOB display ──────────────────────────────────────────────────────
        // Gregorian YYYY-MM-DD → locale-aware display string.
        // For 'am': convert to Ethiopian using the same JDN algorithm as the datepicker.
        formatDobForReview(gcStr) {
            if (!gcStr) return '';
            const locale = '{{ app()->getLocale() }}';
            if (locale !== 'am') return gcStr;
            const p = gcStr.split('-');
            if (p.length !== 3) return gcStr;
            const et = this._jdnToEt(this._gToJdn(+p[0], +p[1], +p[2]));
            const months = ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'];
            return `${et.day} ${months[et.month - 1]} ${et.year}`;
        },
        _gToJdn(y, m, d) {
            return Math.floor((1461*(y+4800+Math.floor((m-14)/12)))/4)
                 + Math.floor((367*(m-2-12*Math.floor((m-14)/12)))/12)
                 - Math.floor((3*Math.floor((y+4900+Math.floor((m-14)/12))/100))/4)
                 + d - 32075;
        },
        _jdnToEt(j) {
            const r = j - 1724221;
            const year = Math.floor((4*r+1463)/1461);
            const doy  = r - (365*(year-1) + Math.floor(year/4));
            return { year, month: Math.floor(doy/30)+1, day: (doy%30)+1 };
        },

        formatNationalId(input) {
            const digits = input.value.replace(/\D/g, '').slice(0, 16);
            input.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
            if (this.touched['national_id']) this.validateField('national_id', input.value);
        },

        formatPhone(input) {
            const raw = input.value.trim();
            if (raw.startsWith('+')) {
                input.value = '+' + raw.slice(1).replace(/\D/g, '').slice(0, 12);
                if (this.touched[input.name]) this.validateField(input.name, input.value);
                return;
            }

            const digits = raw.replace(/\D/g, '').slice(0, 10);
            input.value = digits.replace(/^(\d{4})(\d{0,3})(\d{0,3}).*/, (_, a, b, c) => [a, b, c].filter(Boolean).join(' '));
            if (this.touched[input.name]) this.validateField(input.name, input.value);
        },

        validateFile(field, file) {
            this.touched[field] = true;
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            let err = '';
            if (!docTypes.includes(ext)) err = msg.docType;
            else if (file.size > maxBytes) err = msg.docSize;
            this.fieldErrors[field] = err;
        },

        validateField(field, value) {
            this.touched[field] = true;
            const v = (value ?? '').toString().trim();
            let err = '';
            // Mirrors the server rule (normalised to +2519XXXXXXXX).
            const ethiopianMobile = /^(09\d{8}|2519\d{8}|\+2519\d{8}|9\d{8})$/;

            switch (field) {
                case 'first_name':
                case 'middle_name':
                case 'last_name':
                    if (!v) err = msg.required;
                    else if (v.length > 100) err = msg.max100;
                    break;
                case 'national_id':
                    if (!v) err = msg.required;
                    else if (v.replace(/\D/g, '').length !== 16) err = msg.nationalId;
                    break;
                case 'gender':
                    if (!v) err = msg.gender;
                    break;
                case 'disability_type':
                    if (this.disabilityStatus === '1' && !v) err = msg.disabilityType;
                    else if (v.length > 255) err = msg.max255;
                    break;
                case 'nationality':
                case 'university_name':
                case 'field_of_study':
                case 'current_employer':
                case 'current_position':
                    if (v.length > 255) err = msg.max255;
                    break;
                case 'work_experience_summary':
                    if (v.length > 2000) err = msg.max2000;
                    break;
                case 'gpa':
                    if (v !== '' && (isNaN(+v) || +v < 0 || +v > 4)) err = msg.gpa;
                    break;
                case 'graduation_year':
                    if (v !== '' && (isNaN(+v) || +v < 1950 || +v > {{ now()->year }})) err = msg.gradYear;
                    break;
                case 'work_experience_years':
                    if (v !== '' && (isNaN(+v) || +v < 0)) err = msg.nonNegative;
                    break;
                case 'work_experience_months':
                    if (v !== '' && (isNaN(+v) || +v < 0 || +v > 11)) err = msg.months;
                    break;
                case 'phone':
                    if (!v) err = msg.required;
                    else if (!ethiopianMobile.test(v.replace(/\s+/g, ''))) err = msg.phone;
                    break;
                case 'alternative_phone':
                    if (v && !ethiopianMobile.test(v.replace(/\s+/g, ''))) err = msg.phone;
                    break;
                case 'email':
                    if (!v) err = msg.required;
                    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) err = msg.email;
                    break;
                case 'password':
                    this.password = value ?? '';
                    if (!v) err = msg.required;
                    else if (this.passwordScore < 5) err = msg.password;
                    if (this.touched['password_confirmation']) {
                        const c = document.getElementById('password_confirmation')?.value ?? '';
                        this.fieldErrors['password_confirmation'] = (c && c !== v) ? msg.passwordMismatch : '';
                    }
                    break;
                case 'password_confirmation': {
                    const p = document.getElementById('password')?.value ?? '';
                    if (!v) err = msg.passwordConfirm;
                    else if (v !== p) err = msg.passwordMismatch;
                    break;
                }
            }

            this.fieldErrors[field] = err;

            if (['email', 'phone', 'national_id'].includes(field) && !err && v) {
                clearTimeout(this._timers[field]);
                this._timers[field] = setTimeout(() => this.checkUnique(field, v), 500);
            }
        },

        async checkUnique(field, value) {
            try {
                const url = `{{ route('applicant.validate-field') }}?field=${field}&value=${encodeURIComponent(value)}`;
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!data.valid && !this.fieldErrors[field]) {
                    this.fieldErrors[field] = data.message;
                }
            } catch (_) { /* silent — server catches on submit */ }
        },

        validateStep(n) {
            const required = {
                1: ['first_name', 'middle_name', 'last_name', 'national_id', 'gender'],
                4: ['phone', 'email', 'password', 'password_confirmation'],
            };
            const optional = {
                1: ['nationality'],
                2: ['gpa', 'graduation_year', 'university_name', 'field_of_study'],
                3: ['work_experience_years', 'work_experience_months'],
                4: ['alternative_phone'],
            };

            let ok = true;

            for (const f of (required[n] ?? [])) {
                const el = document.querySelector(`[name="${f}"]`);
                this.validateField(f, el?.value ?? '');
                if (this.fieldErrors[f]) ok = false;
            }
            for (const f of (optional[n] ?? [])) {
                const el = document.querySelector(`[name="${f}"]`);
                if (el?.value) { this.validateField(f, el.value); if (this.fieldErrors[f]) ok = false; }
            }

            if (n === 1) {
                // DOB is a Gregorian YYYY-MM-DD value — either the native date input
                // (en) or the Ethiopian picker's hidden field (am).
                const dob = document.querySelector('[name="date_of_birth"]')?.value ?? '';
                this.touched['date_of_birth'] = true;
                // YYYY-MM-DD strings compare correctly as text.
                this.fieldErrors['date_of_birth'] = ! dob ? msg.dob : (dob > msg.latestDob ? msg.minAge : '');
                if (this.fieldErrors['date_of_birth']) ok = false;

                if (this.disabilityStatus === '1') {
                    const el = document.querySelector('[name="disability_type"]');
                    this.validateField('disability_type', el?.value ?? '');
                    if (this.fieldErrors['disability_type']) ok = false;
                }
            }

            if (n === 5) {
                const el = document.querySelector('[name="documents"]');
                if (!el?.files?.length && !this.docFileName) {
                    this.fieldErrors['documents'] = msg.docMissing;
                    this.touched['documents'] = true;
                    ok = false;
                } else if (this.fieldErrors['documents']) {
                    ok = false;
                }
            }

            return ok;
        },

        focusFirstError() {
            this.$nextTick(() => {
                const bad = document.querySelector('form [aria-invalid="true"], form .border-red-400, form .border-red-300');
                bad?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                bad?.focus?.();
            });
        },

        show(n) {
            this.step = n;
            this.maxStep = Math.max(this.maxStep, n);
            if (n === 6) this.$nextTick(() => this.syncReviewFields());
            this.$nextTick(() => this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },

        nextStep() {
            if (!this.validateStep(this.step)) { this.focusFirstError(); return; }
            if (this.step < this.totalSteps) this.show(this.step + 1);
        },
        prevStep() { if (this.step > 1) this.show(this.step - 1); },

        // Jump via the sidebar / review "Edit": backwards freely, forwards only
        // to steps already reached and only when the current step is valid.
        goToStep(n) {
            if (n === this.step || n > this.maxStep) return;
            if (n > this.step && !this.validateStep(this.step)) { this.focusFirstError(); return; }
            this.show(n);
        },
    };
}
</script>
@endsection
