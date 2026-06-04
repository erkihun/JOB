@extends('layouts.public')

@section('title', __('vacancies.job_vacancies'))
@section('meta_description', 'Browse all open job vacancies and career opportunities.')

@section('content')
@php use Illuminate\Support\Str; @endphp

{{-- Page header --}}
<div class="bg-gradient-to-br from-blue-700 to-indigo-800 text-white py-10 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <h1 class="text-3xl font-extrabold tracking-tight">{{ __('vacancies.job_vacancies') }}</h1>
        <p class="mt-2 text-blue-200 text-sm">{{ __('public.vacancies_found', ['count' => $vacancies->total()]) }}</p>
    </div>
</div>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="vacancyFilters()">

    {{-- ── Sticky Filter Bar ── --}}
    <div class="lg:sticky lg:top-16 z-30 bg-white/95 backdrop-blur-sm border border-gray-200 rounded-2xl shadow-sm mb-8 p-4 sm:p-5">
        <form method="GET" action="{{ route('vacancies.index') }}" id="filter-form">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">

                {{-- Search --}}
                <div class="sm:col-span-2 xl:col-span-2">
                    <label for="search" class="block text-xs font-semibold text-gray-500 mb-1.5">
                        {{ __('public.search') }}
                    </label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="{{ __('public.search_placeholder') }}"
                               class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>

                {{-- Department --}}
                <div>
                    <label for="department" class="block text-xs font-semibold text-gray-500 mb-1.5">
                        {{ __('vacancies.department') }}
                    </label>
                    <select id="department" name="department"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        <option value="">{{ __('public.all_departments') }}</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Employment Type --}}
                <div>
                    <label for="employment_type" class="block text-xs font-semibold text-gray-500 mb-1.5">
                        {{ __('vacancies.employment_type') }}
                    </label>
                    <select id="employment_type" name="employment_type"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        <option value="">{{ __('public.all_types') }}</option>
                        @foreach($employmentTypes as $value => $label)
                        <option value="{{ $value }}" {{ request('employment_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        {{ __('public.search') }}
                    </button>
                    @if(request()->hasAny(['search','department','employment_type','location','field_of_study','opening_date','closing_date']))
                    <a href="{{ route('vacancies.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition"
                       title="{{ __('public.clear') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Advanced filters toggle --}}
            <div x-data="{ open: {{ request()->hasAny(['location','field_of_study','opening_date','closing_date']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 transition">
                    <svg class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <span x-text="open ? '{{ __('public.hide_filters') }}' : '{{ __('public.more_filters') }}'"></span>
                </button>
                <div x-show="open" x-collapse class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mt-3 pt-3 border-t border-gray-100">
                    <div>
                        <label for="location" class="block text-xs font-semibold text-gray-500 mb-1.5">{{ __('vacancies.location') }}</label>
                        <input type="text" id="location" name="location" value="{{ request('location') }}"
                               placeholder="{{ __('public.location_placeholder') }}"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label for="field_of_study" class="block text-xs font-semibold text-gray-500 mb-1.5">{{ __('vacancies.field_of_study') }}</label>
                        <input type="text" id="field_of_study" name="field_of_study" value="{{ request('field_of_study') }}"
                               placeholder="{{ __('public.field_placeholder') }}"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label for="opening_date" class="block text-xs font-semibold text-gray-500 mb-1.5">{{ __('vacancies.opening_date') }}</label>
                        <input type="date" id="opening_date" name="opening_date" value="{{ request('opening_date') }}"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label for="closing_date" class="block text-xs font-semibold text-gray-500 mb-1.5">{{ __('vacancies.closing_date') }}</label>
                        <input type="date" id="closing_date" name="closing_date" value="{{ request('closing_date') }}"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Results count --}}
    <p class="mb-5 text-sm text-gray-500 font-medium">
        @if($vacancies->total() === 1)
            {{ __('public.vacancy_found', ['count' => $vacancies->total()]) }}
        @else
            {{ __('public.vacancies_found', ['count' => $vacancies->total()]) }}
        @endif
    </p>

    {{-- ── Empty State ── --}}
    @if($vacancies->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-16 text-center shadow-sm">
        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 mb-2">{{ __('public.no_results_title') }}</h3>
        <p class="text-sm text-gray-500 mb-5">{{ __('public.no_results') }}</p>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ __('public.clear') }}
        </a>
    </div>

    @else
    {{-- ── Vacancy Cards Grid ── --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($vacancies as $vacancy)
        @php
            $daysLeft    = (int) now()->diffInDays($vacancy->closing_date, false);
            $isUrgent    = $daysLeft >= 0 && $daysLeft <= 6;
            $isPast      = $daysLeft < 0;
            $loc         = $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false);
            $desc        = $vacancy->getTranslation('description', app()->getLocale(), false) ?: $vacancy->getTranslation('description', 'en', false);
            $descExcerpt = $desc ? Str::limit(strip_tags($desc), 100) : null;
        @endphp
        <div class="group relative flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-lg hover:border-blue-200 hover:-translate-y-0.5 transition-all duration-200 overflow-hidden scroll-animate" data-delay="{{ ($loop->index % 3) + 1 }}">

            {{-- Urgency indicator --}}
            @if($isUrgent && !$isPast)
            <div class="absolute top-0 inset-x-0 h-1 bg-linear-to-r from-orange-400 to-transparent"></div>
            @elseif(!$isPast)
            <div class="absolute top-0 inset-x-0 h-1 bg-linear-to-r from-green-400 to-transparent"></div>
            @endif

            <div class="flex flex-col flex-1 p-5 pt-6">

                {{-- Header row --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        @if($vacancy->code)
                        <span class="inline-block font-mono text-[10px] font-medium text-gray-400 bg-gray-100 rounded px-1.5 py-0.5 mb-1.5">{{ $vacancy->code }}</span>
                        @endif
                        <h2 class="font-bold text-gray-900 group-hover:text-blue-700 transition leading-snug text-sm sm:text-base">
                            {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                        </h2>
                    </div>
                    @if($vacancy->employment_type)
                    <span class="shrink-0 rounded-lg bg-blue-50 border border-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 whitespace-nowrap">
                        {{ $vacancy->employment_type->label() }}
                    </span>
                    @endif
                </div>

                {{-- Meta chips --}}
                <div class="flex flex-wrap gap-2 text-xs text-gray-500 mb-3">
                    @if($vacancy->institution)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 border border-indigo-100 px-2 py-1 text-indigo-700 font-medium">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                        {{ Str::limit($vacancy->institution->displayName(), 30) }}
                    </span>
                    @endif
                    @if($vacancy->department)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                        <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ Str::limit($vacancy->department, 25) }}
                    </span>
                    @endif
                    @if($loc)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                        <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $loc }}
                    </span>
                    @endif
                    @if($vacancy->number_of_positions)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-50 border border-gray-100 px-2 py-1">
                        <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $vacancy->number_of_positions }} {{ __('public.positions') }}
                    </span>
                    @endif
                </div>

                {{-- Description excerpt --}}
                @if($descExcerpt)
                <p class="text-xs text-gray-500 leading-relaxed mb-4 flex-1">{{ $descExcerpt }}</p>
                @else
                <div class="flex-1"></div>
                @endif

                {{-- Footer --}}
                <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between gap-2">

                    {{-- Closing date countdown --}}
                    @if($isPast)
                    <span class="text-xs font-semibold text-red-600 inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('vacancies.deadline_passed') }}
                    </span>
                    @elseif($isUrgent)
                    <span class="text-xs font-bold text-orange-600 inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
                    </span>
                    @else
                    <span class="text-xs text-gray-400">
                        {{ __('public.closes') }} {{ et_date($vacancy->closing_date, 'M d, Y') }}
                    </span>
                    @endif

                    {{-- CTA buttons --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('vacancies.show', $vacancy) }}"
                           class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                            {{ __('vacancies.view_details') }}
                        </a>
                        @if(!$isPast)
                        <a href="{{ route('vacancies.show', $vacancy) }}"
                           class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">
                            {{ __('vacancies.apply_now') }}
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $vacancies->links() }}
    </div>
    @endif

</div>

@push('scripts')
<script>
function vacancyFilters() {
    return {};
}
</script>
@endpush
@endsection
