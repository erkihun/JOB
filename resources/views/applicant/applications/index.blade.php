@extends('layouts.applicant')

@section('title', __('menus.my_applications'))

@section('content')
@php
    $locale = app()->getLocale();
    $tone = [
        'success' => 'bg-green-50 text-green-800',
        'danger'  => 'bg-red-50 text-red-800',
        'warning' => 'bg-accent-muted text-accent-dark',
        'info'    => 'bg-brand-muted text-brand-dark',
    ];
@endphp
<div class="space-y-6">

    <x-applicant.page-header :title="__('applicant.my_applications')"
                             :description="$applications->total() > 0 ? __('applicant.applications_count', ['count' => $applications->total()]) : __('applicant.applications_intro')">
        <a href="{{ route('applicant.vacancies.index') }}" class="inline-flex h-11 items-center rounded-xl bg-accent-dark px-5 text-[15px] font-extrabold text-white transition hover:bg-accent">{{ __('public.find_jobs') }}</a>
    </x-applicant.page-header>

    @if($applications->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500" aria-hidden="true">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </span>
        <h2 class="text-base font-bold text-gray-900">{{ __('applicant.no_applications_yet') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('applicant.start_applying') }}</p>
        <a href="{{ route('applicant.vacancies.index') }}" class="mt-5 inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark">{{ __('applicant.browse_jobs') }}</a>
    </div>
    @else
    <ul class="space-y-4">
        @foreach($applications as $application)
        @php $vacancyTitle = $application->vacancy->getTranslation('title', $locale, false) ?: $application->vacancy->getTranslation('title', 'en', false); @endphp
        <li class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand/40 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold text-gray-900">
                        <a href="{{ route('applicant.applications.show', $application) }}" class="hover:text-brand hover:underline">{{ $vacancyTitle }}</a>
                    </h2>
                    <p class="mt-0.5 text-[13px] text-gray-600">
                        <span class="font-mono">{{ $application->reference_number }}</span>
                        @if($application->submitted_at) · {{ __('applicant.applied_on', ['date' => et_date($application->submitted_at, 'M d, Y')]) }}@endif
                    </p>
                </div>
                <span class="self-start rounded-full px-3 py-1 text-xs font-bold {{ $tone[\App\Support\ApplicationProgress::tone($application->status)] }}">{{ $application->status->label() }}</span>
            </div>

            <x-applicant.tracker :application="$application" class="mt-4" />

            <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-700">{{ \App\Support\ApplicationProgress::nextText($application) }}</p>
                <div class="flex shrink-0 gap-2">
                    @if($application->isEditable())
                    <a href="{{ route('applicant.applications.edit', $application) }}" class="inline-flex h-10 items-center rounded-xl border border-gray-300 px-4 text-sm font-bold text-gray-900 hover:bg-gray-50">{{ __('applicant.edit_application') }}</a>
                    @endif
                    <a href="{{ route('applicant.applications.show', $application) }}" class="inline-flex h-10 items-center rounded-xl bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark">{{ __('applicant.view_application') }}</a>
                </div>
            </div>
        </li>
        @endforeach
    </ul>

    @if($applications->hasPages())
    <div>{{ $applications->links() }}</div>
    @endif
    @endif
</div>
@endsection
