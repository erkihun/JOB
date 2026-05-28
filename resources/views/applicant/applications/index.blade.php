@extends('layouts.applicant')

@section('title', __('menus.my_applications'))

@section('content')
<div class="space-y-5">

    {{-- Page header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.my_applications') }}</h1>
            @if($applications->total() > 0)
            <p class="mt-0.5 text-sm text-gray-500">
                {{ __('applicant.applications_count', ['count' => $applications->total()]) }}
            </p>
            @endif
        </div>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition shadow-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            {{ __('vacancies.job_vacancies') }}
        </a>
    </div>

    {{-- Empty state --}}
    @if($applications->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-14 text-center shadow-sm">
        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 mb-2">{{ __('applicant.no_applications_yet') }}</h3>
        <p class="text-sm text-gray-500 mb-5">{{ __('applicant.start_applying') }}</p>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition shadow-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            {{ __('applicant.browse_jobs') }}
        </a>
    </div>

    @else
    {{-- Application cards --}}
    <div class="space-y-3">
        @foreach($applications as $application)
        @php
            $statusColor = $application->status->color();
            $borderMap = [
                'success' => 'border-l-green-500',
                'danger'  => 'border-l-red-500',
                'warning' => 'border-l-amber-500',
                'info'    => 'border-l-blue-500',
            ];
            $badgeMap = [
                'success' => 'bg-green-100 text-green-800 border-green-200',
                'danger'  => 'bg-red-100 text-red-800 border-red-200',
                'warning' => 'bg-amber-100 text-amber-800 border-amber-200',
                'info'    => 'bg-blue-100 text-blue-800 border-blue-200',
            ];
            $borderLeft = $borderMap[$statusColor] ?? 'border-l-gray-300';
            $badge      = $badgeMap[$statusColor] ?? 'bg-gray-100 text-gray-800 border-gray-200';
        @endphp
        <div class="group flex flex-col sm:flex-row sm:items-center rounded-2xl border border-gray-200 border-l-4 {{ $borderLeft }} bg-white shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

            {{-- Left: vacancy info --}}
            <div class="flex-1 min-w-0 px-5 py-4">
                <p class="font-bold text-gray-900 text-sm sm:text-base leading-snug group-hover:text-blue-700 transition truncate">
                    {{ $application->vacancy->getTranslation('title', app()->getLocale(), false)
                       ?: $application->vacancy->getTranslation('title', 'en', false) }}
                </p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                    <span class="font-mono text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded px-1.5 py-0.5">
                        {{ $application->reference_number }}
                    </span>
                    @if($application->submitted_at)
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('applicant.submitted_at') }}: {{ et_date($application->submitted_at, 'M d, Y') }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Right: status badge + actions --}}
            <div class="flex items-center gap-3 px-5 pb-4 sm:py-4 sm:border-l sm:border-gray-100 shrink-0">
                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badge }}">
                    {{ $application->status->label() }}
                </span>
                <a href="{{ route('applicant.applications.show', $application) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ __('applicant.view_application') }}
                </a>
                @if($application->isEditable())
                <a href="{{ route('applicant.applications.edit', $application) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    {{ __('applicant.edit_application') }}
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($applications->hasPages())
    <div class="mt-4">
        {{ $applications->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
