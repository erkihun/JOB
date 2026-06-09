@extends('layouts.applicant')

@section('title', __('applicant.apply_for_position'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.apply_for_position') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $vacancy->getTranslation('title', app()->getLocale(), false)
               ?: $vacancy->getTranslation('title', 'en', false) }}
        </p>

        {{-- Announcement context: institution + deadline --}}
        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
            @if($vacancy->institution)
            <span class="inline-flex items-center gap-1.5 text-gray-700">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h2m-2 4h2m-2 4h2m4-8h2m-2 4h2m-2 4h2"/>
                </svg>
                <span class="font-medium">{{ $vacancy->institution->name }}</span>
            </span>
            @endif
            <span class="inline-flex items-center gap-1.5 text-gray-700">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ __('vacancies.closing_date') }}: <span class="font-medium">{{ et_date($vacancy->closing_date, 'd M Y') }}</span></span>
            </span>
        </div>

        <p class="mt-2 text-xs text-gray-400">{{ __('applications.other_institutions_note') }}</p>
    </div>

    @php
        // A field is "supplied by profile" when the profile already has a value,
        // so the form hides it and submits the stored value silently.
        $hasFieldOfStudy  = ! empty($defaults['field_of_study']);
        $hasGraduation    = ! empty($defaults['graduation_date']);
        $hasCgpa          = ! empty($defaults['cgpa']);
        $showAcademic     = ! ($hasFieldOfStudy && $hasGraduation);
    @endphp

    <form method="POST"
          action="{{ route('applicant.applications.store', $vacancy) }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf

        {{-- Silently submit any academic value already on the profile --}}
        @if($hasFieldOfStudy)
            <input type="hidden" name="field_of_study" value="{{ $defaults['field_of_study'] }}">
        @endif
        @if($hasGraduation)
            <input type="hidden" name="graduation_date" value="{{ $defaults['graduation_date'] }}">
        @endif
        @if($hasCgpa)
            <input type="hidden" name="cgpa" value="{{ $defaults['cgpa'] }}">
        @endif

        @php $oneClick = ($profileComplete ?? false) || (! $showAcademic && $requiredDocuments->isEmpty()); @endphp

        @if($oneClick)
        <div class="rounded-xl border border-green-200 bg-green-50 p-6">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800">{{ __('applications.ready_to_apply') }}</p>
                    <p class="mt-1 text-xs text-green-700">{{ __('applications.using_profile_data') }}</p>
                </div>
            </div>
        </div>
        @elseif(! $showAcademic)
        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
            {{ __('applications.using_profile_data') }}
        </div>
        @endif

        @if($showAcademic)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-900">{{ __('applicant.academic_info') }}</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Field of Study --}}
                @unless($hasFieldOfStudy)
                <div>
                    <label for="field_of_study" class="block text-sm font-medium text-gray-700">
                        {{ __('fields.field_of_study') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="field_of_study" name="field_of_study"
                           value="{{ old('field_of_study', $defaults['field_of_study']) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('field_of_study') border-red-400 @enderror">
                    @error('field_of_study')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endunless

                {{-- Graduation Date --}}
                @unless($hasGraduation)
                    @if(app()->getLocale() === 'am')
                        <x-ethiopian-datepicker
                            name="graduation_date"
                            :label="__('fields.graduation_date')"
                            :value="old('graduation_date', $defaults['graduation_date'])"
                            :max="now()->toDateString()"
                            required/>
                    @else
                    <div>
                        <label for="graduation_date" class="block text-sm font-medium text-gray-700">
                            {{ __('fields.graduation_date') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="graduation_date" name="graduation_date"
                               value="{{ old('graduation_date', $defaults['graduation_date']) }}"
                               max="{{ now()->toDateString() }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('graduation_date') border-red-400 @enderror">
                        @error('graduation_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @endif
                @endunless

                {{-- CGPA --}}
                @unless($hasCgpa)
                <div>
                    <label for="cgpa" class="block text-sm font-medium text-gray-700">
                        {{ __('applicant.cgpa_optional') }}
                    </label>
                    <input type="number" id="cgpa" name="cgpa"
                           value="{{ old('cgpa', $defaults['cgpa']) }}"
                           step="0.01" min="0" max="4"
                           placeholder="0.00 – 4.00"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('cgpa') border-red-400 @enderror">
                    @error('cgpa')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endunless
            </div>
        </div>
        @endif

        @if($requiredDocuments->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-900">{{ __('applicant.uploaded_documents') }}</h2>

            <div class="space-y-4">
                @foreach($requiredDocuments as $doc)
                @php
                    $types = $doc->allowed_types ?? ['pdf', 'jpg', 'jpeg', 'png'];
                    $maxMb = $doc->max_size_mb ?? 2;
                    $docName = $doc->document_name;
                @endphp
                <div>
                    <label for="doc_{{ $doc->id }}" class="block text-sm font-medium text-gray-700">
                        {{ $docName }}
                        @if($doc->is_required)
                            <span class="text-red-500">*</span>
                        @else
                            <span class="text-gray-400 font-normal text-xs">({{ __('applicant.optional') }})</span>
                        @endif
                    </label>
                    <p class="text-xs text-gray-400 mb-1">
                        {{ strtoupper(implode(', ', $types)) }} · {{ __('documents.max_size') }} {{ $maxMb }} MB
                    </p>
                    <input type="file"
                           id="doc_{{ $doc->id }}"
                           name="documents[{{ $doc->id }}]"
                           accept="{{ implode(',', array_map(fn($t) => '.' . $t, $types)) }}"
                           class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 @error('documents.' . $doc->id) border border-red-400 rounded-md @enderror">
                    @error('documents.' . $doc->id)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if($doc->description)
                    <p class="mt-1 text-xs text-gray-400">
                        {{ $doc->description }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex gap-3">
            <button type="submit"
                    class="rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __('applicant.submit_application') }}
            </button>
            <a href="{{ route('vacancies.show', $vacancy) }}"
               class="rounded-md border border-gray-300 px-5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                {{ __('applicant.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
