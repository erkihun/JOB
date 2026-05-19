@extends('layouts.public')

@section('title', __('vacancies.job_vacancies'))
@section('meta_description', 'Browse all open job vacancies and career opportunities.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Page Title --}}
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('vacancies.job_vacancies') }}</h1>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('vacancies.index') }}" class="mb-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="search" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ app()->getLocale() === 'am' ? 'ፈልግ' : 'Search' }}
                </label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'የሥራ ርዕስ...' : 'Job title...' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="department" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.department') }}
                </label>
                <select id="department"
                        name="department"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ app()->getLocale() === 'am' ? 'ሁሉም ክፍሎች' : 'All Departments' }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                        {{ $dept }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="employment_type" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.employment_type') }}
                </label>
                <select id="employment_type"
                        name="employment_type"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ app()->getLocale() === 'am' ? 'ሁሉም ዓይነቶች' : 'All Types' }}</option>
                    @foreach($employmentTypes as $value => $label)
                    <option value="{{ $value }}" {{ request('employment_type') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="location" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.location') }}
                </label>
                <input type="text"
                       id="location"
                       name="location"
                       value="{{ request('location') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'አካባቢ...' : 'Location...' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="field_of_study" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.field_of_study') }}
                </label>
                <input type="text"
                       id="field_of_study"
                       name="field_of_study"
                       value="{{ request('field_of_study') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'የጥናት መስክ...' : 'Field of study...' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="opening_date" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.opening_date') }}
                </label>
                <input type="date"
                       id="opening_date"
                       name="opening_date"
                       value="{{ request('opening_date') }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="closing_date" class="block text-xs font-medium text-gray-600 mb-1">
                    {{ __('vacancies.closing_date') }}
                </label>
                <input type="date"
                       id="closing_date"
                       name="closing_date"
                       value="{{ request('closing_date') }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    {{ app()->getLocale() === 'am' ? 'ፈልግ' : 'Search' }}
                </button>
                @if(request()->hasAny(['search','department','employment_type','location','field_of_study','opening_date','closing_date']))
                <a href="{{ route('vacancies.index') }}"
                   class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    {{ app()->getLocale() === 'am' ? 'አጽዳ' : 'Clear' }}
                </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Results count --}}
    <p class="mb-4 text-sm text-gray-500">
        {{ $vacancies->total() }}
        {{ app()->getLocale() === 'am' ? 'ውጤት' : ($vacancies->total() === 1 ? 'vacancy found' : 'vacancies found') }}
    </p>

    {{-- Vacancy List --}}
    @if($vacancies->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 p-12 text-center text-gray-500">
        {{ app()->getLocale() === 'am' ? 'ምንም ውጤት አልተገኘም።' : 'No vacancies found matching your criteria.' }}
    </div>
    @else
    <div class="space-y-4">
        @foreach($vacancies as $vacancy)
        <a href="{{ route('vacancies.show', $vacancy) }}"
           class="group flex flex-col sm:flex-row sm:items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-blue-300">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h2 class="font-semibold text-gray-900 group-hover:text-blue-700 truncate">
                        {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                    </h2>
                    @if($vacancy->employment_type)
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                        {{ $vacancy->employment_type->label() }}
                    </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                    @if($vacancy->department)
                    <span>{{ $vacancy->department }}</span>
                    @endif
                    @if($vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false))
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        {{ $vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false) }}
                    </span>
                    @endif
                    @if($vacancy->number_of_positions)
                    <span>{{ $vacancy->number_of_positions }} {{ app()->getLocale() === 'am' ? 'ቦታ' : 'pos.' }}</span>
                    @endif
                </div>
            </div>

            <div class="shrink-0 text-right text-sm">
                <div class="text-gray-500">
                    {{ app()->getLocale() === 'am' ? 'ይዘጋል:' : 'Closes:' }}
                    <span class="{{ $vacancy->closing_date->diffInDays(now()) <= 3 ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                        {{ $vacancy->closing_date->format('M d, Y') }}
                    </span>
                </div>
                <span class="mt-2 inline-block text-sm font-medium text-blue-600 group-hover:text-blue-800">
                    {{ __('vacancies.view_details') }} →
                </span>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $vacancies->links() }}
    </div>
    @endif

</div>
@endsection
