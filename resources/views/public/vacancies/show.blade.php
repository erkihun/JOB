@extends('layouts.public')

@section('title', $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false))
@section('meta_description', strip_tags(Str::limit($vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false), 160)))

@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="{{ route('home') }}" class="hover:text-blue-600">{{ app()->getLocale() === 'am' ? 'መነሻ' : 'Home' }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('vacancies.index') }}" class="hover:text-blue-600">{{ __('vacancies.job_vacancies') }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 truncate">
            {{ Str::limit($vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false), 40) }}
        </span>
    </nav>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="border-b border-gray-100 bg-gray-50 px-6 py-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    @if($vacancy->code)
                    <p class="text-xs font-mono text-gray-400 mb-1">{{ $vacancy->code }}</p>
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                    </h1>
                    @if($vacancy->department)
                    <p class="mt-1 text-sm text-gray-500">{{ $vacancy->department }}</p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if($alreadyApplied)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-4 py-2 text-sm font-medium text-green-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('vacancies.already_applied') }}
                        </span>
                    @elseif($canApply)
                        @auth
                            @if(auth()->user()->hasRole('applicant'))
                                <a href="{{ route('applicant.applications.create', $vacancy) }}"
                                   class="inline-block rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                    {{ __('vacancies.apply_now') }}
                                </a>
                            @endif
                        @else
                            <a href="{{ route('applicant.login') }}?redirect={{ urlencode(request()->url()) }}"
                               class="inline-block rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                {{ __('vacancies.apply_now') }}
                            </a>
                        @endauth
                    @elseif($vacancy->isPastDeadline())
                        <span class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 text-sm font-medium text-red-800">
                            {{ __('vacancies.deadline_passed') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700">
                            {{ __('vacancies.vacancy_not_open') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Key Info Grid --}}
        <div class="grid grid-cols-2 gap-4 border-b border-gray-100 px-6 py-5 sm:grid-cols-3 lg:grid-cols-4">
            @if($vacancy->employment_type)
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.employment_type') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $vacancy->employment_type->label() }}</dd>
            </div>
            @endif

            @if($vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false))
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.location') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false) }}
                </dd>
            </div>
            @endif

            @if($vacancy->number_of_positions)
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.number_of_positions') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $vacancy->number_of_positions }}</dd>
            </div>
            @endif

            @if($vacancy->salary_grade)
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.salary_grade') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $vacancy->salary_grade }}</dd>
            </div>
            @endif

            @if($vacancy->field_of_study)
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.field_of_study') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $vacancy->field_of_study }}</dd>
            </div>
            @endif

            @if($vacancy->minimum_experience !== null)
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.min_experience') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $vacancy->minimum_experience }}
                    {{ app()->getLocale() === 'am' ? 'ዓመት' : ($vacancy->minimum_experience === 1 ? 'year' : 'years') }}
                </dd>
            </div>
            @endif

            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.opening_date') }}</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $vacancy->opening_date->format('M d, Y') }}</dd>
            </div>

            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('vacancies.closing_date') }}</dt>
                <dd class="mt-1 text-sm font-semibold {{ $vacancy->closing_date->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $vacancy->closing_date->format('M d, Y') }}
                    @if(!$vacancy->closing_date->isPast())
                    <span class="text-xs font-normal text-gray-400 ml-1">
                        ({{ $vacancy->closing_date->diffForHumans() }})
                    </span>
                    @endif
                </dd>
            </div>
        </div>

        {{-- Body --}}
        <div class="divide-y divide-gray-100 px-6 py-6 space-y-6">

            {{-- Description --}}
            @if($vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false))
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('vacancies.description') }}</h2>
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! nl2br(e($vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false))) !!}
                </div>
            </section>
            @endif

            {{-- Requirements --}}
            @if($vacancy->getTranslation('qualification_requirements', app()->getLocale(), false) ?: $vacancy->getTranslation('qualification_requirements', 'en', false))
            <section class="pt-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('vacancies.requirements') }}</h2>
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! nl2br(e($vacancy->getTranslation('qualification_requirements', app()->getLocale(), false) ?: $vacancy->getTranslation('qualification_requirements', 'en', false))) !!}
                </div>
            </section>
            @endif

            {{-- Required Documents --}}
            @if($vacancy->requiredDocuments->isNotEmpty())
            <section class="pt-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('vacancies.required_documents') }}</h2>
                <ul class="space-y-3">
                    @foreach($vacancy->requiredDocuments as $doc)
                    <li class="flex items-start gap-3 rounded-lg bg-gray-50 border border-gray-200 px-4 py-3">
                        <svg class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $doc->document_name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if($doc->is_required)
                                <span class="text-red-600 font-medium">{{ app()->getLocale() === 'am' ? 'ግዴታ' : 'Required' }}</span>
                                @else
                                <span>{{ app()->getLocale() === 'am' ? 'አማራጭ' : 'Optional' }}</span>
                                @endif
                                @if($doc->allowed_types)
                                · {{ implode(', ', array_map('strtoupper', $doc->allowed_types)) }}
                                @endif
                                · {{ app()->getLocale() === 'am' ? 'ከፍተኛ' : 'Max' }} {{ $doc->max_size_mb }} MB
                            </p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

        </div>

        {{-- Footer CTA --}}
        @if($canApply && !$alreadyApplied)
        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end">
            @auth
                @if(auth()->user()->hasRole('applicant'))
                    <a href="{{ route('applicant.applications.create', $vacancy) }}"
                       class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                        {{ __('vacancies.apply_now') }}
                    </a>
                @endif
            @else
                <a href="{{ route('applicant.login') }}?redirect={{ urlencode(request()->url()) }}"
                   class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                    {{ __('vacancies.apply_now') }}
                </a>
            @endauth
        </div>
        @endif
    </div>

    {{-- Back link --}}
    <div class="mt-6">
        <a href="{{ route('vacancies.index') }}" class="text-sm text-gray-500 hover:text-blue-600">
            ← {{ app()->getLocale() === 'am' ? 'ወደ ዝርዝር ተመለስ' : 'Back to vacancies' }}
        </a>
    </div>

</div>
@endsection
