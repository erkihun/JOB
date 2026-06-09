@extends('layouts.applicant')

@section('title', __('applicant.edit_application_title'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.edit_application_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $application->vacancy->getTranslation('title', app()->getLocale(), false)
               ?: $application->vacancy->getTranslation('title', 'en', false) }}
            · {{ $application->reference_number }}
        </p>
    </div>

    {{-- Application Fields Form --}}
    <form method="POST"
          action="{{ route('applicant.applications.update', $application) }}"
          class="space-y-5">
        @csrf
        @method('PUT')

        {{-- Academic details are preserved from the original application (not re-asked). --}}
        <input type="hidden" name="field_of_study" value="{{ old('field_of_study', $application->field_of_study) }}">
        <input type="hidden" name="graduation_date" value="{{ old('graduation_date', \Carbon\Carbon::parse($application->graduation_date)->toDateString()) }}">
        <input type="hidden" name="cgpa" value="{{ old('cgpa', $application->cgpa) }}">

        {{-- Change applied position --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ __('applicant.applied_position') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('applicant.change_position_hint') }}</p>
            </div>

            <div>
                <label for="vacancy_id" class="block text-sm font-medium text-gray-700">
                    {{ __('menus.vacancies') }}
                </label>
                <select id="vacancy_id" name="vacancy_id"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @error('vacancy_id') border-red-400 @enderror">
                    @foreach($openVacancies as $v)
                        @php
                            $vTitle = $v->getTranslation('title', app()->getLocale(), false)
                                      ?: $v->getTranslation('title', 'en', false);
                            $vInst = $v->institution?->name;
                        @endphp
                        <option value="{{ $v->id }}"
                            {{ old('vacancy_id', $application->vacancy_id) === $v->id ? 'selected' : '' }}>
                            {{ $vTitle }}{{ $vInst ? ' — '.$vInst : '' }} ({{ $v->code }})
                        </option>
                    @endforeach
                </select>
                @error('vacancy_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __('applicant.save_changes') }}
            </button>
            <a href="{{ route('applicant.applications.show', $application) }}"
               class="rounded-md border border-gray-300 px-5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                {{ __('applicant.cancel') }}
            </a>
        </div>
    </form>

    {{-- Document Replacement --}}
    @if($application->documents->isNotEmpty())
    <div>
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('applicant.replace_documents') }}</h2>
        <div class="space-y-4">
            @foreach($application->documents as $document)
            @php
                $vacDoc  = $document->vacancyDocument;
                $types   = $vacDoc?->allowed_types ?? ['pdf', 'jpg', 'jpeg', 'png'];
                $maxMb   = $vacDoc?->max_size_mb ?? 2;
                $docName = $vacDoc?->document_name
                           ?: $document->original_name;
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                <p class="text-sm font-medium text-gray-900 mb-1">{{ $docName }}</p>
                <p class="text-xs text-gray-400 mb-3">
                    {{ __('applicant.current_file') }}: {{ $document->original_name }}
                    · {{ number_format($document->file_size / 1024, 1) }} KB
                </p>
                <form method="POST"
                      action="{{ route('applicant.applications.documents.replace', [$application, $document]) }}"
                      enctype="multipart/form-data"
                      class="flex items-start gap-3 flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400 mb-1">
                            {{ strtoupper(implode(', ', $types)) }} · {{ __('documents.max_size') }} {{ $maxMb }} MB
                        </p>
                        <input type="file"
                               name="file"
                               accept="{{ implode(',', array_map(fn($t) => '.' . $t, $types)) }}"
                               required
                               class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 @error('file_' . $document->id) border border-red-400 rounded-md @enderror">
                        @error('file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                            class="shrink-0 rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                        {{ __('applicant.replace') }}
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
