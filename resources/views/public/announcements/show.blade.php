@extends('layouts.public')
@section('title', $announcement->subject)

@section('content')

<x-public.page-header :title="$announcement->subject"
                      :crumbs="[['label' => __('menus.announcements'), 'url' => route('announcements.index')], ['label' => Str::limit($announcement->subject, 40)]]"
                      width="max-w-4xl">
    <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-[15px] text-gray-700">
        @if($announcement->published_at)
        <p class="inline-flex items-center gap-2">
            <x-public.icon name="calendar" class="h-4 w-4 text-gray-500" />
            {{ __('public.published_on') }}
            <time datetime="{{ $announcement->published_at->toDateString() }}" class="font-bold text-gray-900">{{ et_date($announcement->published_at, 'd M Y') }}</time>
        </p>
        @endif
        @if($announcement->opening_date && $announcement->closing_date)
        <dl class="flex flex-wrap gap-x-6 gap-y-2">
            <div class="flex gap-1.5"><dt>{{ __('vacancies.opening_date') }}:</dt><dd class="font-bold text-gray-900">{{ et_date($announcement->opening_date, 'M d, Y') }}</dd></div>
            <div class="flex gap-1.5"><dt>{{ __('vacancies.closing_date') }}:</dt><dd class="font-bold text-gray-900">{{ et_date($announcement->closing_date, 'M d, Y') }}</dd></div>
        </dl>
        @endif
    </div>
</x-public.page-header>

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <article class="announcement-content prose prose-sm max-w-none rounded-2xl border border-gray-200 bg-white p-6 text-gray-800 sm:prose-base sm:p-10
                    prose-headings:text-gray-900 prose-a:text-brand prose-strong:text-gray-900">
        {!! $announcement->renderableHtml() !!}
    </article>

    @if($announcement->vacancies->isNotEmpty())
    <h2 class="mb-4 mt-10 text-xl font-extrabold text-gray-900">{{ __('public.vacancies_in_announcement') }}</h2>
    <div class="space-y-4">
        @foreach($announcement->vacancies as $vacancy)
            <x-public.vacancy-card :vacancy="$vacancy" />
        @endforeach
    </div>
    @endif

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('announcements.index') }}"
           class="inline-flex items-center gap-1.5 text-[15px] font-bold text-brand hover:underline">
            <x-public.icon name="arrow-left" class="h-4 w-4" />
            {{ __('public.back_to_announcements') }}
        </a>
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand px-5 text-[15px] font-bold text-white transition hover:bg-brand-dark">
            {{ __('public.browse_vacancies') }}
            <x-public.icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
</div>
@endsection
