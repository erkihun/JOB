@extends('layouts.applicant')
@section('title', __('menus.profile'))

@section('content')
@php
    $yrs    = (int) ($applicant->work_experience_years  ?? 0);
    $mos    = (int) ($applicant->work_experience_months ?? 0);
    $expStr = trim(($yrs ? $yrs . ' ' . __('public.years') : '') . ($mos ? ' ' . $mos . ' ' . __('fields.months') : '')) ?: __('applicant.not_provided');
    $appsCount = $applicant->applications()->count();
@endphp

{{-- ── Hero ── --}}
<div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 shadow-lg">
    {{-- decorative circles --}}
    <div class="pointer-events-none absolute -right-12 -top-12 h-56 w-56 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -left-8 bottom-0 h-40 w-40 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-5 px-6 pb-8 pt-10 sm:flex-row sm:items-end sm:gap-7 sm:px-10">

        {{-- Avatar --}}
        <div class="shrink-0">
            @if($applicant->profile_photo_path)
            <img src="{{ route('applicant.profile.photo') }}" alt=""
                 class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white/30 shadow-xl sm:h-28 sm:w-28">
            @else
            <div class="h-24 w-24 rounded-2xl bg-white/15 ring-4 ring-white/30 flex items-center justify-center shadow-xl sm:h-28 sm:w-28">
                <span class="text-4xl font-black text-white/80 sm:text-5xl">
                    {{ mb_strtoupper(mb_substr($applicant->first_name ?? '?', 0, 1)) }}
                </span>
            </div>
            @endif
        </div>

        {{-- Name & meta --}}
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-black text-white leading-tight truncate sm:text-3xl">
                {{ $applicant->full_name ?: '—' }}
            </h1>
            <p class="mt-1 text-sm text-blue-100">{{ $applicant->email }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                @if($applicant->applicant_code)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
                    {{ $applicant->applicant_code }}
                </span>
                @endif
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ $appsCount }} {{ __('menus.applications') }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ et_date($applicant->created_at, 'M Y') }}
                </span>
            </div>
        </div>

        {{-- Edit button --}}
        <a href="{{ route('applicant.profile.edit') }}"
           class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            {{ __('applicant.edit_profile') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
    <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- ── Info grid ── --}}
