@extends('layouts.applicant')

@section('title', __('vacancies.job_vacancies'))

@section('content')
<div class="space-y-5">

    <h1 class="text-xl font-bold text-gray-900">{{ __('vacancies.job_vacancies') }}</h1>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('applicant.vacancies.index') }}"
          class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 space-y-3">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="search" class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.search') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'የሥራ ርዕስ...' : 'Job title...' }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="department" class="block text-xs font-medium text-gray-600 mb-1">{{ __('vacancies.department') }}</label>
                <select id="department" name="department"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ app()->getLocale() === 'am' ? 'ሁሉም ክፍሎች' : 'All Departments' }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="employment_type" class="block text-xs font-medium text-gray-600 mb-1">{{ __('vacancies.employment_type') }}</label>
                <select id="employment_type" name="employment_type"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ app()->getLocale() === 'am' ? 'ሁሉም ዓይነቶች' : 'All Types' }}</option>
                    @foreach($employmentTypes as $value => $label)
                    <option value="{{ $value }}" {{ request('employment_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="location" class="block text-xs font-medium text-gray-600 mb-1">{{ __('vacancies.location') }}</label>
                <input type="text" id="location" name="location" value="{{ request('location') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'አካባቢ...' : 'Location...' }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label for="field_of_study" class="block text-xs font-medium text-gray-600 mb-1">{{ __('vacancies.field_of_study') }}</label>
                <input type="text" id="field_of_study" name="field_of_study" value="{{ request('field_of_study') }}"
                       placeholder="{{ app()->getLocale() === 'am' ? 'የጥናት መስክ...' : 'Field of study...' }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                    {{ __('messages.search') }}
                </button>
                @if(request()->hasAny(['search','department','employment_type','location','field_of_study','opening_date','closing_date']))
                <a href="{{ route('applicant.vacancies.index') }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    {{ __('messages.reset') }}
                </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Count --}}
    <p class="text-sm text-gray-500">
        {{ $vacancies->total() }}
        {{ app()->getLocale() === 'am' ? 'ውጤት' : ($vacancies->total() === 1 ? 'vacancy found' : 'vacancies found') }}
    </p>

    {{-- List --}}
    @if($vacancies->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 p-12 text-center text-gray-400 bg-white">
        <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <p class="text-sm">{{ app()->getLocale() === 'am' ? 'ምንም ውጤት አልተገኘም።' : 'No vacancies found matching your criteria.' }}</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($vacancies as $vacancy)
        <a href="{{ route('applicant.vacancies.show', $vacancy) }}"
           class="group flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md hover:border-blue-300">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h2 class="font-semibold text-gray-900 group-hover:text-blue-700 text-sm sm:text-base truncate">
                        {{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}
                    </h2>
                    @if($vacancy->employment_type)
                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 shrink-0">
                        {{ $vacancy->employment_type->label() }}
                    </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                    @if($vacancy->department)
                    <span>{{ $vacancy->department }}</span>
                    @endif
                    @if($vacancy->getTranslation('location', app()->getLocale(), false) ?: $vacancy->getTranslation('location', 'en', false))
                    <span class="flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div class="shrink-0 flex sm:flex-col sm:items-end items-center justify-between gap-2">
                <div class="text-xs text-gray-500">
                    {{ app()->getLocale() === 'am' ? 'ይዘጋል:' : 'Closes:' }}
                    <span class="{{ $vacancy->closing_date->diffInDays(now()) <= 3 ? 'text-red-600 font-semibold' : 'text-gray-700 font-medium' }}">
                        {{ et_date($vacancy->closing_date, 'M d, Y') }}
                    </span>
                </div>
                <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-800">
                    {{ __('vacancies.view_details') }} →
                </span>
            </div>
        </a>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $vacancies->links() }}
    </div>
    @endif

</div>
@endsection
