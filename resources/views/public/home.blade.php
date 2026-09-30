@extends('layouts.public')

@section('title', __('public.home'))
@section('meta_description', 'Find the latest job vacancies and career opportunities.')

@section('content')
@php
    $orgName = \App\Models\Setting::get('org.name', config('app.name'));
    $locale  = app()->getLocale();
    $tr = fn ($model, string $field) => $model->getTranslation($field, $locale, false) ?: $model->getTranslation($field, 'en', false);
    $safeLink = function (?string $url): ?string {
        $url = trim((string) $url);
        return ($url !== '' && (str_starts_with($url, '/') || preg_match('#^https?://#i', $url))) ? $url : null;
    };
    $statItems = [
        ['value' => $stats['vacancies'],    'label' => __('public.stat_open_vacancies'), 'icon' => 'briefcase'],
        ['value' => $stats['positions'],    'label' => __('public.stat_positions'),      'icon' => 'users'],
        ['value' => $stats['institutions'], 'label' => __('public.stat_institutions'),   'icon' => 'building'],
    ];
@endphp

{{-- ══════════════════════════════════════════════════════ HERO ══ --}}
@if($sliders->isNotEmpty())
<section class="relative isolate h-[calc(100svh-4rem)] min-h-140 max-h-190 overflow-hidden bg-gray-950"
         x-data="{ active: 0, total: {{ $sliders->count() }}, timer: null,
                   start() { if (this.total > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.timer = setInterval(() => this.next(), 7000) },
                   stop() { clearInterval(this.timer) },
                   next() { this.active = (this.active + 1) % this.total },
                   prev() { this.active = (this.active - 1 + this.total) % this.total } }"
         x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()"
         aria-roledescription="carousel">

    {{-- Slides --}}
    @foreach($sliders as $i => $slider)
    @php
        $slideTitle    = $tr($slider, 'title');
        $slideSubtitle = $tr($slider, 'subtitle');
        $slideBtnText  = $tr($slider, 'button_text');
        $slideBtnLink  = $safeLink($slider->button_link);
    @endphp
    <div x-show="active === {{ $i }}"
         x-transition:enter="transition duration-1000 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-700 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0"
         @if($i !== 0) style="display:none" @endif
         aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $sliders->count() }}">
        @if($slider->image_path)
            <img src="{{ Storage::disk('public')->url($slider->image_path) }}" alt=""
                 class="hero-kenburns absolute inset-0 h-full w-full object-cover"
                 @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
        @else
            <div class="absolute inset-0 bg-navy"></div>
        @endif
        <div class="absolute inset-0 bg-linear-to-r from-gray-950/90 via-gray-950/60 to-gray-950/10"></div>
        <div class="absolute inset-0 bg-linear-to-t from-gray-950/80 via-transparent to-transparent"></div>

        {{-- Slide copy --}}
        <div class="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-center px-4 pb-44 sm:px-6 sm:pb-40 lg:px-8">
            <div class="max-w-2xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-white/80 ring-1 ring-white/15 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                    {{ $orgName }}
                </p>
                @if($slideTitle)
                <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $slideTitle }}</h1>
                @endif
                @if($slideSubtitle)
                <p class="mt-4 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg">{{ $slideSubtitle }}</p>
                @endif
                @if($slideBtnText && $slideBtnLink)
                <a href="{{ $slideBtnLink }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/25 backdrop-blur transition hover:bg-white/20">
                    {{ $slideBtnText }}
                    <x-public.icon name="arrow-right" />
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach

    {{-- Search + stats pinned to bottom --}}
    <div class="absolute inset-x-0 bottom-0 z-20">
        <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <x-public.vacancy-search />
                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/80">
                    @foreach($statItems as $stat)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="font-bold tabular-nums text-white">{{ number_format($stat['value']) }}</span>
                        {{ $stat['label'] }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Slider controls --}}
    @if($sliders->count() > 1)
    <div class="absolute bottom-8 right-4 z-30 hidden items-center gap-3 sm:right-6 md:flex lg:right-8">
        <div class="flex gap-1.5">
            @foreach($sliders as $i => $slider)
            <button type="button" @click="active = {{ $i }}"
                    :class="active === {{ $i }} ? 'w-6 bg-white' : 'w-2 bg-white/40 hover:bg-white/70'"
                    class="h-2 rounded-full transition-all duration-300"
                    :aria-current="active === {{ $i }}"
                    aria-label="{{ __('public.go_to_slide', ['number' => $i + 1]) }}"></button>
            @endforeach
        </div>
        <button type="button" @click="prev()" aria-label="{{ __('public.previous') }}"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/25">
            <x-public.icon name="chevron-left" />
        </button>
        <button type="button" @click="next()" aria-label="{{ __('public.next') }}"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/25">
            <x-public.icon name="chevron-right" />
        </button>
    </div>
    @endif
