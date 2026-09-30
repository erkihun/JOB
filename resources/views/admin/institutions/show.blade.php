@extends('layouts.admin')
@section('title', $institution->name)

@section('content')
@php $active = $institution->status === 'active'; @endphp
<div class="space-y-6">

    <x-admin.page-header :title="$institution->name"
                         :description="collect([$institution->code, $institution->short_name, $institution->type])->filter()->implode(' · ')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('admin.resource.institutions'), 'url' => route('admin.institutions.index')], ['label' => $institution->short_name ?: $institution->code]]">
        <x-admin.status :tone="$active ? 'success' : 'gray'" :label="$active ? __('admin.status_active') : __('admin.status_inactive')" />
        @can('update', $institution)
        <a href="{{ route('admin.institutions.edit', $institution) }}" class="btn btn-secondary">{{ __('messages.edit') }}</a>
        @endcan
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            {{-- Vacancies --}}
            <x-admin.table-card :title="__('vacancies.job_vacancies')"
                                :meta="trans_choice('messages.records_count', $institution->vacancies->count(), ['count' => $institution->vacancies->count()])">
                @if($institution->vacancies->isNotEmpty())
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="table-th">{{ __('vacancies.title') }}</th>
                            <th class="table-th hidden md:table-cell">{{ __('vacancies.closing_date') }}</th>
                            <th class="table-th">{{ __('vacancies.status') }}</th>
                            <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($institution->vacancies as $vacancy)
                        <tr class="table-row">
                            <td class="table-td">
                                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $vacancy->getTranslation('title', app()->getLocale(), false) ?: $vacancy->getTranslation('title', 'en', false) }}</a>
                                <p class="font-mono text-xs text-gray-600">{{ $vacancy->code }}</p>
                            </td>
                            <td class="table-td table-td-muted hidden md:table-cell">{{ et_date($vacancy->announcement?->closing_date) }}</td>
                            <td class="table-td"><x-admin.status :status="$vacancy->status" /></td>
                            <td class="table-td"><div class="table-actions"><a href="{{ route('admin.vacancies.show', $vacancy) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a></div></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <x-admin.empty :text="__('messages.inst_no_vacancies')" />
                @endif
            </x-admin.table-card>

            @if($institution->latitude && $institution->longitude)
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">{{ __('admin.institution_location') }}</h2>
                    <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}" target="_blank" rel="noopener noreferrer" class="link-action">{{ __('admin.institution_open_in_maps') }}</a>
                </div>
                <iframe width="100%" height="320" style="border:0; display:block;" loading="lazy" allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade" title="{{ $institution->name }}"
                        src="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}&hl={{ app()->getLocale() }}&z=15&output=embed"></iframe>
            </section>
            @endif
        </div>

        {{-- Summary --}}
        <aside class="card card-body lg:sticky lg:top-24">
            <h2 class="card-title mb-4">{{ __('admin.institution_contact_info') }}</h2>
            <dl class="space-y-4 text-sm">
                <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('admin.institution_code') }}</dt><dd class="mt-0.5 font-mono text-gray-900">{{ $institution->code }}</dd></div>
                <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('admin.column.email') }}</dt><dd class="mt-0.5 break-all text-gray-900">{{ $institution->email ?: '—' }}</dd></div>
                <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('admin.column.phone') }}</dt><dd class="mt-0.5 text-gray-900">{{ $institution->phone ?: '—' }}</dd></div>
                <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('admin.institution_website') }}</dt>
                    <dd class="mt-0.5 break-all">@if($institution->website)<a href="{{ $institution->website }}" target="_blank" rel="noopener noreferrer" class="link-action">{{ preg_replace('#^https?://#', '', rtrim($institution->website, '/')) }}</a>@else — @endif</dd></div>
                <div><dt class="text-[13px] font-semibold text-gray-600">{{ __('admin.institution_address') }}</dt><dd class="mt-0.5 text-gray-900">{{ $institution->address ?: '—' }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
