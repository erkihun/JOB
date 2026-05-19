@extends('layouts.applicant')

@section('title', __('menus.my_applications'))

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.my_applications') }}</h1>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('vacancies.job_vacancies') }}
        </a>
    </div>

    @if($applications->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 p-14 text-center text-gray-500">
        <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">{{ __('applicant.no_applications_yet') }}</p>
        <a href="{{ route('vacancies.index') }}"
           class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">
            {{ __('applicant.start_applying') }}
        </a>
    </div>
    @else
    <div class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        @foreach($applications as $application)
        @php
            $colorMap = [
                'success' => 'bg-green-100 text-green-800',
                'danger'  => 'bg-red-100 text-red-800',
                'warning' => 'bg-amber-100 text-amber-800',
                'info'    => 'bg-blue-100 text-blue-800',
            ];
            $badge = $colorMap[$application->status->color()] ?? 'bg-gray-100 text-gray-800';
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 hover:bg-gray-50/50 transition">
            <div class="min-w-0">
                <p class="font-medium text-gray-900 text-sm truncate">
                    {{ $application->vacancy->getTranslation('title', app()->getLocale(), false)
                       ?: $application->vacancy->getTranslation('title', 'en', false) }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">
                    {{ $application->reference_number }}
                    @if($application->submitted_at)
                    · {{ __('applicant.submitted_at') }}: {{ $application->submitted_at->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                    {{ $application->status->label() }}
                </span>
                <a href="{{ route('applicant.applications.show', $application) }}"
                   class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ __('applicant.view_application') }}
                </a>
                @if($application->isEditable())
                <a href="{{ route('applicant.applications.edit', $application) }}"
                   class="text-sm text-gray-500 hover:text-gray-800">
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
