@extends('layouts.applicant')

@section('title', __('vacancies.job_vacancies'))

@section('content')
@php
    $filterKeys = ['search', 'department', 'employment_type', 'location', 'field_of_study'];
    $filtersActive = request()->hasAny($filterKeys);
    $input = 'h-11 w-full rounded-xl border border-gray-300 bg-white px-3.5 text-[15px] text-gray-900 placeholder:text-gray-500 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20';
    $label = 'mb-1.5 block text-sm font-semibold text-gray-700';
@endphp
<div class="space-y-6">
    <x-applicant.page-header :title="__('vacancies.job_vacancies')"
                             :description="trans_choice('public.vacancies_count', $vacancies->total(), ['count' => number_format($vacancies->total())])" />

    <form method="GET" action="{{ route('applicant.vacancies.index') }}" role="search" class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))]">
            <div class="sm:col-span-2 lg:col-span-1">
                <label for="search" class="{{ $label }}">{{ __('public.what') }}</label>
                <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('public.hero_search_placeholder') }}" class="{{ $input }}">
            </div>
            <div>
                <label for="department" class="{{ $label }}">{{ __('vacancies.department') }}</label>
                <select id="department" name="department" class="{{ $input }}">
                    <option value="">{{ __('public.all_departments') }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="employment_type" class="{{ $label }}">{{ __('vacancies.employment_type') }}</label>
                <select id="employment_type" name="employment_type" class="{{ $input }}">
                    <option value="">{{ __('public.all_types') }}</option>
                    @foreach($employmentTypes as $value => $text)
                    <option value="{{ $value }}" @selected(request('employment_type') === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="location" class="{{ $label }}">{{ __('public.where') }}</label>
                <input type="text" id="location" name="location" value="{{ request('location') }}" placeholder="{{ __('public.location_placeholder') }}" class="{{ $input }}">
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            @if($filtersActive)
            <a href="{{ route('applicant.vacancies.index') }}" class="inline-flex h-11 items-center rounded-xl px-4 text-sm font-bold text-gray-700 hover:bg-gray-100">{{ __('public.clear_all') }}</a>
            @endif
            <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-brand px-6 text-sm font-bold text-white hover:bg-brand-dark">{{ __('public.search') }}</button>
        </div>
    </form>

    @if($vacancies->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="font-bold text-gray-900">{{ __('public.no_results_title') }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ __('public.no_results') }}</p>
            @if($filtersActive)
            <a href="{{ route('applicant.vacancies.index') }}" class="mt-5 inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark">{{ __('public.clear_all') }}</a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach($vacancies as $vacancy)
                <x-public.vacancy-card :vacancy="$vacancy" heading-tag="h2"
                                       :href="route('applicant.vacancies.show', $vacancy)"
                                       :applied="in_array($vacancy->id, $appliedIds, true)" />
            @endforeach
        </div>
        @if($vacancies->hasPages())
        <div>{{ $vacancies->links() }}</div>
        @endif
    @endif
</div>
@endsection
