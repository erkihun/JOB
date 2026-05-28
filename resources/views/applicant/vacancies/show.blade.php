@extends('layouts.applicant')
@php use Illuminate\Support\Str; @endphp

@section('title', $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false))

@section('content')
@php
    $isPast      = $vacancy->closing_date->isPast();
    $daysLeft    = (int) now()->diffInDays($vacancy->closing_date, false);
    $isUrgent    = !$isPast && $daysLeft <= 6;
    $loc         = $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false);
    $isAm        = app()->getLocale() === 'am';
@endphp

<div class="space-y-0">

    {{-- Back link --}}
    <a href="{{ route('applicant.vacancies.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition mb-5">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $isAm ? 'ወደ ዝርዝር ተመለስ' : 'Back to vacancies' }}
    </a>

    <div class="lg:grid lg:grid-cols-3 lg:gap-6">

        {{-- ── Main column ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Hero card --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-blue-700 via-blue-600 to-indigo-700 p-6 sm:p-8 text-white shadow-lg">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.12),transparent_60%)]"></div>
                <div class="relative">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            @if($vacancy->code)
                            <span class="inline-block font-mono text-xs font-medium text-blue-200 bg-white/10 border border-white/20 rounded-lg px-2.5 py-1 mb-3">
                                {{ $vacancy->code }}
                            </span>
                            @endif
                            <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight text-white">
                                {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                            </h1>
                            @if($vacancy->department)
                            <p class="mt-2 text-blue-200 text-sm font-medium">{{ $vacancy->department }}</p>
                            @endif
                        </div>

                        {{-- Deadline badge --}}
                        @if($isPast)
                        <span class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-red-500 px-3 py-2 text-xs font-bold text-white shadow">
                            {{ __('vacancies.deadline_passed') }}
                        </span>
                        @elseif($isUrgent)
                        <span class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-orange-500 px-3 py-2 text-xs font-bold text-white shadow">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
                        </span>
                        @endif
                    </div>

                    {{-- Metadata chips --}}
                    <div class="mt-5 flex flex-wrap gap-2">
                        @if($vacancy->employment_type)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 border border-white/20 px-3 py-1.5 text-xs font-semibold text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $vacancy->employment_type->label() }}
                        </span>
                        @endif
                        @if($loc)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 border border-white/20 px-3 py-1.5 text-xs font-semibold text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $loc }}
                        </span>
                        @endif
                        @if($vacancy->number_of_positions)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 border border-white/20 px-3 py-1.5 text-xs font-semibold text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $vacancy->number_of_positions }} {{ __('public.positions') }}
                        </span>
                        @endif
                        @if($vacancy->salary_grade)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 border border-white/20 px-3 py-1.5 text-xs font-semibold text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $vacancy->salary_grade }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Key info grid --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-3 lg:grid-cols-4">
                    @if($vacancy->institution)
                    <div class="px-4 py-4">
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('vacancies.recruiting_institution') }}</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $vacancy->institution->name }}</dd>
                    </div>
                    @endif
                    @if($vacancy->field_of_study)
                    <div class="px-4 py-4">
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('vacancies.field_of_study') }}</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $vacancy->field_of_study }}</dd>
                    </div>
                    @endif
                    @if($vacancy->minimum_experience !== null)
                    <div class="px-4 py-4">
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('vacancies.min_experience') }}</dt>
                        <dd class="text-sm font-semibold text-gray-900">
                            {{ $vacancy->minimum_experience }}
                            {{ $isAm ? __('public.years') : ($vacancy->minimum_experience === 1 ? 'year' : 'years') }}
                        </dd>
                    </div>
                    @endif
                    <div class="px-4 py-4">
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('vacancies.opening_date') }}</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ et_date($vacancy->opening_date, 'M d, Y') }}</dd>
                    </div>
                    <div class="px-4 py-4">
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('vacancies.closing_date') }}</dt>
                        <dd class="text-sm font-semibold {{ $isPast ? 'text-red-600' : 'text-gray-900' }}">
                            {{ et_date($vacancy->closing_date, 'M d, Y') }}
                            @if(!$isPast)
                            <span class="block text-xs font-normal text-gray-400 mt-0.5">({{ et_diff_for_humans($vacancy->closing_date) }})</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            @if($vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false))
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">{{ __('vacancies.description') }}</h2>
                </div>
                <div class="px-6 py-5 text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                    {{ $vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false) }}
                </div>
            </section>
            @endif

            {{-- Requirements --}}
            @if($vacancy->getTranslation('qualification_requirements', app()->getLocale(), false) ?: $vacancy->getTranslation('qualification_requirements', 'en', false))
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">{{ __('vacancies.requirements') }}</h2>
                </div>
                <div class="px-6 py-5 text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                    {{ $vacancy->getTranslation('qualification_requirements', app()->getLocale(), false) ?: $vacancy->getTranslation('qualification_requirements', 'en', false) }}
                </div>
            </section>
            @endif

            {{-- Required documents --}}
            @if($vacancy->requiredDocuments->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                        <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">{{ __('vacancies.required_documents') }}</h2>
                </div>
                <ul class="divide-y divide-gray-50 px-6 py-3">
                    @foreach($vacancy->requiredDocuments as $doc)
                    <li class="flex items-start gap-4 py-3.5">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $doc->is_required ? 'bg-blue-100' : 'bg-gray-100' }}">
                            <svg class="h-3.5 w-3.5 {{ $doc->is_required ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $doc->document_name }}</p>
                            <p class="mt-0.5 flex flex-wrap gap-2 text-xs text-gray-500">
                                @if($doc->is_required)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">{{ __('public.required') }}</span>
                                @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ __('public.optional') }}</span>
                                @endif
                                @if($doc->allowed_types)
                                <span>{{ implode(', ', array_map('strtoupper', $doc->allowed_types)) }}</span>
                                @endif
                                <span>{{ __('vacancies.detail_max') }} {{ $doc->max_size_mb }} MB</span>
                            </p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Mobile-only bottom apply CTA (above lg breakpoint sidebar takes over) --}}
            <div class="lg:hidden">
                @include('applicant.vacancies._apply_card', ['isPast' => $isPast, 'isUrgent' => $isUrgent, 'daysLeft' => $daysLeft, 'isAm' => $isAm])
            </div>

        </div>

        {{-- ── Sticky sidebar ── --}}
        <div class="hidden lg:block mt-0">
            <div class="lg:sticky lg:top-6 space-y-4">
                @include('applicant.vacancies._apply_card', ['isPast' => $isPast, 'isUrgent' => $isUrgent, 'daysLeft' => $daysLeft, 'isAm' => $isAm])
            </div>
        </div>

    </div><!-- /grid -->
</div>
@endsection
