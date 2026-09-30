@extends('layouts.admin')
@section('title', $applicant->full_name ?: __('menus.applicants'))

@section('content')
@php
    $r = __('dashboard.restricted');
    $sens = fn ($value) => $canViewSensitive ? ($value ?: '—') : $r;
    $experience = trim(
        ($applicant->work_experience_years ? trans_choice('public.years_count', (int) $applicant->work_experience_years, ['count' => $applicant->work_experience_years]) : '')
        .($applicant->work_experience_months ? ' '.$applicant->work_experience_months.' '.__('fields.months') : '')
    );
    $sections = [
        __('applicant.personal_info') => [
            __('fields.first_name')        => $applicant->first_name,
            __('fields.middle_name')       => $applicant->middle_name,
            __('fields.last_name')         => $applicant->last_name,
            __('fields.gender')            => $applicant->gender?->label(),
            __('fields.date_of_birth')     => $applicant->date_of_birth ? et_date($applicant->date_of_birth) : null,
            __('fields.nationality')       => $applicant->nationality,
            __('fields.national_id')       => $sens($applicant->national_id),
            __('fields.disability_status') => $applicant->disability_status ? ($applicant->disability_type ?: __('applicant.disability_yes')) : __('applicant.disability_no'),
        ],
        __('applicant.contact_info') => [
            __('fields.email')             => $sens($applicant->email),
            __('fields.phone')             => $sens($applicant->phone),
            __('fields.alternative_phone') => $sens($applicant->alternative_phone),
            __('fields.address')           => $sens($applicant->address),
        ],
        __('applicant.education') => [
            __('fields.education_level')  => $applicant->education_level?->label(),
            __('fields.university_name')  => $applicant->university_name,
            __('fields.field_of_study')   => $applicant->field_of_study,
            __('fields.graduation_year')  => $applicant->graduation_year,
            __('fields.gpa')              => $applicant->gpa,
        ],
        __('applicant.work_experience') => [
            __('fields.experience')         => $experience ?: null,
            __('fields.current_employer')   => $applicant->current_employer,
            __('fields.current_position')   => $applicant->current_position,
            __('fields.experience_summary') => $applicant->work_experience_summary,
        ],
    ];
@endphp
<div class="space-y-6" x-data="{ previewUrl: '', previewName: '', open: false, loading: false }">

    <x-admin.page-header :title="$applicant->full_name ?: '—'"
                         :description="collect([$applicant->applicant_code, __('messages.registered').': '.et_date($applicant->created_at)])->filter()->implode(' · ')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.applicants'), 'url' => route('admin.applicants.index')], ['label' => $applicant->applicant_code ?: __('messages.view')]]">
        <x-admin.avatar :name="$applicant->full_name" size="md" :photo="$applicant->profile_photo_path ? route('admin.applicants.photo', $applicant) : null" />
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            @foreach($sections as $title => $fields)
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ $title }}</h2></div>
                <dl class="dl-grid card-body">
                    @foreach($fields as $label => $value)
                    <div class="{{ $label === __('fields.experience_summary') ? 'sm:col-span-2 lg:col-span-3' : '' }}"><dt>{{ $label }}</dt><dd class="whitespace-pre-line">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>
            @endforeach

            <x-admin.table-card :title="__('menus.documents')" :meta="trans_choice('messages.files_count', $applicant->profileDocuments->count(), ['count' => $applicant->profileDocuments->count()])">
                @if($applicant->profileDocuments->isEmpty())
                    <x-admin.empty :text="__('applicant.no_documents')" />
                @else
                <ul class="divide-y divide-gray-100">
                    @foreach($applicant->profileDocuments as $doc)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $doc->original_name }}</p>
                            <p class="text-xs text-gray-600">{{ number_format($doc->file_size / 1024, 1) }} KB</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" class="btn btn-ghost btn-sm"
                                    @click="previewUrl = @js(route('admin.profile-documents.preview', $doc)); previewName = @js($doc->original_name); loading = true; open = true">{{ __('messages.preview') }}</button>
                            <a href="{{ route('admin.profile-documents.download', $doc) }}" class="btn btn-secondary btn-sm">{{ __('messages.download') }}</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </x-admin.table-card>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">{{ __('menus.applications') }}</h2>
                    <span class="badge badge-blue">{{ $applicant->applications->count() }}</span>
                </div>
                @forelse($applicant->applications as $application)
                <a href="{{ route('admin.applications.show', $application) }}" class="block border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $application->vacancy?->title ?? '—' }}</p>
                    <p class="font-mono text-xs text-gray-600">{{ $application->reference_number }}</p>
                    <div class="mt-1.5 flex items-center justify-between gap-2">
                        <x-admin.status :status="$application->status" />
                        <span class="text-xs text-gray-600">{{ et_date($application->submitted_at ?? $application->created_at) }}</span>
                    </div>
                </a>
                @empty
                <p class="px-5 py-4 text-sm text-gray-600">{{ __('messages.no_records') }}</p>
                @endforelse
            </section>
        </aside>
    </div>

    {{-- Document preview --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false; previewUrl = ''">
            <div class="absolute inset-0 bg-gray-950/60" @click="open = false; previewUrl = ''"></div>
            <div class="relative z-10 flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <span class="truncate text-sm font-semibold text-gray-900" x-text="previewName"></span>
                    <button type="button" @click="open = false; previewUrl = ''" class="btn btn-secondary btn-sm">{{ __('messages.close') }}</button>
                </div>
                <div class="relative min-h-0 flex-1 bg-gray-100">
                    <p x-show="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-600">{{ __('messages.loading') }}</p>
                    <iframe :src="open ? previewUrl : ''" @load="loading = false" class="h-full w-full border-0" title="{{ __('messages.preview') }}"></iframe>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
