@extends('layouts.admin')
@section('title', $announcement->subject)

@section('content')
@php
    $published = $announcement->isPublished();
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$announcement->subject"
                         :description="$announcement->code"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.announcements'), 'url' => route('admin.announcements.index')], ['label' => $announcement->code ?? __('messages.view')]]">
        <x-admin.status :tone="$stage->tone()" :label="$stage->label()" />
        @if($published)
        <a href="{{ route('announcements.show', $announcement) }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('messages.ann_view_public') }}</a>
        @endif
        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-primary">{{ __('messages.edit') }}</a>
    </x-admin.page-header>

    @include('admin.announcements._lifecycle')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('messages.ann_content') }}</h2></div>
                <div class="card-body">
                    <div class="prose prose-sm max-w-none text-gray-800">{!! $announcement->renderableHtml() ?: '—' !!}</div>
                </div>
            </section>
            @include('admin.announcements._vacancies')
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="card card-body">
                <h2 class="card-title mb-4">{{ __('vacancies.details') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->opening_date) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->closing_date) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.published_at') }}</dt><dd class="font-semibold text-gray-900">{{ $announcement->published_at ? et_date($announcement->published_at) : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.performed_by') }}</dt><dd class="font-semibold text-gray-900">{{ $announcement->author?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.created_at') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->created_at) }}</dd></div>
                </dl>
                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-[13px] font-semibold text-gray-600">{{ __('vacancies.announcement_institutions') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse($announcement->institutions as $inst)
                            <span class="badge badge-gray">{{ $inst->short_name ?: $inst->name }}</span>
                        @empty
                            <span class="text-sm text-gray-600">—</span>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="card card-body" aria-labelledby="deadline-history">
                <h2 id="deadline-history" class="card-title mb-3">{{ __('recruitment.extension.history') }}</h2>
                @forelse($announcement->deadlineExtensions as $extension)
                <div class="border-t border-gray-100 py-3 text-sm first:border-0 first:pt-0">
                    <p class="font-semibold text-gray-900">{{ __('recruitment.extension.from_to', ['old' => et_date($extension->old_closing_date, 'M d, Y'), 'new' => et_date($extension->new_closing_date, 'M d, Y')]) }}</p>
                    <p class="mt-0.5 text-xs text-gray-600">{{ et_date($extension->extended_at) }} · {{ __('recruitment.extension.by', ['name' => $extension->extendedBy?->name ?? '—']) }}</p>
                    <p class="mt-1 whitespace-pre-line text-gray-800">{{ $extension->reason }}</p>
                    @if($extension->reference)<p class="mt-0.5 font-mono text-xs text-gray-600">{{ $extension->reference }}</p>@endif
                </div>
                @empty
                <p class="text-sm text-gray-600">{{ __('recruitment.extension.history_empty') }}</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
