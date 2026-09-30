@extends('layouts.public')

@section('title', __('vacancies.job_vacancies'))
@section('meta_description', 'Browse all open job vacancies and career opportunities.')

@section('content')
@php
    $selectedDepartments = $departmentFilter ?? [];
    $filterLabels = [
        'search'          => __('public.search'),
        'location'        => __('vacancies.location'),
        'employment_type' => __('vacancies.employment_type'),
        'field_of_study'  => __('vacancies.field_of_study'),
        'closing_within'  => __('public.closing_date_filter'),
        'opening_date'    => __('public.opened_after'),
        'closing_date'    => __('public.closes_before'),
    ];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled(request($key)))
        ->map(fn ($label, $key) => [
            'label' => $label,
            'value' => match ($key) {
                'employment_type' => $employmentTypes[request($key)] ?? request($key),
                'closing_within'  => __('public.within_days', ['days' => (int) request($key)]),
                default           => request($key),
            },
            'url' => route('vacancies.index', request()->except([$key, 'page'])),
        ])->values();
    foreach ($selectedDepartments as $dept) {
        $activeFilters->push([
            'label' => __('vacancies.department'),
            'value' => $dept,
            'url'   => route('vacancies.index', array_merge(request()->except(['department', 'page']), ['department' => array_values(array_diff($selectedDepartments, [$dept]))])),
        ]);
    }
    $inputClass = 'h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-base text-gray-900 transition placeholder:text-gray-500 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20';
    $legendClass = 'mb-3 text-[15px] font-extrabold text-gray-900';
    $optionClass = 'flex cursor-pointer items-center gap-3 rounded-lg py-1.5 text-[15px] text-gray-700 hover:text-gray-900';
    $boxClass = 'h-[18px] w-[18px] shrink-0 border-gray-400 text-brand focus:ring-brand';
    $sort = request('sort', 'newest');
@endphp

<x-public.page-header :title="__('vacancies.job_vacancies')"
                      :crumbs="[['label' => __('vacancies.job_vacancies')]]">
    <p class="mt-2 text-base text-gray-600">
        {{ trans_choice('public.vacancies_count', $totals['vacancies'], ['count' => number_format($totals['vacancies'])]) }}
        · {{ trans_choice('public.positions_count', $totals['positions'], ['count' => number_format($totals['positions'])]) }}
    </p>

    {{-- What / where search --}}
    <form method="GET" action="{{ route('vacancies.index') }}" role="search"
          class="mt-6 grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)_auto] sm:items-end">
        @foreach(request()->except(['search', 'location', 'page']) as $key => $value)
            @foreach((array) $value as $v)
                <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">
            @endforeach
        @endforeach
        <div>
            <label for="search" class="mb-1.5 block text-sm font-bold text-gray-700">{{ __('public.what') }}</label>
            <div class="relative">
                <x-public.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" />
                <input type="search" id="search" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('public.hero_search_placeholder') }}" class="{{ $inputClass }} pl-12">
            </div>
        </div>
        <div>
            <label for="location" class="mb-1.5 block text-sm font-bold text-gray-700">{{ __('public.where') }}</label>
            <div class="relative">
                <x-public.icon name="map-pin" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" />
                <input type="text" id="location" name="location" value="{{ request('location') }}"
                       placeholder="{{ __('public.location_placeholder') }}" class="{{ $inputClass }} pl-12">
            </div>
        </div>
        <button type="submit"
                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-accent-dark px-8 text-base font-extrabold text-white transition hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
            {{ __('public.search') }}
        </button>
    </form>
