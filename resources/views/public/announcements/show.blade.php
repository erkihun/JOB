@extends('layouts.public')
@section('title', $announcement->subject)

@section('content')

<x-public.page-header :title="$announcement->subject"
                      :crumbs="[['label' => __('menus.announcements'), 'url' => route('announcements.index')], ['label' => Str::limit($announcement->subject, 40)]]"
                      width="max-w-4xl">
    @if($announcement->published_at)
    <p class="mt-4 inline-flex items-center gap-2 text-sm text-white/70">
        <x-public.icon name="calendar" class="text-white/50" />
        {{ __('public.published_on') }}
        <time datetime="{{ $announcement->published_at->toDateString() }}" class="font-semibold text-white">{{ et_date($announcement->published_at, 'd M Y') }}</time>
    </p>
    @endif
</x-public.page-header>

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    @if($announcement->opening_date && $announcement->closing_date)
        <dl class="mb-6 flex flex-wrap gap-6 text-sm">
            <div><dt>{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold">{{ et_date($announcement->opening_date, 'M d, Y') }}</dd></div>
            <div><dt>{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold">{{ et_date($announcement->closing_date, 'M d, Y') }}</dd></div>
        </dl>
    @endif
    <article class="announcement-content prose prose-sm max-w-none rounded-2xl border border-gray-200 bg-white p-6 text-gray-700 shadow-card sm:prose-base sm:p-10
                    prose-headings:text-gray-900 prose-a:text-brand prose-strong:text-gray-900">
        {!! $announcement->renderableHtml() !!}
    </article>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @foreach($announcement->vacancies as $vacancy)
            <x-public.vacancy-card :vacancy="$vacancy" />
        @endforeach
    </div>
    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('announcements.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition hover:text-brand">
            <x-public.icon name="arrow-left" />
            {{ __('public.back_to_announcements') }}
        </a>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">
            {{ __('public.browse_vacancies') }}
            <x-public.icon name="arrow-right" />
        </a>
    </div>
</div>
@endsection