</section>

<style>
    @keyframes heroKenBurns { from { transform: scale(1.08); } to { transform: scale(1); } }
    .hero-kenburns { animation: heroKenBurns 9s ease-out forwards; }
    @media (prefers-reduced-motion: reduce) { .hero-kenburns { animation: none; } }
</style>

@else
{{-- ── Default hero (no slider images) ── --}}
<section class="relative isolate overflow-hidden bg-navy text-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -left-40 -top-40 h-128 w-lg rounded-full bg-brand/50 blur-[120px]"></div>
        <div class="absolute -bottom-40 right-0 h-112 w-md rounded-full bg-accent/20 blur-[120px]"></div>
        <div class="absolute inset-0 opacity-[0.07]"
             style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:48px 48px;mask-image:radial-gradient(ellipse at center,#000 30%,transparent 75%);"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="mb-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/80 ring-1 ring-white/15">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-accent opacity-60 motion-reduce:hidden"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-accent"></span>
                </span>
                {{ $orgName }}
            </p>
            <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('public.hero_subtitle') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-white/70 sm:text-lg">
                {{ __('public.hero_description', ['org' => $orgName]) }}
            </p>

            <div class="mx-auto mt-10 max-w-2xl">
                <x-public.vacancy-search />
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
                <a href="{{ route('track.show') }}" class="inline-flex items-center gap-1.5 font-medium text-white/70 transition hover:text-white">
                    <x-public.icon name="track" />
                    {{ __('menus.track_application') }}
                </a>
                @guest
                <a href="{{ route('applicant.register') }}" class="inline-flex items-center gap-1.5 font-medium text-white/70 transition hover:text-white">
                    <x-public.icon name="user-plus" />
                    {{ __('menus.register') }}
                </a>
                @endguest
            </div>
        </div>

        {{-- Stats --}}
        <dl class="mx-auto mt-14 grid max-w-3xl grid-cols-3 divide-x divide-white/10 rounded-2xl bg-white/5 ring-1 ring-white/10 backdrop-blur">
            @foreach($statItems as $stat)
            <div class="px-3 py-5 text-center sm:px-6">
                <dt class="text-xs text-white/60 sm:text-sm">{{ $stat['label'] }}</dt>
                <dd class="mt-1 text-2xl font-extrabold tabular-nums sm:text-3xl">{{ number_format($stat['value']) }}</dd>
            </div>
            @endforeach
        </dl>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════ OPEN VACANCIES ══ --}}
<section class="py-16 sm:py-20" aria-labelledby="open-vacancies-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 scroll-animate sa-fade">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-accent">{{ __('public.now_hiring') }}</p>
                <h2 id="open-vacancies-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ __('public.open_vacancies') }}</h2>
            </div>
            @if($vacancies->isNotEmpty())
            <a href="{{ route('vacancies.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-brand transition hover:bg-brand-muted">
                {{ __('public.view_all_vacancies') }}
                <x-public.icon name="arrow-right" />
            </a>
            @endif
        </div>

        @if($vacancies->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                <x-public.icon name="briefcase" class="h-7 w-7" stroke="1.5" />
            </span>
            <p class="font-semibold text-gray-900">{{ __('public.no_vacancies') }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ __('public.no_vacancies_hint') }}</p>
            @guest
            <a href="{{ route('applicant.register') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">
                {{ __('menus.register') }}
            </a>
            @endguest
        </div>
        @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($vacancies as $vacancy)
                <x-public.vacancy-card :vacancy="$vacancy" class="scroll-animate" data-delay="{{ ($loop->index % 3) + 1 }}" />
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════════════════ ANNOUNCEMENTS ══ --}}
@if($announcements->isNotEmpty())
<section class="border-y border-gray-200 bg-white py-16 sm:py-20" aria-labelledby="announcements-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 scroll-animate sa-fade">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-accent">{{ __('menus.announcements') }}</p>
                <h2 id="announcements-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ __('public.latest_announcements') }}</h2>
            </div>
            <a href="{{ route('announcements.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-brand transition hover:bg-brand-muted">
                {{ __('public.view_all_announcements') }}
                <x-public.icon name="arrow-right" />
            </a>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            @foreach($announcements as $ann)
            <article class="group relative flex flex-col rounded-2xl border border-gray-200 bg-gray-50/60 p-6 transition hover:border-brand/40 hover:bg-white hover:shadow-card-hover scroll-animate"
                     data-delay="{{ $loop->iteration }}">
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent-muted text-accent">
                        <x-public.icon name="megaphone" />
                    </span>
                    <time datetime="{{ optional($ann->published_at)->toDateString() }}">{{ et_date($ann->published_at, 'd M Y') }}</time>
                </div>
                <h3 class="mt-4 text-base font-bold leading-snug text-gray-900 group-hover:text-brand">
                    <a href="{{ route('announcements.show', $ann) }}" class="after:absolute after:inset-0 after:rounded-2xl">
                        {{ $ann->subject }}
                    </a>
                </h3>
                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-600">
                    {{ Str::limit(trim(html_entity_decode(strip_tags($ann->content))), 220) }}
                </p>
                <span class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-brand">
                    {{ __('public.read_more') }}
                    <x-public.icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                </span>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════ HOW TO APPLY ══ --}}
