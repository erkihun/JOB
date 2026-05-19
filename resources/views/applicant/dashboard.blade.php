@extends('layouts.applicant')

@section('title', __('menus.dashboard'))

@section('content')
<div class="space-y-6">

    {{-- Welcome --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ __('applicant.welcome', ['name' => $applicant?->full_name ?? auth()->user()->name]) }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('applicant.what_today') }}</p>
    </div>

    {{-- ── Profile Completion Widget ──────────────────────────────────────── --}}
    @if($applicant && $completionPct < 100)
    <div class="rounded-xl border border-amber-200 bg-amber-50 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-5 w-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-amber-800">{{ __('applicant.profile_incomplete') }}</p>
                </div>

                {{-- Progress bar --}}
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-amber-700 mb-1">
                        <span>{{ __('applicant.profile_completion') }}</span>
                        <span class="font-semibold">{{ $completionPct }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-amber-200">
                        <div class="h-2 rounded-full bg-amber-500 transition-all"
                             style="width: {{ $completionPct }}%"></div>
                    </div>
                </div>

                @if($completionMissing)
                <p class="text-xs text-amber-700 font-medium mb-1">{{ __('applicant.missing_fields') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($completionMissing as $field)
                    <span class="inline-block rounded-full border border-amber-300 bg-white px-2.5 py-0.5 text-xs text-amber-700">
                        {{ $field }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
            <a href="{{ route('applicant.profile.edit') }}"
               class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition">
                {{ __('applicant.complete_profile') }}
            </a>
        </div>
    </div>
    @elseif($applicant && $completionPct === 100)
    <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 flex items-center gap-2">
        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-sm font-medium text-green-800">{{ __('applicant.profile_complete') }}</p>
    </div>
    @endif

    {{-- Stats --}}
    @if($applicant && $applications->isNotEmpty())
    @php
        $total    = $applications->count();
        $active   = $applications->filter(fn ($a) => in_array($a->status, [
                        \App\Enums\ApplicationStatus::Submitted,
                        \App\Enums\ApplicationStatus::UnderReview,
                        \App\Enums\ApplicationStatus::CorrectionRequired]))->count();
        $positive = $applications->filter(fn ($a) => in_array($a->status, [
                        \App\Enums\ApplicationStatus::PassedScreening,
                        \App\Enums\ApplicationStatus::ShortlistedExam,
                        \App\Enums\ApplicationStatus::ShortlistedInterview,
                        \App\Enums\ApplicationStatus::Selected,
                        \App\Enums\ApplicationStatus::ExamCompleted,
                        \App\Enums\ApplicationStatus::InterviewCompleted,
                        \App\Enums\ApplicationStatus::Waitlisted]))->count();
        $rejected = $applications->filter(fn ($a) => in_array($a->status, [
                        \App\Enums\ApplicationStatus::FailedScreening,
                        \App\Enums\ApplicationStatus::NotSelected,
                        \App\Enums\ApplicationStatus::Withdrawn]))->count();
    @endphp
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border bg-white p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-gray-900">{{ $total }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">{{ __('applicant.total_applications') }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-blue-600">{{ $active }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">{{ __('applicant.active_applications') }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-green-600">{{ $positive }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">{{ __('applicant.passed_applications') }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-red-500">{{ $rejected }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">{{ __('applicant.rejected_applications') }}</p>
        </div>
    </div>
    @endif

    {{-- Quick actions --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ __('applicant.quick_actions') }}</h2>
        <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap sm:gap-3">
            <a href="{{ route('applicant.vacancies.index') }}"
               class="flex flex-col sm:flex-row items-center justify-center sm:justify-start gap-1 sm:gap-2 rounded-xl bg-blue-600 px-2 py-3 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-medium text-white hover:bg-blue-700 transition shadow-sm text-center">
                <svg class="h-5 w-5 sm:h-4 sm:w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="leading-tight">{{ __('applicant.browse_jobs') }}</span>
            </a>
            <a href="{{ route('applicant.applications.index') }}"
               class="flex flex-col sm:flex-row items-center justify-center sm:justify-start gap-1 sm:gap-2 rounded-xl border border-gray-300 bg-white px-2 py-3 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition text-center">
                <svg class="h-5 w-5 sm:h-4 sm:w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="leading-tight">{{ __('menus.my_applications') }}</span>
            </a>
            <a href="{{ route('applicant.profile.edit') }}"
               class="flex flex-col sm:flex-row items-center justify-center sm:justify-start gap-1 sm:gap-2 rounded-xl border border-gray-300 bg-white px-2 py-3 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition text-center">
                <svg class="h-5 w-5 sm:h-4 sm:w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="leading-tight">{{ __('menus.profile') }}</span>
            </a>
        </div>
    </div>

    {{-- Recent applications --}}
    <div>
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('applicant.recent_applications') }}</h2>

        @if($applications->isNotEmpty())
        <div class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            @foreach($applications->take(5) as $application)
            <a href="{{ route('applicant.applications.show', $application) }}"
               class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 text-sm truncate">
                        {{ $application->vacancy->getTranslation('title', app()->getLocale(), false)
                           ?: $application->vacancy->getTranslation('title', 'en', false) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $application->reference_number }}</p>
                </div>
                @php
                    $color = match($application->status->getColor()) {
                        'success' => 'bg-green-100 text-green-800',
                        'danger'  => 'bg-red-100 text-red-800',
                        'warning' => 'bg-amber-100 text-amber-800',
                        default   => 'bg-blue-100 text-blue-800',
                    };
                @endphp
                <span class="ml-4 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">
                    {{ $application->status->label() }}
                </span>
            </a>
            @endforeach
        </div>
        @if($applications->count() > 5)
        <div class="mt-3 text-right">
            <a href="{{ route('applicant.applications.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                {{ __('public.view_all') }}
            </a>
        </div>
        @endif
        @else
        <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
            <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">{{ __('applicant.no_applications_yet') }}</p>
            <a href="{{ route('vacancies.index') }}"
               class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">
                {{ __('vacancies.job_vacancies') }} →
            </a>
        </div>
        @endif
    </div>

</div>
@endsection
