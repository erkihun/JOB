@extends('layouts.public')
@section('title', __('menus.announcements'))

@section('content')

<x-public.page-header :title="__('menus.announcements')"
                      :subtitle="__('public.announcements_subtitle')"
                      :crumbs="[['label' => __('menus.announcements')]]"
                      width="max-w-5xl" />

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    @if($announcements->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                <x-public.icon name="megaphone" class="h-7 w-7" stroke="1.5" />
            </span>
            <p class="font-semibold text-gray-900">{{ __('messages.no_records') }}</p>
        </div>
    @else
        <ol class="space-y-4">
            @foreach($announcements as $ann)
            <li>
                <article class="group relative flex gap-5 rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand/50 hover:shadow-card-hover sm:p-6"
                         data-delay="{{ ($loop->index % 3) + 1 }}">
                    {{-- Date block --}}
                    @if($ann->published_at)
                    <time datetime="{{ $ann->published_at->toDateString() }}"
                          class="hidden w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-muted py-3 text-brand sm:flex">
                        <span class="text-2xl font-extrabold leading-none tabular-nums">{{ et_date($ann->published_at, 'd') }}</span>
                        <span class="mt-1 text-[11px] font-semibold uppercase tracking-wide">{{ et_date($ann->published_at, 'M Y') }}</span>
                    </time>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="mb-1 flex items-center gap-1.5 text-xs text-gray-500 sm:hidden">
                            <x-public.icon name="calendar" class="h-3.5 w-3.5" />
                            {{ et_date($ann->published_at, 'd M Y') }}
                        </p>
                        <h2 class="text-base font-bold leading-snug text-gray-900 group-hover:text-brand sm:text-lg">
                            <a href="{{ route('announcements.show', $ann) }}" class="after:absolute after:inset-0 after:rounded-2xl">
                                {{ $ann->subject }}
                            </a>
                        </h2>
                        <p class="mt-2 line-clamp-2 text-[15px] leading-relaxed text-gray-700">
                            {{ Str::limit(trim(html_entity_decode(strip_tags($ann->content))), 260) }}
                        </p>
                        <span class="mt-3 inline-flex items-center gap-1 text-[15px] font-bold text-brand">
                            {{ __('public.read_more') }}
                            <x-public.icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </span>
                    </div>
                </article>
            </li>
            @endforeach
        </ol>

        @if($announcements->hasPages())
        <div class="mt-10">
            {{ $announcements->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
