@extends('layouts.admin')
@section('title', __('menus.notification_templates'))
@section('content')
@php
    $localeNames = ['en' => 'English', 'am' => 'አማርኛ'];
    $stageLabels = [
        'application' => __('messages.ntpl_stage_application'),
        'screening'   => __('messages.ntpl_stage_screening'),
        'assessment'  => __('messages.ntpl_stage_assessment'),
        'final'       => __('messages.ntpl_stage_final'),
        'general'     => __('messages.ntpl_stage_general'),
    ];
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="__('menus.notification_templates')"
                         :description="__('messages.ntpl_intro')"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.notification_templates')]]" />

    {{-- Summary --}}
    <div class="card card-body flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-muted text-brand" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <p class="font-semibold text-gray-900">{{ __('messages.ntpl_customised_count', ['count' => $customised, 'total' => $totalSlots]) }}</p>
                <p class="text-sm text-gray-600">{{ __('messages.ntpl_default_note') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-green-600"></span>{{ __('messages.ntpl_state_custom') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>{{ __('messages.ntpl_state_default') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>{{ __('messages.ntpl_state_off') }}</span>
        </div>
    </div>

    @foreach($stageLabels as $stage => $stageLabel)
        @continue(! $groups->has($stage))
        <section class="card overflow-hidden" aria-labelledby="stage-{{ $stage }}">
            <h2 id="stage-{{ $stage }}" class="border-b border-gray-100 bg-gray-50 px-5 py-3 text-xs font-bold uppercase tracking-wider text-gray-600">{{ $stageLabel }}</h2>
            <ul class="divide-y divide-gray-100">
                @foreach($groups[$stage] as $row)
                @php $type = $row['type']; @endphp
                <li class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $type->getLabel() }}</p>
                        <p class="mt-0.5 text-sm text-gray-600">{{ __('messages.ntpl_when.'.$type->value) }}</p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-{{ count($locales) }} lg:w-[30rem]">
                        @foreach($row['locales'] as $locale => $template)
                        @php
                            $state = $template === null ? 'default' : ($template->active ? 'custom' : 'off');
                            $dot = ['custom' => 'bg-green-600', 'default' => 'bg-gray-300', 'off' => 'bg-amber-500'][$state];
                            $stateLabel = ['custom' => __('messages.ntpl_state_custom'), 'default' => __('messages.ntpl_state_default'), 'off' => __('messages.ntpl_state_off')][$state];
                        @endphp
                        <a href="{{ route('admin.notification-templates.edit', [$type->value, $locale]) }}"
                           class="group flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2.5 transition hover:border-brand/50 hover:bg-brand-muted/40">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900">{{ $localeNames[$locale] ?? strtoupper($locale) }}</span>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-600">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                                    {{ $stateLabel }}
                                    @if($template)<span class="text-gray-400">· {{ et_date($template->updated_at) }}</span>@endif
                                </span>
                            </span>
                            <span class="shrink-0 text-sm font-semibold text-brand group-hover:underline">
                                {{ $template ? __('messages.edit') : __('messages.ntpl_customise') }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
