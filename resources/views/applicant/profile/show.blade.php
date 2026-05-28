@extends('layouts.applicant')

@section('title', __('menus.profile'))

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.profile_heading') }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">{{ __('applicant.profile_subtitle') }}</p>
        </div>
        <a href="{{ route('applicant.profile.edit') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
            {{ __('applicant.edit_profile') }}
        </a>
    </div>

    {{-- Profile photo + name --}}
    <div class="flex items-center gap-5 rounded-xl border border-gray-200 bg-white shadow-sm px-6 py-5">
        @if($applicant->profile_photo_path)
        <img src="{{ route('applicant.profile.photo') }}" alt=""
             class="h-20 w-20 shrink-0 rounded-full object-cover border-2 border-gray-200">
        @else
        <div class="h-20 w-20 shrink-0 rounded-full bg-gray-100 flex items-center justify-center border-2 border-gray-200">
            <svg class="h-9 w-9 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        @endif
        <div>
            <p class="text-lg font-bold text-gray-900">{{ $applicant->full_name }}</p>
            <p class="text-sm text-gray-500">{{ $applicant->email }}</p>
        </div>
    </div>

    {{-- Personal Information --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.personal_info') }}</h2>
        </div>
        @php
            $rows = [
                [__('fields.first_name'),   $applicant->first_name  ?? __('applicant.not_provided')],
                [__('fields.middle_name'),  $applicant->middle_name ?? '—'],
                [__('fields.last_name'),    $applicant->last_name   ?? __('applicant.not_provided')],
                [__('fields.gender'),       $applicant->gender?->label() ?? __('applicant.not_provided')],
                [__('fields.date_of_birth'), $applicant->date_of_birth ? et_date($applicant->date_of_birth) : __('applicant.not_provided')],
                [__('fields.nationality'),  $applicant->nationality ?? __('applicant.not_provided')],
                [__('fields.national_id'),  $applicant->national_id ?: __('applicant.not_provided')],
                [__('fields.disability_status'), $applicant->disability_status
                    ? ($applicant->disability_type ?? __('applicant.disability_yes'))
                    : __('applicant.disability_no')],
            ];
        @endphp
        @foreach($rows as [$label, $value])
        <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
            <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
            <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Education --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.education_info') }}</h2>
        </div>
        @php
            $eduRows = [
                [__('fields.university_name'), $applicant->university_name ?? __('applicant.not_provided')],
                [__('fields.field_of_study'),  $applicant->field_of_study  ?? __('applicant.not_provided')],
                [__('fields.education_level'), $applicant->education_level?->getLabel() ?? __('applicant.not_provided')],
                [__('fields.graduation_year'), $applicant->graduation_year  ?? __('applicant.not_provided')],
                [__('fields.gpa'),             $applicant->gpa !== null ? number_format((float)$applicant->gpa, 2) . ' / 4.00' : __('applicant.not_provided')],
            ];
        @endphp
        @foreach($eduRows as [$label, $value])
        <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
            <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
            <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Work Experience --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.work_info') }}</h2>
        </div>
        @php
            $yrs = (int) ($applicant->work_experience_years ?? 0);
            $mos = (int) ($applicant->work_experience_months ?? 0);
            $expStr = $yrs . ' ' . __('public.years') . ($mos ? ', ' . $mos . ' mo' : '');
            $workRows = [
                [__('fields.work_experience'), $expStr],
                [__('fields.current_employer'), $applicant->current_employer ?? __('applicant.not_provided')],
                [__('fields.current_position'), $applicant->current_position ?? __('applicant.not_provided')],
            ];
        @endphp
        @foreach($workRows as [$label, $value])
        <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
            <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
            <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
        @if($applicant->work_experience_summary)
        <div class="px-5 py-3">
            <dt class="text-sm font-medium text-gray-500 mb-1">{{ __('fields.work_experience_summary') }}</dt>
            <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $applicant->work_experience_summary }}</dd>
        </div>
        @endif
    </div>

    {{-- Contact --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.contact_info') }}</h2>
        </div>
        @php
            $contactRows = [
                [__('fields.phone'),             $applicant->phone],
                [__('fields.alternative_phone'), $applicant->alternative_phone ?? __('applicant.not_provided')],
                [__('fields.email'),             $applicant->email],
                [__('fields.address'),           $applicant->address ?? __('applicant.not_provided')],
            ];
        @endphp
        @foreach($contactRows as [$label, $value])
        <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
            <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
            <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Profile documents --}}
    @if($applicant->profileDocuments->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('applicant.uploaded_documents') }}</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($applicant->profileDocuments as $doc)
            <div class="flex items-center justify-between px-5 py-3 gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        {{ __('documents.type_' . $doc->document_type) }}
                    </p>
                    <p class="text-xs text-gray-400 truncate">{{ $doc->original_name }} &middot; {{ $doc->file_size_mb }} MB</p>
                </div>
                <a href="{{ route('applicant.profile.documents.download', $doc) }}"
                   class="shrink-0 text-sm text-blue-600 hover:text-blue-800">
                    {{ __('menus.download') ?? 'Download' }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
