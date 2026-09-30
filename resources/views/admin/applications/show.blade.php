@extends('layouts.admin')
@section('title', $application->reference_number)

@section('content')
@php
    $applicant = $application->applicant;
    $restricted = __('dashboard.restricted');
    $personal = [
        __('fields.gender')            => $applicant?->gender?->getLabel(),
        __('fields.date_of_birth')     => et_date($applicant?->date_of_birth),
        __('fields.national_id')       => $canViewSensitive ? $applicant?->national_id : $restricted,
        __('fields.nationality')       => $applicant?->nationality,
        __('fields.phone')             => $canViewSensitive ? $applicant?->phone : $restricted,
        __('fields.email')             => $canViewSensitive ? $applicant?->email : $restricted,
        __('fields.disability_status') => $applicant?->disability_status ? __('applicant.disability_yes') : __('applicant.disability_no'),
    ];
    $education = [
        __('fields.education_level') => $applicant?->education_level?->getLabel(),
        __('fields.field_of_study')  => $application->field_of_study ?: $applicant?->field_of_study,
        __('fields.university_name') => $applicant?->university_name,
        __('fields.graduation_year') => $applicant?->graduation_year,
        __('fields.gpa')             => $application->cgpa ?? $applicant?->gpa,
    ];
    $docs = $application->documents;
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$applicant?->full_name ?? $application->reference_number"
                         :description="$application->reference_number.' · '.$application->vacancy?->title"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.applications'), 'url' => route('admin.applications.index')], ['label' => $application->reference_number]]">
        <x-admin.status :status="$application->status" />
        @if($applicant)
        <a href="{{ route('admin.applicants.show', $applicant) }}" class="btn btn-secondary">{{ __('messages.apps_view_applicant') }}</a>
        @endif
        @can('screening.view')
        <a href="{{ route('admin.screening.review', $application) }}" class="btn btn-primary">{{ __('messages.review_application') }}</a>
        @endcan
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        @if($applicant?->profile_photo_path)
                            <x-admin.avatar :name="$applicant->full_name" :photo="route('admin.applicants.photo', $applicant)" size="md" />
                        @else
                            <x-admin.avatar :name="$applicant?->full_name" size="md" />
                        @endif
                        <h2 class="card-title">{{ __('applicant.personal_info') }}</h2>
                    </div>
                </div>
                <dl class="dl-grid card-body">
                    @foreach($personal as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('applicant.education_info') }}</h2></div>
                <dl class="dl-grid card-body">
                    @foreach($education as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <x-admin.table-card :title="__('applicant.application_documents')" :meta="trans_choice('messages.files_count', $docs->count(), ['count' => $docs->count()])">
                @if($docs->isEmpty())
                    <x-admin.empty :text="__('applicant.no_documents')" />
                @else
                <ul class="divide-y divide-gray-100">
                    @foreach($docs as $doc)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $doc->vacancyDocument?->document_name ?? $doc->original_name }}</p>
                            <p class="truncate text-xs text-gray-600">{{ $doc->original_name }} · {{ number_format(($doc->file_size ?? 0) / 1024, 0) }} KB</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @if(str_starts_with((string) $doc->file_type, 'image/') || $doc->file_type === 'application/pdf')
                            <a href="{{ route('admin.documents.preview', $doc) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">{{ __('messages.preview') }}</a>
                            @endif
                            <a href="{{ route('admin.documents.download', $doc) }}" class="btn btn-secondary btn-sm">{{ __('messages.download') }}</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </x-admin.table-card>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('vacancies.vacancy') }}</h2>
                @if($application->vacancy)
                <a href="{{ route('admin.vacancies.show', $application->vacancy) }}" class="font-semibold text-brand hover:underline">{{ $application->vacancy->title }}</a>
                <p class="mt-0.5 font-mono text-xs text-gray-600">{{ $application->vacancy->code }}</p>
                @endif
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.submitted') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($application->submitted_at ?? $application->created_at) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('admin.institution_name') }}</dt><dd class="text-right font-semibold text-gray-900">{{ $application->vacancy?->institution?->displayName() ?? '—' }}</dd></div>
                    @if($application->screener)
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.screened_by') }}</dt><dd class="text-right font-semibold text-gray-900">{{ $application->screener->name }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('messages.screening_history') }}</h2>
                @forelse($application->screeningReviews->sortByDesc('created_at') as $review)
                <div class="border-t border-gray-100 py-3 first:border-0 first:pt-0">
                    <div class="flex items-center justify-between gap-3">
                        <x-admin.status :status="$review->decision" />
                        <span class="text-xs text-gray-600">{{ et_date($review->created_at) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-600">{{ $review->reviewer?->name }}</p>
                    @if($review->remark)<p class="mt-1 text-sm text-gray-800">{{ $review->remark }}</p>@endif
                </div>
                @empty
                <p class="text-sm text-gray-600">{{ __('messages.apps_not_screened') }}</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
