@extends('layouts.applicant')

@section('title', $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false))

@section('content')
@php
    $locale      = app()->getLocale();
    $tr          = fn (string $field) => $vacancy->getTranslation($field, $locale, false) ?: $vacancy->getTranslation($field, 'en', false);
    $title       = $tr('title');
    $description = $tr('description');
    $requirements = $tr('qualification_requirements');
    $loc         = $tr('location');
    $isPast      = $vacancy->isPastDeadline();
    $daysLeft    = (int) today()->diffInDays($vacancy->announcement->closing_date, false);
    $isUrgent    = ! $isPast && $daysLeft <= 6;
    $isAm        = $locale === 'am';
    $options     = $vacancy->requirementGroups->filter(fn ($g) => $g->requirements->isNotEmpty())->values();
    $card        = 'rounded-2xl border border-gray-200 bg-white p-5 sm:p-6';
@endphp
<div class="space-y-6 {{ ! $isPast && $canApply && ! $alreadyApplied ? 'pb-20 lg:pb-0' : '' }}">

    <x-applicant.page-header :title="$title"
                             :description="collect([$vacancy->department, $vacancy->institution?->name])->filter()->implode(' · ')"
                             :crumbs="[['label' => __('applicant.nav_dashboard'), 'url' => route('applicant.dashboard')], ['label' => __('vacancies.job_vacancies'), 'url' => route('applicant.vacancies.index')], ['label' => $vacancy->code ?? $title]]" />

    <ul class="flex flex-wrap gap-2 text-sm font-semibold text-gray-700">
        @if($vacancy->code)<li class="rounded-md bg-white px-2.5 py-1 font-mono ring-1 ring-gray-200">{{ $vacancy->code }}</li>@endif
        @if($loc)<li class="rounded-md bg-white px-2.5 py-1 ring-1 ring-gray-200">{{ $loc }}</li>@endif
        @if($vacancy->employment_type)<li class="rounded-md bg-white px-2.5 py-1 ring-1 ring-gray-200">{{ $vacancy->employment_type->label() }}</li>@endif
        @if($vacancy->number_of_positions)<li class="rounded-md bg-white px-2.5 py-1 ring-1 ring-gray-200">{{ trans_choice('public.positions_count', $vacancy->number_of_positions, ['count' => $vacancy->number_of_positions]) }}</li>@endif
        @if($vacancy->salary_grade)<li class="rounded-md bg-white px-2.5 py-1 ring-1 ring-gray-200">{{ $vacancy->salary_grade }}</li>@endif
    </ul>

    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start lg:gap-6">
        <div class="space-y-6">
            @if($description || $requirements)
            <section class="{{ $card }}" aria-labelledby="about-heading">
                <h2 id="about-heading" class="text-lg font-extrabold text-gray-900">{{ __('public.about_the_role') }}</h2>
                @if($description)<div class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-gray-800">{{ $description }}</div>@endif
                @if($requirements)
                <h3 class="mt-6 font-extrabold text-gray-900">{{ __('vacancies.requirements') }}</h3>
                <div class="mt-2 whitespace-pre-line text-[15px] leading-relaxed text-gray-800">{{ $requirements }}</div>
                @endif
            </section>
            @endif

            @if($options->isNotEmpty())
            <section class="{{ $card }}" aria-labelledby="elig-heading">
                <h2 id="elig-heading" class="text-lg font-extrabold text-gray-900">{{ __('vacancies.eligibility_requirements') }}</h2>
                @if($options->count() > 1)<p class="mt-1 text-sm text-gray-600">{{ __('vacancies.eligibility_any_option_hint') }}</p>@endif
                <ol class="mt-4 grid gap-3 {{ $options->count() > 1 ? 'md:grid-cols-2' : '' }}">
                    @foreach($options as $group)
                    <li class="rounded-xl border p-4 {{ $loop->first ? 'border-brand/30 bg-brand-muted/50' : 'border-gray-200 bg-gray-50' }}">
                        <p class="text-sm font-extrabold uppercase tracking-wide text-brand-dark">{{ \App\Services\Eligibility\VacancyEligibilityChecker::optionLabel($loop->iteration, $group->title) }}</p>
                        <ul class="mt-2 space-y-1.5 text-[15px] text-gray-800">
                            @foreach($group->requirements as $req)
                            <li class="flex gap-2"><span class="text-brand" aria-hidden="true">✓</span><span>{{ $req->summary() }}</span></li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach
                </ol>
            </section>
            @endif

            @if($vacancy->requiredDocuments->isNotEmpty())
            <section class="{{ $card }}" aria-labelledby="docs-heading">
                <h2 id="docs-heading" class="text-lg font-extrabold text-gray-900">{{ __('vacancies.required_documents') }}</h2>
                <ul class="mt-4 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                    @foreach($vacancy->requiredDocuments as $doc)
                    <li class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-semibold text-gray-900">{{ $doc->document_name }}</span>
                        <span class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $doc->is_required ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600' }}">{{ $doc->is_required ? __('public.required') : __('public.optional') }}</span>
                            @if($doc->allowed_types){{ implode(', ', array_map('strtoupper', $doc->allowed_types)) }} · @endif{{ __('vacancies.detail_max') }} {{ $doc->max_size_mb }} MB
                        </span>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>

        <aside class="mt-6 lg:sticky lg:top-24 lg:mt-0">
            @include('applicant.vacancies._apply_card', ['isPast' => $isPast, 'isUrgent' => $isUrgent, 'daysLeft' => $daysLeft, 'isAm' => $isAm])
        </aside>
    </div>
</div>

{{-- Mobile sticky apply bar --}}
@if(! $isPast && $canApply && ! $alreadyApplied)
<div class="fixed inset-x-0 bottom-16 z-40 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-8px_24px_rgba(0,0,0,0.06)] lg:hidden">
    <div class="flex items-center gap-3">
        <p class="min-w-0 flex-1 text-sm font-bold {{ $isUrgent ? 'text-accent-dark' : 'text-gray-700' }}">
            {{ $daysLeft === 0 ? __('public.closes_today') : trans_choice('public.closes_in_days_count', $daysLeft, ['count' => $daysLeft]) }}
        </p>
        <a href="{{ route('applicant.applications.create', $vacancy) }}" class="inline-flex h-11 items-center rounded-xl bg-accent-dark px-5 text-sm font-extrabold text-white">{{ __('vacancies.apply_now') }}</a>
    </div>
</div>
@endif
@endsection
