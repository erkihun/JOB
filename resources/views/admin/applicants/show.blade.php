@extends('layouts.admin')
@section('title', $applicant->full_name ?: __('menus.applicants'))
@section('breadcrumb')
    <a href="{{ route('admin.applicants.index') }}" class="hover:text-brand">{{ __('menus.applicants') }}</a>
    <span class="mx-1 text-gray-400">/</span> {{ $applicant->full_name ?: $applicant->email }}
@endsection

@section('content')
<div class="space-y-5">

    {{-- Back --}}
    <a href="{{ route('admin.applicants.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.applicants') }}</a>

    {{-- Profile header --}}
    <div class="flex items-center gap-5 rounded-xl border border-gray-100 bg-white px-6 py-5 shadow-sm">
        @if($applicant->profile_photo_path)
            <img src="{{ route('admin.applicants.photo', $applicant) }}" alt=""
                 class="h-20 w-20 shrink-0 rounded-full object-cover border-2 border-gray-200">
        @else
            <div class="h-20 w-20 shrink-0 rounded-full bg-blue-100 flex items-center justify-center border-2 border-gray-200">
                <span class="text-2xl font-bold text-blue-600">{{ strtoupper(substr($applicant->first_name ?? '?', 0, 1)) }}</span>
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-gray-900">{{ $applicant->full_name ?: '—' }}</h1>
            <p class="text-sm text-gray-500">{{ $applicant->email }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @if($applicant->applicant_code)
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-mono font-semibold text-gray-600">{{ $applicant->applicant_code }}</span>
                @endif
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                    {{ $applicant->applications_count ?? $applicant->applications->count() }} {{ __('menus.applications') }}
                </span>
                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                    {{ __('messages.registered') }}: {{ $applicant->created_at->format('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Personal Information --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.personal_info') }}</h2>
            </div>
            @php
                $rows = [
                    [__('fields.first_name'),    $applicant->first_name   ?? '—'],
                    [__('fields.middle_name'),   $applicant->middle_name  ?? '—'],
                    [__('fields.last_name'),     $applicant->last_name    ?? '—'],
                    [__('fields.gender'),        $applicant->gender?->label() ?? '—'],
                    [__('fields.date_of_birth'), $applicant->date_of_birth?->format('d M Y') ?? '—'],
                    [__('fields.nationality'),   $applicant->nationality  ?? '—'],
                    [__('fields.national_id'),   $applicant->national_id  ?: '—'],
                    [__('fields.disability_status'), $applicant->disability_status
                        ? ($applicant->disability_type ?? __('applicant.disability_yes'))
                        : __('applicant.disability_no')],
                ];
            @endphp
            @foreach($rows as [$label, $value])
            <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
                <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
                <dd class="text-sm text-gray-900">{{ $value }}</dd>
            </div>
            @endforeach
        </div>

        {{-- Contact Information --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.contact_info') }}</h2>
            </div>
            @php
                $rows = [
                    [__('fields.email'),             $applicant->email            ?: '—'],
                    [__('fields.phone'),             $applicant->phone            ?: '—'],
                    [__('fields.alternative_phone'), $applicant->alternative_phone ?: '—'],
                    [__('fields.address'),           $applicant->address          ?: '—'],
                ];
            @endphp
            @foreach($rows as [$label, $value])
            <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
                <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
                <dd class="text-sm text-gray-900">{{ $value }}</dd>
            </div>
            @endforeach
        </div>

        {{-- Education --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.education') }}</h2>
            </div>
            @php
                $rows = [
                    [__('fields.education_level'),  $applicant->education_level?->label() ?? '—'],
                    [__('fields.university_name'),  $applicant->university_name  ?: '—'],
                    [__('fields.field_of_study'),   $applicant->field_of_study   ?: '—'],
                    [__('fields.graduation_year'),  $applicant->graduation_year  ?: '—'],
                    [__('fields.gpa'),              $applicant->gpa              ?: '—'],
                ];
            @endphp
            @foreach($rows as [$label, $value])
            <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
                <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
                <dd class="text-sm text-gray-900">{{ $value }}</dd>
            </div>
            @endforeach
        </div>

        {{-- Work Experience --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
                <div class="h-4 w-0.5 rounded bg-accent"></div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.work_experience') }}</h2>
            </div>
            @php
                $exp = '';
                if ($applicant->work_experience_years || $applicant->work_experience_months) {
                    $exp = ($applicant->work_experience_years  ? $applicant->work_experience_years  . ' ' . __('fields.years')  : '')
                         . ($applicant->work_experience_months ? ' ' . $applicant->work_experience_months . ' ' . __('fields.months') : '');
                }
                $rows = [
                    [__('fields.experience'),        $exp ?: '—'],
                    [__('fields.current_employer'),  $applicant->current_employer  ?: '—'],
                    [__('fields.current_position'),  $applicant->current_position  ?: '—'],
                    [__('fields.experience_summary'),$applicant->work_experience_summary ?: '—'],
                ];
            @endphp
            @foreach($rows as [$label, $value])
            <div class="flex items-start border-b border-gray-50 last:border-b-0 px-5 py-3 gap-4">
                <dt class="w-44 shrink-0 text-sm font-medium text-gray-500">{{ $label }}</dt>
                <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $value }}</dd>
            </div>
            @endforeach
        </div>

    </div>

    {{-- Applications --}}
    @if($applicant->applications->count())
    <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
            <div class="h-4 w-0.5 rounded bg-accent"></div>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('menus.applications') }}</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-50 text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-2.5 text-left">{{ __('fields.reference_number') }}</th>
                    <th class="px-5 py-2.5 text-left">{{ __('menus.vacancies') }}</th>
                    <th class="px-5 py-2.5 text-left">{{ __('fields.status') }}</th>
                    <th class="px-5 py-2.5 text-left">{{ __('fields.submitted_at') }}</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($applicant->applications as $application)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $application->reference_number }}</td>
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $application->vacancy?->getTranslation('title', app()->getLocale()) ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $application->status->value === 'submitted' ? 'bg-blue-50 text-blue-700' :
                               ($application->status->value === 'passed' ? 'bg-green-50 text-green-700' :
                               ($application->status->value === 'failed' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600')) }}">
                            {{ $application->status->label() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $application->submitted_at?->format('d M Y') ?? '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.applications.show', $application) }}"
                           class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.view') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Profile Documents --}}
    @if($applicant->profileDocuments->count())
    <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden"
         x-data="{ previewUrl: '', previewName: '', open: false, loading: false }">

        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 flex items-center gap-2">
            <div class="h-4 w-0.5 rounded bg-accent"></div>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('menus.documents') }}</h2>
        </div>

        <div class="divide-y divide-gray-50">
            @foreach($applicant->profileDocuments as $doc)
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <svg class="h-8 w-8 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->original_name }}</p>
                        <p class="text-xs text-gray-400">{{ number_format($doc->file_size / 1024, 1) }} KB</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 shrink-0 ml-4">
                    <button type="button"
                            @click="previewUrl = '{{ route('admin.profile-documents.preview', $doc) }}'; previewName = '{{ addslashes($doc->original_name) }}'; loading = true; open = true"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ __('messages.view') }}
                    </button>
                    <a href="{{ route('admin.profile-documents.download', $doc) }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-gray-800 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ __('messages.download') }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        {{-- PDF Preview Modal --}}
        <template x-teleport="body">
            <div x-show="open"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape.window="open = false; previewUrl = ''"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                     @click="open = false; previewUrl = ''"></div>

                {{-- Modal panel --}}
                <div class="relative z-10 flex flex-col w-full max-w-5xl h-[90vh] rounded-xl bg-white shadow-2xl overflow-hidden"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="scale-95 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="scale-100 opacity-100"
                     x-transition:leave-end="scale-95 opacity-0">

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-gray-50 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="h-4 w-4 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-800 truncate" x-text="previewName"></span>
                        </div>
                        <button type="button"
                                @click="open = false; previewUrl = ''"
                                class="ml-4 shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- PDF viewer --}}
                    <div class="relative flex-1 min-h-0">
                        {{-- Loading spinner --}}
                        <div x-show="loading"
                             class="absolute inset-0 flex flex-col items-center justify-center bg-gray-100 z-10 gap-3">
                            <svg class="h-8 w-8 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span class="text-sm text-gray-500">Loading document…</span>
                        </div>
                        <iframe :src="open ? previewUrl : ''"
                                @load="loading = false"
                                class="h-full w-full border-0 bg-gray-100"
                                title="Document Preview"></iframe>
                    </div>
                </div>
            </div>
        </template>

    </div>
    @endif

</div>
@endsection
