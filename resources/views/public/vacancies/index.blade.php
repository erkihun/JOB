@extends('layouts.public')

@section('title', __('vacancies.job_vacancies'))
@section('meta_description', 'Browse all open job vacancies and career opportunities.')

@section('content')
@php
    $advancedKeys = ['location', 'field_of_study', 'opening_date', 'closing_date'];
    $filterLabels = [
        'search'          => __('public.search'),
        'department'      => __('vacancies.department'),
        'employment_type' => __('vacancies.employment_type'),
        'location'        => __('vacancies.location'),
        'field_of_study'  => __('vacancies.field_of_study'),
        'opening_date'    => __('vacancies.opening_date'),
        'closing_date'    => __('vacancies.closing_date'),
    ];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled(request($key)))
        ->map(fn ($label, $key) => [
            'label' => $label,
            'value' => $key === 'employment_type' ? ($employmentTypes[request($key)] ?? request($key)) : request($key),
            'url'   => route('vacancies.index', request()->except([$key, 'page'])),
        ]);
    $inputClass = 'h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20';
    $labelClass = 'mb-1.5 block text-xs font-semibold text-gray-600';
@endphp

<x-public.page-header :title="__('vacancies.job_vacancies')"
                      :subtitle="__('public.vacancies_subtitle')"
                      :crumbs="[['label' => __('vacancies.job_vacancies')]]" />

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

    {{-- ── Filter panel ── --}}
    <div class="relative z-10 -mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-lg shadow-gray-900/5 sm:p-5"
         x-data="{ advanced: {{ collect($advancedKeys)->contains(fn ($k) => filled(request($k))) ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('vacancies.index') }}" role="search">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
                <div class="sm:col-span-2 lg:col-span-5">
                    <label for="search" class="{{ $labelClass }}">{{ __('public.search') }}</label>
                    <div class="relative">
                        <x-public.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('public.hero_search_placeholder') }}"
                               class="{{ $inputClass }} pl-10">
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label for="department" class="{{ $labelClass }}">{{ __('vacancies.department') }}</label>
                    <select id="department" name="department" class="{{ $inputClass }}">
                        <option value="">{{ __('public.all_departments') }}</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="employment_type" class="{{ $labelClass }}">{{ __('vacancies.employment_type') }}</label>
                    <select id="employment_type" name="employment_type" class="{{ $inputClass }}">
                        <option value="">{{ __('public.all_types') }}</option>
                        @foreach($employmentTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('employment_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end sm:col-span-2 lg:col-span-2">
                    <button type="submit"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <x-public.icon name="search" />
                        {{ __('public.search') }}
                    </button>
                </div>
            </div>

            {{-- Advanced filters --}}
            <div x-show="advanced" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 id="advanced-filters"
                 class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="location" class="{{ $labelClass }}">{{ __('vacancies.location') }}</label>
                    <input type="text" id="location" name="location" value="{{ request('location') }}"
                           placeholder="{{ __('public.location_placeholder') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="field_of_study" class="{{ $labelClass }}">{{ __('vacancies.field_of_study') }}</label>
                    <input type="text" id="field_of_study" name="field_of_study" value="{{ request('field_of_study') }}"
                           placeholder="{{ __('public.field_placeholder') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="opening_date" class="{{ $labelClass }}">{{ __('public.opened_after') }}</label>
                    <input type="date" id="opening_date" name="opening_date" value="{{ request('opening_date') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="closing_date" class="{{ $labelClass }}">{{ __('public.closes_before') }}</label>
                    <input type="date" id="closing_date" name="closing_date" value="{{ request('closing_date') }}" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <button type="button" @click="advanced = !advanced"
                        :aria-expanded="advanced.toString()" aria-controls="advanced-filters"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-brand transition hover:bg-brand-muted">
                    <x-public.icon name="filter" class="h-3.5 w-3.5" />
                    <span x-text="advanced ? @js(__('public.hide_filters')) : @js(__('public.more_filters'))">{{ __('public.more_filters') }}</span>
                    <x-public.icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="advanced && 'rotate-180'" />
                </button>
            </div>
        </form>
    </div>

    {{-- ── Results header ── --}}
    <div class="mb-5 mt-8 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600" aria-live="polite">
            <span class="font-bold text-gray-900">{{ number_format($vacancies->total()) }}</span>
            {{ $vacancies->total() === 1 ? __('public.result_singular') : __('public.result_plural') }}
        </p>

        @if($activeFilters->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            @foreach($activeFilters as $filter)
            <a href="{{ $filter['url'] }}"
               class="group inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-brand-muted py-1 pl-3 pr-2 text-xs font-medium text-brand transition hover:border-brand/40"
               title="{{ __('public.remove_filter') }}">
                <span class="text-brand/70">{{ $filter['label'] }}:</span>
                <span class="max-w-40 truncate font-semibold">{{ $filter['value'] }}</span>
                <x-public.icon name="x" class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100" />
            </a>
            @endforeach
            <a href="{{ route('vacancies.index') }}" class="px-1 text-xs font-semibold text-gray-500 underline-offset-2 hover:text-gray-900 hover:underline">
                {{ __('public.clear_all') }}
            </a>
        </div>
        @endif
    </div>

    {{-- ── Results ── --}}
    @if($vacancies->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
            <x-public.icon name="search" class="h-7 w-7" stroke="1.5" />
        </span>
        <h2 class="font-semibold text-gray-900">{{ __('public.no_results_title') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ __('public.no_results') }}</p>
        @if($activeFilters->isNotEmpty())
        <a href="{{ route('vacancies.index') }}"
           class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">
            <x-public.icon name="refresh" />
            {{ __('public.clear_all') }}
        </a>
        @endif
    </div>
    @else
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($vacancies as $vacancy)
            <x-public.vacancy-card :vacancy="$vacancy" heading-tag="h2" class="scroll-animate" data-delay="{{ ($loop->index % 3) + 1 }}" />
        @endforeach
    </div>

    @if($vacancies->hasPages())
    <div class="mt-10">
        {{ $vacancies->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
