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
        @php
            $hasMap = $vacancy->institution && $vacancy->institution->latitude && $vacancy->institution->longitude;
            $mapId  = 'map-modal-app-' . $vacancy->id;
        @endphp

        {{-- Map modal --}}
        @if($hasMap)
        <div id="{{ $mapId }}"
             x-data="{ open: false }"
             x-show="open"
             x-cloak
             @keydown.escape.window="open = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display:none">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="open = false"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $vacancy->institution->name }}</p>
                        @if($vacancy->institution->address)
                        <p class="text-xs text-gray-400 truncate">{{ $vacancy->institution->address }}</p>
                        @endif
                    </div>
                    <button type="button" @click="open = false"
                            class="ml-3 shrink-0 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <iframe
                    width="100%" height="300" style="border:0;display:block;" loading="lazy"
                    allowfullscreen referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q={{ $vacancy->institution->latitude }},{{ $vacancy->institution->longitude }}&hl={{ app()->getLocale() }}&z=15&output=embed">
                </iframe>
                <div class="px-4 py-2.5 border-t border-gray-100 flex justify-end">
                    <a href="https://www.google.com/maps?q={{ $vacancy->institution->latitude }},{{ $vacancy->institution->longitude }}"
                       target="_blank" rel="noopener noreferrer"
                       class="text-xs font-medium text-blue-600 hover:underline">
                        {{ __('admin.institution_open_in_maps') }} ↗
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div x-data="{}">
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
                    @if($vacancy->institution)
                    <span class="inline-flex items-center gap-1 text-indigo-600 font-medium">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                        {{ $vacancy->institution->displayName() }}
                        @if($hasMap)
                        <button type="button"
                                @click.prevent.stop="document.getElementById('{{ $mapId }}')._x_dataStack[0].open = true"
                                title="{{ __('admin.institution_open_in_maps') }}"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                        @endif
                    </span>
                    @endif
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
                    <span class="{{ (int) $vacancy->closing_date->diffInDays(now()) <= 3 ? 'text-red-600 font-semibold' : 'text-gray-700 font-medium' }}">
                        {{ et_date($vacancy->closing_date, 'M d, Y') }}
                    </span>
                </div>
                <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-800">
                    {{ __('vacancies.view_details') }} →
                </span>
            </div>
        </a>
        </div>{{-- /x-data wrapper --}}
        @endforeach
    </div>

    <div class="mt-4">
        {{ $vacancies->links() }}
    </div>
    @endif

</div>
@endsection
