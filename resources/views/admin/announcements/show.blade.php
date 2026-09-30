@extends('layouts.admin')
@section('title', $announcement->subject)

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.announcements.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
            ← {{ __('menus.announcements') }}
        </a>
        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-primary">
            {{ __('messages.edit') }}
        </a>
    </div>

    <div class="card card-body space-y-4">
        <div class="flex items-start justify-between gap-4">
            <h1 class="page-title">{{ $announcement->subject }}</h1>
            @if($announcement->isPublished())
                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __('messages.published') }}</span>
            @else
                <span class="inline-flex shrink-0 items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">{{ __('messages.draft') }}</span>
            @endif
        </div>

        <div class="flex gap-6 text-xs text-gray-600">
            <span>{{ __('messages.performed_by') }}: {{ $announcement->author?->name ?? '—' }}</span>
            <span>{{ __('messages.published_at') }}: {{ $announcement->published_at?->format('d M Y, H:i') ?? '—' }}</span>
            <span>{{ __('messages.created_at') }}: {{ $announcement->created_at->format('d M Y, H:i') }}</span>
        </div>

        <dl class="grid gap-3 text-sm sm:grid-cols-3">
            <div><dt>{{ __('vacancies.announcement_code') }}</dt><dd class="font-semibold">{{ $announcement->code ?? '—' }}</dd></div>
            <div><dt>{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold">{{ et_date($announcement->opening_date) }}</dd></div>
            <div><dt>{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold">{{ et_date($announcement->closing_date) }}</dd></div>
        </dl>
        <p class="text-sm">{{ __('vacancies.announcement_institutions') }}: {{ $announcement->institutions->pluck('name')->join(', ') ?: '—' }}</p>
        @include('admin.announcements._vacancies')
        <hr class="border-gray-100">

        @php $safeHtml = $announcement->renderableHtml(); @endphp
        <div class="prose prose-sm max-w-none text-gray-700">
            {!! $safeHtml !!}
        </div>
    </div>
</div>
@endsection
