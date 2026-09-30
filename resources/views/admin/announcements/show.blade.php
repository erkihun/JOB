@extends('layouts.admin')
@section('title', $announcement->subject)

@section('content')
@php
    $published = $announcement->isPublished();
    $scheduled = ! $published && $announcement->status === 'published' && $announcement->published_at?->isFuture();
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$announcement->subject"
                         :description="$announcement->code"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.announcements'), 'url' => route('admin.announcements.index')], ['label' => $announcement->code ?? __('messages.view')]]">
        <x-admin.status :tone="$published ? 'success' : ($scheduled ? 'info' : 'gray')"
                        :label="$published ? __('messages.published') : ($scheduled ? __('messages.ann_mode_schedule') : __('messages.draft'))" />
        @if($published)
        <a href="{{ route('announcements.show', $announcement) }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('messages.ann_view_public') }}</a>
        @endif
        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-primary">{{ __('messages.edit') }}</a>
    </x-admin.page-header>

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

        <aside class="card card-body lg:sticky lg:top-24">
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
        </aside>
    </div>
</div>
@endsection
