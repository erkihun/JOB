@extends('layouts.admin')
@section('title', $application->reference_number)

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.applications.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.applications') }}</a>
            <h1 class="mt-1 text-lg font-semibold text-gray-900">{{ $application->reference_number }}</h1>
        </div>
        <a href="{{ route('admin.screening.review', $application) }}" class="btn btn-primary">
            {{ __('messages.review_application') }} →
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Applicant profile --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.personal_info') }}</h2>
                </div>
                <div class="mb-4 flex items-center gap-4">
                    @if($application->applicant?->profile_photo_path)
                    <img src="{{ route('applicant.profile.photo') }}" class="h-16 w-16 rounded-full border border-gray-200 object-cover">
                    @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-muted text-lg font-semibold text-brand ring-1 ring-brand/10">
                        {{ mb_substr($application->applicant?->full_name ?? '?', 0, 2) }}
                    </div>
                    @endif
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ $application->applicant?->full_name }}</p>
                        <p class="text-sm text-gray-500">{{ $canViewSensitive ? ($application->applicant?->email ?? '—') : __('dashboard.restricted') }}</p>
                    </div>
                </div>
                @php
                $info = [
                    __('fields.gender')            => $application->applicant?->gender?->getLabel(),
                    __('fields.date_of_birth')     => $application->applicant?->date_of_birth?->format('d M Y'),
                    __('fields.national_id')       => $canViewSensitive ? $application->applicant?->national_id : __('dashboard.restricted'),
                    __('fields.nationality')       => $application->applicant?->nationality,
                    __('fields.phone')             => $canViewSensitive ? $application->applicant?->phone : __('dashboard.restricted'),
                    __('fields.disability_status') => $application->applicant?->disability_status ? __('applicant.disability_yes') : __('applicant.disability_no'),
                ];
                @endphp
                <dl class="grid gap-3 sm:grid-cols-2">
                    @foreach($info as $label => $value)
                    <div>
                        <dt class="text-xs text-gray-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.education_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-2">
                    @php
                    $edu = [
                        __('fields.university_name') => $application->applicant?->university_name,
                        __('fields.field_of_study')  => $application->applicant?->field_of_study,
                        __('fields.education_level') => $application->applicant?->education_level?->getLabel(),
                        __('fields.graduation_year') => $application->applicant?->graduation_year,
                        __('fields.gpa')             => $application->applicant?->gpa,
                    ];
                    @endphp
                    @foreach($edu as $label => $value)
                    <div>
                        <dt class="text-xs text-gray-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            {{-- Documents --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.uploaded_documents') }}</h2>
                </div>
                @if($application->applicant?->profileDocuments->isEmpty())
                <p class="text-sm text-gray-400">{{ __('applicant.no_documents') }}</p>
                @else
                <div class="space-y-2">
                    @foreach($application->applicant->profileDocuments as $doc)
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $doc->original_name }}</p>
                            <p class="text-xs text-gray-400">{{ $doc->document_type }} · {{ $doc->file_size_mb }} MB</p>
                        </div>
                        <a href="{{ route('admin.documents.download', $doc) }}"
                           class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.download') }}</a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('vacancies.vacancy') }}</h2>
                </div>
                <p class="font-semibold text-gray-900">{{ $application->vacancy?->title }}</p>
                <p class="mt-1 font-mono text-xs text-gray-400">{{ $application->vacancy?->code }}</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('vacancies.status') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->status->getLabel() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('messages.submitted') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Screening history --}}
            @if($application->screeningReviews->isNotEmpty())
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-navy"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.screening_history') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach($application->screeningReviews as $review)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold {{ $review->decision->value === 'passed' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $review->decision->getLabel() }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ $review->reviewer?->name }}</p>
                        @if($review->remarks)
                        <p class="mt-1 text-xs text-gray-600">{{ $review->remarks }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