<section class="py-16 sm:py-20" aria-labelledby="how-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto mb-12 max-w-2xl text-center scroll-animate sa-fade">
            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-accent">{{ __('public.simple_steps') }}</p>
            <h2 id="how-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ __('public.how_it_works') }}</h2>
            <p class="mt-3 text-gray-500">{{ __('public.how_it_works_desc') }}</p>
        </div>

        <div class="relative">
        <div class="absolute left-[12.5%] right-[12.5%] top-7 hidden h-px bg-linear-to-r from-brand/10 via-brand/40 to-brand/10 lg:block" aria-hidden="true"></div>
        <ol class="relative grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['icon' => 'user-plus',  'title' => __('public.process_step_1_title'), 'desc' => __('public.process_step_1_desc')],
                ['icon' => 'user',       'title' => __('public.process_step_2_title'), 'desc' => __('public.process_step_2_desc')],
                ['icon' => 'file-text',  'title' => __('public.process_step_3_title'), 'desc' => __('public.process_step_3_desc')],
                ['icon' => 'track',      'title' => __('public.process_step_4_title'), 'desc' => __('public.process_step_4_desc')],
            ] as $step)
            <li class="relative flex flex-col items-center rounded-2xl px-4 text-center scroll-animate sa-scale" data-delay="{{ $loop->iteration }}">
                <div class="relative">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-brand shadow-card ring-1 ring-gray-200">
                        <x-public.icon :name="$step['icon']" class="h-6 w-6" />
                    </span>
                    <span class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-accent text-xs font-bold text-white ring-4 ring-gray-50">
                        {{ $loop->iteration }}
                    </span>
                </div>
                <h3 class="mt-5 font-bold text-gray-900">{{ $step['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-500">{{ $step['desc'] }}</p>
            </li>
            @endforeach
        </ol>
        </div>

        @guest
        <div class="mt-12 text-center">
            <a href="{{ route('applicant.register') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-brand-dark">
                {{ __('public.get_started') }}
                <x-public.icon name="arrow-right" />
            </a>
        </div>
        @endguest
    </div>
</section>

{{-- ══════════════════════════════════════ WHY APPLY WITH US ══ --}}
<section class="border-t border-gray-200 bg-white py-16 sm:py-20" aria-labelledby="why-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto mb-12 max-w-2xl text-center scroll-animate sa-fade">
            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-accent">{{ __('public.our_commitment') }}</p>
            <h2 id="why-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ __('public.why_apply_title') }}</h2>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            @foreach([
                ['icon' => 'shield-check', 'title' => __('public.why_apply_1_title'), 'desc' => __('public.why_apply_1_desc')],
                ['icon' => 'users',        'title' => __('public.why_apply_2_title'), 'desc' => __('public.why_apply_2_desc')],
                ['icon' => 'trending-up',  'title' => __('public.why_apply_3_title'), 'desc' => __('public.why_apply_3_desc')],
            ] as $item)
            <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-6 scroll-animate" data-delay="{{ $loop->iteration }}">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand text-white shadow-sm">
                    <x-public.icon :name="$item['icon']" class="h-5 w-5" />
                </span>
                <h3 class="mt-5 font-bold text-gray-900">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TRACK APPLICATION CTA ══ --}}
<section class="px-4 pt-16 sm:px-6 sm:pt-20 lg:px-8">
    <div class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-navy px-6 py-12 text-white sm:px-12 sm:py-14 scroll-animate sa-fade">
        <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-brand/50 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-col items-start gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-5">
                <span class="hidden h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15 sm:flex">
                    <x-public.icon name="track" class="h-7 w-7" />
                </span>
                <div class="max-w-xl">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('public.track_cta_title') }}</h2>
                    <p class="mt-2 leading-relaxed text-white/70">{{ __('public.track_cta_desc') }}</p>
                </div>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <a href="{{ route('track.show') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-navy transition hover:bg-gray-100">
                    {{ __('public.track_cta_button') }}
                    <x-public.icon name="arrow-right" />
                </a>
                @guest
                <a href="{{ route('applicant.register') }}"
                   class="inline-flex items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/10">
                    {{ __('menus.register') }}
                </a>
                @endguest
            </div>
        </div>
    </div>
</section>

@endsection
