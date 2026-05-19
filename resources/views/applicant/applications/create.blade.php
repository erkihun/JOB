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
    </div>

    <form method="POST"
          action="{{ route('applicant.applications.store', $vacancy) }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-900">{{ __('applicant.academic_info') }}</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Field of Study --}}
                <div>
                    <label for="field_of_study" class="block text-sm font-medium text-gray-700">
                        {{ __('fields.field_of_study') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="field_of_study" name="field_of_study"
                           value="{{ old('field_of_study') }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('field_of_study') border-red-400 @enderror">
                    @error('field_of_study')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Graduation Date --}}
                <div>
                    <label for="graduation_date" class="block text-sm font-medium text-gray-700">
                        {{ __('fields.graduation_date') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="graduation_date" name="graduation_date"
                           value="{{ old('graduation_date') }}"
                           max="{{ now()->toDateString() }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('graduation_date') border-red-400 @enderror">
                    @error('graduation_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- CGPA --}}
                <div>
                    <label for="cgpa" class="block text-sm font-medium text-gray-700">
                        {{ __('applicant.cgpa_optional') }}
                    </label>
                    <input type="number" id="cgpa" name="cgpa"
                           value="{{ old('cgpa') }}"
                           step="0.01" min="0" max="4"
                           placeholder="0.00 – 4.00"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('cgpa') border-red-400 @enderror">
                    @error('cgpa')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        @if($requiredDocuments->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-900">{{ __('applicant.uploaded_documents') }}</h2>

            <div class="space-y-4">
                @foreach($requiredDocuments as $doc)
                @php
                    $types = $doc->allowed_types ?? ['pdf', 'jpg', 'jpeg', 'png'];
                    $maxMb = $doc->max_size_mb ?? 2;
                    $docName = $doc->getTranslation('document_name', app()->getLocale(), false)
                               ?: $doc->getTranslation('document_name', 'en', false)
                               ?: $doc->document_name;
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
                        {{ $doc->getTranslation('description', app()->getLocale(), false) ?: $doc->description }}
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
