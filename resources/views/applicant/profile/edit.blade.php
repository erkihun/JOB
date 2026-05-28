@extends('layouts.applicant')

@section('title', __('applicant.edit_profile'))

@section('content')
<div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.edit_profile') }}</h1>

    <form method="POST" action="{{ route('applicant.profile.update') }}"
          enctype="multipart/form-data" class="space-y-5"
          x-data="{ disabilityStatus: '{{ old('disability_status', $applicant->disability_status ? '1' : '0') }}', workYears: {{ (int) old('work_experience_years', $applicant->work_experience_years ?? 0) }} }">
        @csrf
        @method('PUT')

        {{-- ── Personal Information ──────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('applicant.personal_info') }}</h2>

            {{-- Profile photo --}}
            <div class="flex items-center gap-5">
                <div class="shrink-0">
                    @if($applicant->profile_photo_path)
                    <img src="{{ route('applicant.profile.photo') }}" alt=""
                         class="h-20 w-20 rounded-full object-cover border-2 border-gray-200">
                    @else
                    <div class="h-20 w-20 rounded-full bg-gray-100 flex items-center justify-center border-2 border-gray-200">
                        <svg class="h-9 w-9 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('fields.profile_photo') }}</label>
                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png"
                           class="mt-1 block text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-400">JPG/PNG ≤ 2 MB</p>
                    @error('profile_photo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-reg-field name="first_name"  :label="__('fields.first_name')"  required :value="$applicant->first_name"/>
                <x-reg-field name="middle_name" :label="__('fields.middle_name')" :value="$applicant->middle_name"/>
                <x-reg-field name="last_name"   :label="__('fields.last_name')"   required :value="$applicant->last_name"/>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Gender --}}
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700">{{ __('fields.gender') }} <span class="text-red-500">*</span></label>
                    <select id="gender" name="gender"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('gender') border-red-400 @enderror">
                        <option value="male"   {{ old('gender', $applicant->gender?->value) === 'male'   ? 'selected' : '' }}>{{ __('statuses.gender.male') }}</option>
                        <option value="female" {{ old('gender', $applicant->gender?->value) === 'female' ? 'selected' : '' }}>{{ __('statuses.gender.female') }}</option>
                        <option value="other"  {{ old('gender', $applicant->gender?->value) === 'other'  ? 'selected' : '' }}>{{ __('statuses.gender.other') }}</option>
                    </select>
                    @error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if(app()->getLocale() === 'am')
                    <x-ethiopian-datepicker
                        name="date_of_birth"
                        :label="__('fields.date_of_birth')"
                        :value="old('date_of_birth', $applicant->date_of_birth?->format('Y-m-d'))"
                        :max="now()->toDateString()"/>
                @else
                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700">{{ __('fields.date_of_birth') }}</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="{{ old('date_of_birth', $applicant->date_of_birth?->format('Y-m-d')) }}"
                           max="{{ now()->toDateString() }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    @error('date_of_birth')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif

                <div>
                    <label for="national_id" class="block text-sm font-medium text-gray-700">{{ __('fields.national_id') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="national_id" name="national_id"
                           value="{{ old('national_id', $applicant->national_id) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('national_id') border-red-400 @enderror">
                    @error('national_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nationality" class="block text-sm font-medium text-gray-700">{{ __('fields.nationality') }}</label>
                    <input type="text" id="nationality" name="nationality"
                           value="{{ old('nationality', $applicant->nationality) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            {{-- Disability --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('fields.disability_status') }}</label>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="disability_status" value="1" x-model="disabilityStatus"
                               class="h-4 w-4 text-blue-600">
                        <span class="text-sm">{{ __('applicant.disability_yes') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="disability_status" value="0" x-model="disabilityStatus"
                               class="h-4 w-4 text-blue-600">
                        <span class="text-sm">{{ __('applicant.disability_no') }}</span>
                    </label>
                </div>
                <div x-show="disabilityStatus === '1'" x-transition>
                    <label for="disability_type" class="block text-sm font-medium text-gray-700">{{ __('fields.disability_type') }}</label>
                    <input type="text" id="disability_type" name="disability_type"
                           value="{{ old('disability_type', $applicant->disability_type) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('disability_type') border-red-400 @enderror">
                    @error('disability_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Education ────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('applicant.education_info') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="university_name" class="block text-sm font-medium text-gray-700">{{ __('fields.university_name') }}</label>
                    <input type="text" id="university_name" name="university_name"
                           value="{{ old('university_name', $applicant->university_name) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label for="field_of_study" class="block text-sm font-medium text-gray-700">{{ __('fields.field_of_study') }}</label>
                    <input type="text" id="field_of_study" name="field_of_study"
                           value="{{ old('field_of_study', $applicant->field_of_study) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label for="education_level" class="block text-sm font-medium text-gray-700">{{ __('fields.education_level') }}</label>
                    <select id="education_level" name="education_level"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">—</option>
                        @foreach(\App\Enums\EducationLevel::cases() as $level)
                        <option value="{{ $level->value }}" {{ old('education_level', $applicant->education_level?->value) === $level->value ? 'selected' : '' }}>
                            {{ $level->getLabel() }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="graduation_year" class="block text-sm font-medium text-gray-700">{{ __('fields.graduation_year') }}</label>
                    <select id="graduation_year" name="graduation_year"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('graduation_year') border-red-400 @enderror">
                        <option value="">—</option>
                        @for($y = now()->year + 2; $y >= 1960; $y--)
                        <option value="{{ $y }}" {{ (string) old('graduation_year', $applicant->graduation_year) === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('graduation_year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="gpa" class="block text-sm font-medium text-gray-700">{{ __('fields.gpa') }} <span class="text-gray-400 text-xs">(0–4)</span></label>
                    <input type="number" id="gpa" name="gpa" step="0.01" min="0" max="4"
                           value="{{ old('gpa', $applicant->gpa) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('gpa') border-red-400 @enderror">
                    @error('gpa')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Work Experience ──────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('applicant.work_info') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="work_experience_years" class="block text-sm font-medium text-gray-700">{{ __('fields.work_experience_years') }}</label>
                    <input type="number" id="work_experience_years" name="work_experience_years" min="0"
                           x-model.number="workYears"
                           value="{{ old('work_experience_years', $applicant->work_experience_years ?? 0) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
            <div x-show="workYears > 0" x-transition class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="work_experience_months" class="block text-sm font-medium text-gray-700">{{ __('fields.work_experience_months') }} <span class="text-gray-400 text-xs">(0–11)</span></label>
                        <input type="number" id="work_experience_months" name="work_experience_months" min="0" max="11"
                               value="{{ old('work_experience_months', $applicant->work_experience_months ?? 0) }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('work_experience_months') border-red-400 @enderror">
                        @error('work_experience_months')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="current_employer" class="block text-sm font-medium text-gray-700">{{ __('fields.current_employer') }}</label>
                        <input type="text" id="current_employer" name="current_employer"
                               value="{{ old('current_employer', $applicant->current_employer) }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="current_position" class="block text-sm font-medium text-gray-700">{{ __('fields.current_position') }}</label>
                        <input type="text" id="current_position" name="current_position"
                               value="{{ old('current_position', $applicant->current_position) }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label for="work_experience_summary" class="block text-sm font-medium text-gray-700">{{ __('fields.work_experience_summary') }}</label>
                    <textarea id="work_experience_summary" name="work_experience_summary" rows="3"
                              class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('work_experience_summary', $applicant->work_experience_summary) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Contact & Address ────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('applicant.contact_info') }}</h2>
            <input type="hidden" name="preferred_locale" value="{{ old('preferred_locale', $applicant->preferred_locale) }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('fields.phone') }} <span class="text-red-500">*</span></label>
                    <input type="tel" id="phone" name="phone"
                           value="{{ old('phone', $applicant->phone) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('phone') border-red-400 @enderror">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="alternative_phone" class="block text-sm font-medium text-gray-700">{{ __('fields.alternative_phone') }}</label>
                    <input type="tel" id="alternative_phone" name="alternative_phone"
                           value="{{ old('alternative_phone', $applicant->alternative_phone) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('fields.email') }} <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $applicant->email) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ethnicity" class="block text-sm font-medium text-gray-700">{{ __('applicant.ethnicity_optional') }}</label>
                    <input type="text" id="ethnicity" name="ethnicity"
                           value="{{ old('ethnicity', $applicant->ethnicity) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">{{ __('fields.address') }}</label>
                    <textarea id="address" name="address" rows="2"
                              class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('address', $applicant->address) }}</textarea>
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Profile Documents ───────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('applicant.uploaded_documents') }}</h2>

            @php $existing = $applicant->profileDocuments->firstWhere('document_type', 'documents'); @endphp

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 space-y-3">
                <div class="flex items-start gap-3">
                    <svg class="h-8 w-8 shrink-0 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-700">{{ __('documents.type_documents') }}</p>
                            @if($existing)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">{{ __('applicant.doc_uploaded') }}</span>
                            @endif
                        </div>
                        @if($existing)
                        <p class="mt-0.5 text-xs text-gray-400 truncate">
                            {{ $existing->original_name }} &middot; {{ $existing->file_size_mb }} MB &middot;
                            <a href="{{ route('applicant.profile.documents.download', $existing) }}"
                               class="text-blue-600 hover:text-blue-800">{{ __('menus.download') ?? 'Download' }}</a>
                        </p>
                        @else
                        <p class="mt-0.5 text-xs text-gray-400">{{ __('applicant.doc_not_uploaded') }}</p>
                        @endif

                        <label class="mt-2 block text-xs text-gray-500">
                            {{ $existing ? __('applicant.doc_replace') : __('applicant.doc_upload_new') }}
                        </label>
                        <input type="file" name="documents" accept=".pdf"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 @error('documents') border border-red-300 rounded-md @enderror">
                        @error('documents')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-400">PDF only &middot; max 2 MB</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                {{ __('applicant.save_changes') }}
            </button>
            <a href="{{ route('applicant.profile.show') }}"
               class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                {{ __('applicant.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