</x-public.page-header>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ filtersOpen: false }">
    <div class="lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:items-start lg:gap-8">

        {{-- ── Filters (sidebar on desktop, slide-over on mobile) ── --}}
        <div x-show="filtersOpen" x-cloak @click="filtersOpen = false" class="fixed inset-0 z-50 bg-gray-950/50 lg:hidden"></div>
        <aside id="vacancy-filters" aria-label="{{ __('public.filters') }}"
               class="fixed inset-y-0 left-0 z-50 w-80 max-w-[88vw] -translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition-transform lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:rounded-2xl lg:border lg:border-gray-200 lg:shadow-none"
               :class="filtersOpen && 'translate-x-0'">
            <form method="GET" action="{{ route('vacancies.index') }}" x-ref="filters">
                @foreach(['search', 'location', 'sort'] as $keep)
                    @if(filled(request($keep)))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                @endforeach

                <div class="-mt-1 mb-2 flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-lg font-extrabold text-gray-900">{{ __('public.filters') }}</h2>
                    <div class="flex items-center gap-2">
                        @if($activeFilters->isNotEmpty())
                        <a href="{{ route('vacancies.index') }}" class="text-sm font-bold text-brand hover:underline">{{ __('public.clear_all') }}</a>
                        @endif
                        <button type="button" @click="filtersOpen = false" class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 lg:hidden">
                            <x-public.icon name="x" class="h-5 w-5" />
                            <span class="sr-only">{{ __('public.close') }}</span>
                        </button>
                    </div>
                </div>

                <fieldset class="border-b border-gray-100 py-4">
                    <legend class="{{ $legendClass }}">{{ __('vacancies.employment_type') }}</legend>
                    <label class="{{ $optionClass }}">
                        <input type="radio" name="employment_type" value="" class="{{ $boxClass }}" @checked(blank(request('employment_type'))) @change="$refs.filters.requestSubmit()">
                        <span class="flex-1">{{ __('public.all_types') }}</span>
                    </label>
                    @foreach($employmentTypes as $value => $label)
                        @php $count = (int) ($typeCounts[$value] ?? 0); @endphp
                        @continue($count === 0 && request('employment_type') !== $value)
                        <label class="{{ $optionClass }}">
                            <input type="radio" name="employment_type" value="{{ $value }}" class="{{ $boxClass }}" @checked(request('employment_type') === $value) @change="$refs.filters.requestSubmit()">
                            <span class="flex-1">{{ $label }}</span>
                            <span class="text-sm text-gray-500">{{ $count }}</span>
                        </label>
                    @endforeach
                </fieldset>

                @if($departments->isNotEmpty())
                <fieldset class="border-b border-gray-100 py-4">
                    <legend class="{{ $legendClass }}">{{ __('vacancies.department') }}</legend>
                    <div class="max-h-72 space-y-0.5 overflow-y-auto pr-1">
                        @foreach($departments as $dept => $count)
                        <label class="{{ $optionClass }}">
                            <input type="checkbox" name="department[]" value="{{ $dept }}" class="{{ $boxClass }} rounded" @checked(in_array($dept, $selectedDepartments, true)) @change="$refs.filters.requestSubmit()">
                            <span class="flex-1">{{ $dept }}</span>
                            <span class="text-sm text-gray-500">{{ $count }}</span>
                        </label>
                        @endforeach
                    </div>
                </fieldset>
                @endif

                <fieldset class="border-b border-gray-100 py-4">
                    <legend class="{{ $legendClass }}">{{ __('public.closing_date_filter') }}</legend>
                    @foreach(['' => __('public.any_time'), '7' => __('public.within_days', ['days' => 7]), '30' => __('public.within_days', ['days' => 30])] as $value => $label)
                    <label class="{{ $optionClass }}">
                        <input type="radio" name="closing_within" value="{{ $value }}" class="{{ $boxClass }}" @checked((string) request('closing_within', '') === (string) $value) @change="$refs.filters.requestSubmit()">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </fieldset>

                <div class="py-4">
                    <label for="field_of_study" class="{{ $legendClass }} block">{{ __('vacancies.field_of_study') }}</label>
                    <input type="text" id="field_of_study" name="field_of_study" value="{{ request('field_of_study') }}"
                           placeholder="{{ __('public.field_placeholder') }}" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-[15px] focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand text-[15px] font-bold text-white transition hover:bg-brand-dark">
                    {{ __('public.apply_filters') }}
                </button>
            </form>
        </aside>

        {{-- ── Results ── --}}
        <div class="min-w-0">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="mr-1 text-base text-gray-700" aria-live="polite">
                        <strong class="text-gray-900">{{ trans_choice('public.vacancies_count', $vacancies->total(), ['count' => number_format($vacancies->total())]) }}</strong>
                        {{ $activeFilters->isNotEmpty() ? __('public.match_your_search') : '' }}
                    </p>
                    @foreach($activeFilters as $filter)
                    <a href="{{ $filter['url'] }}" title="{{ __('public.remove_filter') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-brand-muted py-1 pl-3 pr-2 text-sm font-bold text-brand-dark transition hover:bg-brand/15">
                        <span class="sr-only">{{ $filter['label'] }}:</span>
                        <span class="max-w-48 truncate">{{ $filter['value'] }}</span>
                        <x-public.icon name="x" class="h-3.5 w-3.5" />
                    </a>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="filtersOpen = true" aria-controls="vacancy-filters"
                            class="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-[15px] font-bold text-gray-900 lg:hidden">
                        <x-public.icon name="filter" class="h-4 w-4" />
                        {{ __('public.filters') }}
                        @if($activeFilters->isNotEmpty())<span class="rounded-full bg-brand px-1.5 text-xs text-white">{{ $activeFilters->count() }}</span>@endif
                    </button>
                    <form method="GET" action="{{ route('vacancies.index') }}" class="flex items-center gap-2">
                        @foreach(request()->except(['sort', 'page']) as $key => $value)
                            @foreach((array) $value as $v)
                                <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">
                            @endforeach
                        @endforeach
                        <label for="sort" class="hidden text-[15px] text-gray-700 sm:block">{{ __('public.sort_by') }}</label>
                        <select id="sort" name="sort" onchange="this.form.requestSubmit()"
                                class="h-11 rounded-xl border border-gray-300 bg-white pl-3 pr-9 text-[15px] font-semibold text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                            <option value="newest" @selected($sort === 'newest')>{{ __('public.sort_newest') }}</option>
                            <option value="closing" @selected($sort === 'closing')>{{ __('public.sort_closing') }}</option>
                            <option value="positions" @selected($sort === 'positions')>{{ __('public.sort_positions') }}</option>
                        </select>
                        <noscript><button type="submit" class="h-11 rounded-xl border border-gray-300 px-3 text-sm font-bold">{{ __('public.apply_filters') }}</button></noscript>
                    </form>
                </div>
            </div>

            @if($vacancies->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                    <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                        <x-public.icon name="search" class="h-7 w-7" stroke="1.5" />
                    </span>
                    <h2 class="font-bold text-gray-900">{{ __('public.no_results_title') }}</h2>
                    <p class="mt-1 text-[15px] text-gray-600">{{ __('public.no_results') }}</p>
                    @if($activeFilters->isNotEmpty())
                    <a href="{{ route('vacancies.index') }}"
                       class="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark">
                        <x-public.icon name="refresh" class="h-4 w-4" />
                        {{ __('public.clear_all') }}
                    </a>
                    @endif
                </div>
            @else
                <div class="space-y-4">
                    @foreach($vacancies as $vacancy)
                        <x-public.vacancy-card :vacancy="$vacancy" heading-tag="h2" />
                    @endforeach
                </div>

                @if($vacancies->hasPages())
                <div class="mt-8">
                    {{ $vacancies->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
