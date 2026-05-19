@php $isEdit = isset($vacancy) && $vacancy->exists; @endphp

<div class="grid gap-6 lg:grid-cols-3">

    <div class="space-y-5 lg:col-span-2">
        <div class="rounded-xl border border-gray-100 bg-white p-6" style="box-shadow: var(--shadow-card)">
            <div class="mb-5 flex items-center gap-2">
                <span class="h-5 w-1 rounded-full bg-accent"></span>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('vacancies.basic_info') }}</h2>
            </div>

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('vacancies.code') }}
                            @if(!($autoCode ?? false)) <span class="text-red-500">*</span> @endif
                        </label>
                        @if($autoCode ?? false)
                            {{-- Auto-generate mode: show existing code (edit) or preview (create) --}}
                            <div class="form-input mt-1 flex items-center gap-2 bg-gray-50">
                                <span class="font-mono text-sm font-medium text-gray-700">
                                    {{ $vacancy->code ?: ($codePreview ?? __('settings.code_auto_generate')) }}
                                </span>
                                <span class="ml-auto rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ __('settings.auto') }}</span>
                            </div>
                        @else
                            <input
                                type="text"
                                name="code"
                                value="{{ old('code', $vacancy->code ?? '') }}"
                                class="form-input mt-1 font-mono @error('code') form-input-error @enderror"
                                placeholder="VAC-2026-0001"
                            >
                            @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.department') }}</label>
                        <input
                            type="text"
                            name="department"
                            value="{{ old('department', $vacancy->department ?? '') }}"
                            class="form-input mt-1"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('vacancies.title') }} (English) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="title[en]"
                        value="{{ old('title.en', $vacancy->getTranslation('title', 'en', false) ?? '') }}"
                        class="form-input mt-1 @error('title.en') form-input-error @enderror"
                    >
                    @error('title.en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.title') }} (አማርኛ)</label>
                    <input
                        type="text"
                        name="title[am]"
                        value="{{ old('title.am', $vacancy->getTranslation('title', 'am', false) ?? '') }}"
                        class="form-input mt-1"
                    >
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('vacancies.location') }} (English) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="location[en]"
                            value="{{ old('location.en', $vacancy->getTranslation('location', 'en', false) ?? '') }}"
                            class="form-input mt-1 @error('location.en') form-input-error @enderror"
                        >
                        @error('location.en')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.location') }} (አማርኛ)</label>
                        <input
                            type="text"
                            name="location[am]"
                            value="{{ old('location.am', $vacancy->getTranslation('location', 'am', false) ?? '') }}"
                            class="form-input mt-1"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.description') }} (English)</label>
                    <textarea name="description[en]" rows="4" class="form-textarea mt-1">{{ old('description.en', $vacancy->getTranslation('description', 'en', false) ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.description') }} (አማርኛ)</label>
                    <textarea name="description[am]" rows="4" class="form-textarea mt-1">{{ old('description.am', $vacancy->getTranslation('description', 'am', false) ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.qualification_requirements') }} (English)</label>
                    <textarea name="qualification_requirements[en]" rows="4" class="form-textarea mt-1">{{ old('qualification_requirements.en', $vacancy->getTranslation('qualification_requirements', 'en', false) ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.qualification_requirements') }} (አማርኛ)</label>
                    <textarea name="qualification_requirements[am]" rows="4" class="form-textarea mt-1">{{ old('qualification_requirements.am', $vacancy->getTranslation('qualification_requirements', 'am', false) ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="rounded-xl border border-gray-100 bg-white p-6" style="box-shadow: var(--shadow-card)">
            <div class="mb-5 flex items-center gap-2">
                <span class="h-5 w-1 rounded-full bg-brand"></span>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('vacancies.details') }}</h2>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('vacancies.status') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="status" class="form-select mt-1">
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" {{ old('status', $vacancy->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>
                                {{ $s->getLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.employment_type') }}</label>
                    <select name="employment_type" class="form-select mt-1">
                        <option value="">--</option>
                        @foreach ($employmentTypes as $et)
                            <option value="{{ $et->value }}" {{ old('employment_type', $vacancy->employment_type?->value ?? '') === $et->value ? 'selected' : '' }}>
                                {{ $et->getLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('vacancies.positions') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="number_of_positions"
                        min="1"
                        value="{{ old('number_of_positions', $vacancy->number_of_positions ?? 1) }}"
                        class="form-input mt-1"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.education_level') }}</label>
                    <select name="education_level" class="form-select mt-1">
                        <option value="">--</option>
                        @foreach ($educationLevels as $el)
                            <option value="{{ $el->value }}" {{ old('education_level', $vacancy->education_level?->value ?? '') === $el->value ? 'selected' : '' }}>
                                {{ $el->getLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.field_of_study') }}</label>
                    <input
                        type="text"
                        name="field_of_study"
                        value="{{ old('field_of_study', $vacancy->field_of_study ?? '') }}"
                        class="form-input mt-1"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.minimum_experience') }} (years)</label>
                    <input
                        type="number"
                        name="minimum_experience"
                        min="0"
                        value="{{ old('minimum_experience', $vacancy->minimum_experience ?? 0) }}"
                        class="form-input mt-1"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.salary_grade') }}</label>
                    <input
                        type="text"
                        name="salary_grade"
                        value="{{ old('salary_grade', $vacancy->salary_grade ?? '') }}"
                        class="form-input mt-1"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('vacancies.opening_date') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="opening_date"
                        value="{{ old('opening_date', $vacancy->opening_date?->format('Y-m-d') ?? '') }}"
                        class="form-input mt-1 @error('opening_date') form-input-error @enderror"
                    >
                    @error('opening_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('vacancies.closing_date') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="closing_date"
                        value="{{ old('closing_date', $vacancy->closing_date?->format('Y-m-d') ?? '') }}"
                        class="form-input mt-1 @error('closing_date') form-input-error @enderror"
                    >
                    @error('closing_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary flex-1 justify-center">
                {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
            </button>
            <a href="{{ route('admin.vacancies.index') }}" class="btn btn-secondary">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </div>
</div>
