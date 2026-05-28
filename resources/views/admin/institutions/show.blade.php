@extends('layouts.admin')
@section('title', $institution->name)

@section('content')
<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.institutions.index') }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
                {{ __('admin.resource.institutions') }}
            </a>
            <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <h1 class="text-lg font-semibold text-gray-900">{{ $institution->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $institution)
            <a href="{{ route('admin.institutions.edit', $institution) }}" class="btn-secondary btn">{{ __('messages.edit') }}</a>
            @endcan
        </div>
    </div>

    {{-- Info card --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6" style="box-shadow: var(--shadow-card)">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_code') }}</dt>
                <dd class="mt-1 font-mono text-sm font-medium text-gray-900">{{ $institution->code }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_name') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->name }}</dd>
            </div>
            @if($institution->short_name)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_short_name') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->short_name }}</dd>
            </div>
            @endif
            @if($institution->type)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_type') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->type }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.column.status') }}</dt>
                <dd class="mt-1">
                    <span class="{{ $institution->status === 'active' ? 'badge-green' : 'badge-red' }}">
                        {{ $institution->status === 'active' ? __('admin.status_active') : __('admin.status_inactive') }}
                    </span>
                </dd>
            </div>
            @if($institution->email)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.column.email') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->email }}</dd>
            </div>
            @endif
            @if($institution->phone)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.column.phone') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->phone }}</dd>
            </div>
            @endif
            @if($institution->website)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_website') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <a href="{{ $institution->website }}" target="_blank" rel="noopener noreferrer"
                       class="text-blue-600 hover:underline">{{ $institution->website }}</a>
                </dd>
            </div>
            @endif
            @if($institution->address)
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.institution_address') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $institution->address }}</dd>
            </div>
            @endif
            @if($institution->latitude && $institution->longitude)
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('admin.institution_location') }}</dt>
                <dd>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <iframe
                            width="100%"
                            height="320"
                            style="border:0; display:block;"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}&hl={{ app()->getLocale() }}&z=15&output=embed">
                        </iframe>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400 font-mono">
                        {{ $institution->latitude }}, {{ $institution->longitude }}
                        &nbsp;·&nbsp;
                        <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-blue-600 hover:underline">
                            {{ __('admin.institution_open_in_maps') }}
                        </a>
                    </p>
                </dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Vacancies --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden" style="box-shadow: var(--shadow-card)">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-gray-700">{{ __('vacancies.job_vacancies') }} ({{ $institution->vacancies->count() }})</h2>
        </div>
        @if($institution->vacancies->isNotEmpty())
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('vacancies.code') }}</th>
                    <th class="table-th">{{ __('vacancies.title') }}</th>
                    <th class="hidden table-th md:table-cell">{{ __('vacancies.closing_date') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($institution->vacancies as $vacancy)
                <tr class="table-row">
                    <td class="table-td font-mono text-xs text-gray-500">{{ $vacancy->code }}</td>
                    <td class="table-td font-medium text-gray-900">{{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}</td>
                    <td class="hidden table-td text-gray-500 md:table-cell">{{ et_date($vacancy->closing_date) }}</td>
                    <td class="table-td">
                        <span class="{{ $vacancy->status->value === 'open' ? 'badge-green' : 'badge-gray' }}">
                            {{ $vacancy->status->getLabel() }}
                        </span>
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.vacancies.show', $vacancy) }}"
                           class="text-xs font-medium text-gray-400 hover:text-gray-700 transition">{{ __('messages.view') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-5 py-10 text-center text-sm text-gray-400">{{ __('messages.no_records') }}</div>
        @endif
    </div>

</div>
@endsection
