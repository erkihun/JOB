@extends('layouts.public')

@section('title', __('applicant.register_heading'))

@section('content')
<div class="min-h-screen bg-gray-50 py-10 px-4 sm:px-6 lg:px-8">
<div class="mx-auto max-w-7xl" x-data="registrationForm()" x-cloak>

    {{-- Page heading --}}
    <div class="mb-8 text-center">
        @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
        @if($orgLogo)
            <img src="{{ Storage::url($orgLogo) }}" alt="{{ \App\Models\Setting::get('org.name') }}"
                 class="mx-auto mb-4 h-12 w-auto object-contain">
        @endif
        <h1 class="text-2xl font-bold text-gray-900">{{ __('applicant.register_heading') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('applicant.register_subheading') }}</p>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('applicant.already_have_account') }}
            <a href="{{ route('applicant.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                {{ __('applicant.sign_in_link') }}
            </a>
        </p>
    </div>

    {{-- Progress bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            @php
                $steps = [
                    __('applicant.step_personal'),
                    __('applicant.step_education'),
                    __('applicant.step_work'),
                    __('applicant.step_contact'),
                    __('applicant.step_documents'),
                    __('applicant.step_review'),
                ];
            @endphp
            @foreach($steps as $i => $label)
            <div class="flex flex-col items-center flex-1">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all"
                     :class="step > {{ $i + 1 }}
                        ? 'bg-green-500 text-white'
                        : step === {{ $i + 1 }}
                            ? 'bg-blue-600 text-white ring-4 ring-blue-100'
                            : 'bg-gray-200 text-gray-500'">
                    <template x-if="step > {{ $i + 1 }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="step <= {{ $i + 1 }}">
                        <span>{{ $i + 1 }}</span>
                    </template>
                </div>
                <span class="mt-1 hidden text-xs sm:block"
                      :class="step >= {{ $i + 1 }} ? 'text-blue-600 font-medium' : 'text-gray-400'">
                    {{ $label }}
                </span>
            </div>
            @if($i < count($steps) - 1)
            <div class="h-0.5 flex-1 mt-[-16px] mx-1 transition-all"
                 :class="step > {{ $i + 1 }} ? 'bg-green-400' : 'bg-gray-200'"></div>
            @endif
            @endforeach
        </div>
        <p class="text-center text-xs text-gray-400 mt-1">
            {{ str_replace([':current', ':total'], ['', count($steps)], __('applicant.step_of')) }}
            <span x-text="step"></span> / {{ count($steps) }}
        </p>
    </div>

    {{-- Validation errors (server-side) --}}
    @if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold mb-1">{{ __('validation.required') === 'The :attribute field is required.' ? 'Please fix the following errors:' : 'እባክዎ ስህተቶቹን ያስተካክሉ:' }}</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('applicant.register') }}"
          enctype="multipart/form-data" class="space-y-0" novalidate>
        @csrf

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 1 · Personal Information
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 1" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_1_heading') }}</h2>
            </div>
            <div class="p-6 space-y-5">

                {{-- Profile photo --}}
                <div class="flex items-start gap-5">
                    <div class="shrink-0">
                        <div class="relative h-24 w-24 rounded-full overflow-hidden border-2 border-gray-200 bg-gray-100 flex items-center justify-center">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!photoPreview">
                                <svg class="h-10 w-10 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>
                            </template>
                        </div>
                        <label class="mt-2 block text-center text-xs text-blue-600 hover:text-blue-800 cursor-pointer">
                            {{ __('fields.profile_photo') }}
                            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png"
                                   @change="previewPhoto($event)"
                                   class="sr-only">
                        </label>
                        <p class="mt-0.5 text-center text-xs text-gray-400">JPG/PNG ≤ 2 MB</p>
                        @error('profile_photo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex-1 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-reg-field name="first_name" :label="__('fields.first_name')" required/>
                            <x-reg-field name="middle_name" :label="__('fields.middle_name')"/>
                            <x-reg-field name="last_name" :label="__('fields.last_name')" required/>
                        </div>
                        <input type="hidden" name="full_name" :value="fullName || '{{ old('full_name') }}'">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- Gender --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('fields.gender') }} <span class="text-red-500">*</span></label>
                        <select name="gender"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('gender') border-red-400 @enderror">
                            <option value="">— {{ __('fields.gender') }} —</option>
                            <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>{{ __('statuses.gender.male') }}</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('statuses.gender.female') }}</option>
                            <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>{{ __('statuses.gender.other') }}</option>
                        </select>
                        @error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Date of birth --}}
                    <x-reg-field name="date_of_birth" :label="__('fields.date_of_birth')" type="date"/>

                    {{-- Nationality --}}
                    <x-reg-field name="nationality" :label="__('fields.nationality')"/>

                    {{-- National ID --}}
                    <x-reg-field name="national_id" :label="__('fields.national_id')" required/>
                </div>

                {{-- Disability status --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <p class="text-sm font-medium text-gray-700">{{ __('fields.disability_status') }} <span class="text-red-500">*</span></p>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="disability_status" value="1"
                                   x-model="disabilityStatus"
                                   {{ old('disability_status') === '1' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600">
                            <span class="text-sm">{{ __('applicant.disability_yes') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="disability_status" value="0"
                                   x-model="disabilityStatus"
                                   {{ old('disability_status', '0') === '0' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600">
                            <span class="text-sm">{{ __('applicant.disability_no') }}</span>
                        </label>
                    </div>
                    @error('disability_status')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                    {{-- Disability type — shown only when status is yes --}}
                    <div x-show="disabilityStatus === '1'" x-transition class="pt-1">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('fields.disability_type') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="disability_type"
                               value="{{ old('disability_type') }}"
                               placeholder="{{ __('applicant.disability_type_hint') }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('disability_type') border-red-400 @enderror">
                        @error('disability_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>
            @include('applicant.auth._reg_nav', ['step' => 1])
        </div>

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 2 · Education
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 2" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_2_heading') }}</h2>
            </div>
            <div class="p-6 space-y-4">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-reg-field name="university_name" :label="__('fields.university_name')" class="sm:col-span-2"/>

                    <x-reg-field name="field_of_study" :label="__('fields.field_of_study')"/>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('fields.education_level') }}</label>
                        <select name="education_level"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('education_level') border-red-400 @enderror">
                            <option value="">— {{ __('fields.education_level') }} —</option>
                            @foreach(\App\Enums\EducationLevel::cases() as $level)
                            <option value="{{ $level->value }}" {{ old('education_level') === $level->value ? 'selected' : '' }}>
                                {{ $level->getLabel() }}
                            </option>
                            @endforeach
                        </select>
                        @error('education_level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('fields.graduation_year') }}</label>
                        <select name="graduation_year"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('graduation_year') border-red-400 @enderror">
                            <option value="">— {{ __('fields.graduation_year') }} —</option>
                            @for($y = now()->year + 2; $y >= 1960; $y--)
                            <option value="{{ $y }}" {{ (string) old('graduation_year') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        @error('graduation_year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('fields.gpa') }}
                            <span class="text-gray-400 font-normal text-xs">(0.00 – 4.00)</span>
                        </label>
                        <input type="number" name="gpa" step="0.01" min="0" max="4"
                               value="{{ old('gpa') }}"
                               placeholder="e.g. 3.50"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('gpa') border-red-400 @enderror">
                        @error('gpa')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>
            @include('applicant.auth._reg_nav', ['step' => 2])
        </div>

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 3 · Work Experience
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 3" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_3_heading') }}</h2>
            </div>
            <div class="p-6 space-y-4">

                <div class="max-w-xs">
                    <label class="block text-sm font-medium text-gray-700">{{ __('fields.work_experience_years') }}</label>
                    <input type="number" name="work_experience_years" min="0"
                           x-model.number="workYears"
                           value="{{ old('work_experience_years', 0) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('work_experience_years') border-red-400 @enderror">
                    @error('work_experience_years')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div x-show="workYears > 0" x-transition class="space-y-4">
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700">{{ __('fields.work_experience_months') }} <span class="text-gray-400 text-xs">(0–11)</span></label>
                        <input type="number" name="work_experience_months" min="0" max="11"
                               value="{{ old('work_experience_months', 0) }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('work_experience_months') border-red-400 @enderror">
                        @error('work_experience_months')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-reg-field name="current_employer" :label="__('fields.current_employer')"/>
                        <x-reg-field name="current_position" :label="__('fields.current_position')"/>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('fields.work_experience_summary') }}
                            <span class="text-gray-400 font-normal text-xs">({{ __('applicant.optional') }})</span>
                        </label>
                        <textarea name="work_experience_summary" rows="4"
                                  class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('work_experience_summary') }}</textarea>
                        @error('work_experience_summary')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>
            @include('applicant.auth._reg_nav', ['step' => 3])
        </div>

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 4 · Contact & Account
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 4" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_4_heading') }}</h2>
            </div>
            <div class="p-6 space-y-5">

                <input type="hidden" name="preferred_locale" value="{{ old('preferred_locale', app()->getLocale()) }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-reg-field name="phone"             :label="__('fields.phone')"             required type="tel"/>
                    <x-reg-field name="alternative_phone" :label="__('fields.alternative_phone')" type="tel"/>
                    <x-reg-field name="email"             :label="__('fields.email')"             required type="email"/>
                </div>

                <hr class="border-gray-100">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('fields.password') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('password') border-red-400 @enderror">
                        <p class="mt-1 text-xs text-gray-400">Min 8 chars, upper+lower+number+symbol.</p>
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            {{ __('fields.password_confirmation') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

            </div>
            @include('applicant.auth._reg_nav', ['step' => 4])
        </div>

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 5 · Documents
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 5" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-teal-50 to-cyan-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_5_heading') }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('applicant.doc_combined_hint') }}</p>
            </div>
            <div class="p-6">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="h-8 w-8 shrink-0 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('documents.type_documents') }}
                                <span class="ml-1 text-xs font-normal text-gray-400">{{ __('applicant.doc_optional') }}</span>
                            </label>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('applicant.doc_combined_hint') }}</p>
                            <input type="file" name="documents" accept=".pdf"
                                   class="mt-2 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 @error('documents') border border-red-300 rounded-md @enderror">
                            @error('documents')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">PDF only &middot; max 2 MB</p>
                </div>
            </div>
            @include('applicant.auth._reg_nav', ['step' => 5])
        </div>

        {{-- ─────────────────────────────────────────────────────────────────
             STEP 6 · Review & Submit
        ───────────────────────────────────────────────────────────────────── --}}
        <div x-show="step === 6" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-blue-50 to-green-50 px-6 py-4 rounded-t-2xl">
                <h2 class="font-semibold text-gray-800">{{ __('applicant.step_6_heading') }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('applicant.register_subheading') }}</p>
            </div>
            <div class="p-6 space-y-4">

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                    <p>{{ app()->getLocale() === 'am'
                        ? 'ሁሉም ክፍሎች ተሟልተዋል። ከዚህ በታች ውሎቹን ተቀብለው ያስገቡ።'
                        : 'All steps completed. Accept the terms below and submit your registration.' }}</p>
                </div>

                {{-- Terms --}}
                <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <input type="hidden" name="terms" value="0">
                    <input type="checkbox" name="terms" id="terms" value="1"
                           {{ old('terms') ? 'checked' : '' }}
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="terms" class="text-sm text-gray-700">
                        {{ __('applicant.terms_label') }}
                    </label>
                </div>
                @error('terms')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            </div>
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
                <button type="button" @click="prevStep()"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    {{ __('applicant.step_back') }}
                </button>
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    {{ __('applicant.register_button') }}
                </button>
            </div>
        </div>

    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('vacancies.index') }}" class="text-blue-600 hover:text-blue-500">
            {{ __('applicant.back_to_jobs') }}
        </a>
    </p>

</div>
</div>

<script>
function registrationForm() {
    return {
        step: {{ $errors->any() ? 1 : 1 }},
        totalSteps: 6,
        disabilityStatus: '{{ old('disability_status', '0') }}',
        workYears: {{ (int) old('work_experience_years', 0) }},
        firstName: '{{ old('first_name', '') }}',
        middleName: '{{ old('middle_name', '') }}',
        lastName: '{{ old('last_name', '') }}',
        photoPreview: null,

        get fullName() {
            return [this.firstName, this.middleName, this.lastName]
                .map(s => s.trim()).filter(Boolean).join(' ');
        },

        previewPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => { this.photoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        nextStep() { if (this.step < this.totalSteps) this.step++; window.scrollTo(0, 0); },
        prevStep() { if (this.step > 1) this.step--; window.scrollTo(0, 0); },
    };
}
</script>

@endsection