<div class="grid gap-5 lg:grid-cols-2">

    {{-- Personal --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-5 py-3.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100">
                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ __('applicant.personal_info') }}</h2>
        </div>
        @php
            $rows = [
                [__('fields.first_name'),        $applicant->first_name   ?? __('applicant.not_provided')],
                [__('fields.middle_name'),        $applicant->middle_name  ?: '—'],
                [__('fields.last_name'),          $applicant->last_name    ?? __('applicant.not_provided')],
                [__('fields.gender'),             $applicant->gender?->label() ?? __('applicant.not_provided')],
                [__('fields.date_of_birth'),      $applicant->date_of_birth ? et_date($applicant->date_of_birth) : __('applicant.not_provided')],
                [__('fields.nationality'),        $applicant->nationality  ?? __('applicant.not_provided')],
                [__('fields.national_id'),        $applicant->national_id  ?: __('applicant.not_provided')],
                [__('fields.disability_status'),  $applicant->disability_status
                    ? ($applicant->disability_type ?? __('applicant.disability_yes'))
                    : __('applicant.disability_no')],
            ];
        @endphp
        @foreach($rows as [$label, $value])
        <div class="flex items-start gap-4 border-b border-gray-50 px-5 py-3 last:border-b-0 hover:bg-gray-50/50 transition-colors">
            <dt class="w-40 shrink-0 text-xs font-medium text-gray-400 pt-0.5 uppercase tracking-wide">{{ $label }}</dt>
            <dd class="text-sm font-medium text-gray-800 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Contact --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-pink-50 px-5 py-3.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100">
                <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ __('applicant.contact_info') }}</h2>
        </div>
        @php
            $contactRows = [
                [__('fields.email'),             $applicant->email],
                [__('fields.phone'),             $applicant->phone ?: __('applicant.not_provided')],
                [__('fields.alternative_phone'), $applicant->alternative_phone ?: __('applicant.not_provided')],
                [__('fields.region'),            $applicant->region   ?: '—'],
                [__('fields.city'),              $applicant->city     ?: '—'],
                [__('fields.address'),           $applicant->address  ?: __('applicant.not_provided')],
            ];
        @endphp
        @foreach($contactRows as [$label, $value])
        <div class="flex items-start gap-4 border-b border-gray-50 px-5 py-3 last:border-b-0 hover:bg-gray-50/50 transition-colors">
            <dt class="w-40 shrink-0 text-xs font-medium text-gray-400 pt-0.5 uppercase tracking-wide">{{ $label }}</dt>
            <dd class="text-sm font-medium text-gray-800 flex-1 break-all">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Education --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-green-50 to-teal-50 px-5 py-3.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-100">
                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ __('applicant.education_info') }}</h2>
        </div>
        @php
            $eduRows = [
                [__('fields.education_level'), $applicant->education_level?->getLabel() ?? __('applicant.not_provided')],
                [__('fields.university_name'), $applicant->university_name ?? __('applicant.not_provided')],
                [__('fields.field_of_study'),  $applicant->field_of_study  ?? __('applicant.not_provided')],
                [__('fields.graduation_year'), $applicant->graduation_year  ?? __('applicant.not_provided')],
                [__('fields.gpa'),             $applicant->gpa !== null ? number_format((float)$applicant->gpa, 2) . ' / 4.00' : __('applicant.not_provided')],
            ];
        @endphp
        @foreach($eduRows as [$label, $value])
        <div class="flex items-start gap-4 border-b border-gray-50 px-5 py-3 last:border-b-0 hover:bg-gray-50/50 transition-colors">
            <dt class="w-40 shrink-0 text-xs font-medium text-gray-400 pt-0.5 uppercase tracking-wide">{{ $label }}</dt>
            <dd class="text-sm font-medium text-gray-800 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
    </div>

    {{-- Work Experience --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-3.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100">
                <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ __('applicant.work_info') }}</h2>
        </div>
        @php
            $workRows = [
                [__('fields.work_experience'),  $expStr],
                [__('fields.current_employer'), $applicant->current_employer ?? __('applicant.not_provided')],
                [__('fields.current_position'), $applicant->current_position ?? __('applicant.not_provided')],
            ];
        @endphp
        @foreach($workRows as [$label, $value])
        <div class="flex items-start gap-4 border-b border-gray-50 px-5 py-3 last:border-b-0 hover:bg-gray-50/50 transition-colors">
            <dt class="w-40 shrink-0 text-xs font-medium text-gray-400 pt-0.5 uppercase tracking-wide">{{ $label }}</dt>
            <dd class="text-sm font-medium text-gray-800 flex-1">{{ $value }}</dd>
        </div>
        @endforeach
        @if($applicant->work_experience_summary)
        <div class="border-t border-gray-50 px-5 py-4">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('fields.work_experience_summary') }}</p>
            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $applicant->work_experience_summary }}</p>
        </div>
        @endif
    </div>

</div>

{{-- ── Documents ── --}}
@if($applicant->profileDocuments->isNotEmpty())
<div class="mt-5 rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50 px-5 py-3.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100">
            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </span>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ __('applicant.uploaded_documents') }}</h2>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($applicant->profileDocuments as $doc)
        <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/50 transition-colors">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $doc->original_name }}</p>
                <p class="text-xs text-gray-400">{{ number_format($doc->file_size / 1024, 1) }} KB</p>
            </div>
            <a href="{{ route('applicant.profile.documents.download', $doc) }}"
               class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:border-blue-300 hover:text-blue-600 transition">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('messages.download') }}
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
