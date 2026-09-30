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
    $slides = $sliders->map(fn ($s) => [
        'image'    => $s->image_path ? Storage::disk('public')->url($s->image_path) : null,
        'title'    => $tr($s, 'title'),
        'subtitle' => $tr($s, 'subtitle'),
        'btnText'  => $tr($s, 'button_text'),
        'btnLink'  => $safeLink($s->button_link),
    ])->values();
    $sectionEyebrow = 'mb-1.5 text-[13px] font-extrabold uppercase tracking-wider text-accent-dark';
    $sectionTitle   = 'text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl';
@endphp

{{-- ══════════════════════════════════════════════════════ HERO ══ --}}
<section class="relative isolate overflow-hidden bg-ink text-white"
         @if($slides->count() > 1)
         x-data="{ active: 0, total: {{ $slides->count() }}, timer: null,
                   start() { if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.timer = setInterval(() => this.active = (this.active + 1) % this.total, 8000) },
                   stop() { clearInterval(this.timer) } }"
         x-init="start()" @focusin="stop()"
         @else x-data="{ active: 0 }" @endif>

    {{-- Slider images sit behind a strong ink overlay so text always stays readable --}}
    @foreach($slides as $i => $slide)
        @if($slide['image'])
        <img src="{{ $slide['image'] }}" alt="" aria-hidden="true"
             x-show="active === {{ $i }}" x-transition.opacity.duration.700ms
             @if($i !== 0) style="display:none" loading="lazy" @else fetchpriority="high" @endif
             class="absolute inset-0 -z-10 h-full w-full object-cover">
        @endif
    @endforeach
    @if($slides->contains(fn ($s) => $s['image']))
    <div class="absolute inset-0 -z-10 bg-ink/85" aria-hidden="true"></div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-12 lg:gap-14 lg:px-8 lg:py-20">
        <div class="lg:col-span-7">
            <p class="mb-5 inline-flex items-center gap-2 text-[13px] font-extrabold uppercase tracking-wider text-brand-muted">
                <span class="h-2 w-2 rounded-full bg-accent" aria-hidden="true"></span>
                {{ trans_choice('public.now_hiring_count', $stats['vacancies'], ['count' => number_format($stats['vacancies'])]) }}
            </p>

            @if($slides->isNotEmpty())
                @foreach($slides as $i => $slide)
                <div x-show="active === {{ $i }}" @if($i !== 0) style="display:none" @endif>
                    <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">{{ $slide['title'] ?: __('public.hero_title') }}</h1>
                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/80">{{ $slide['subtitle'] ?: __('public.hero_description', ['org' => $orgName]) }}</p>
                    @if($slide['btnText'] && $slide['btnLink'])
                    <a href="{{ $slide['btnLink'] }}" class="mt-5 inline-flex items-center gap-1.5 font-bold text-white underline decoration-white/40 underline-offset-4 hover:decoration-white">
                        {{ $slide['btnText'] }} <x-public.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    @endif
                </div>
                @endforeach
            @else
                <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">{{ __('public.hero_title') }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/80">{{ __('public.hero_description', ['org' => $orgName]) }}</p>
            @endif

            {{-- Search --}}
            <form method="GET" action="{{ route('vacancies.index') }}" role="search"
                  class="mt-8 grid gap-2 rounded-2xl bg-white p-2 shadow-2xl shadow-black/30 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
                <label class="flex h-14 items-center gap-3 rounded-xl bg-gray-50 px-4 ring-brand focus-within:bg-white focus-within:ring-2">
                    <x-public.icon name="search" class="h-5 w-5 shrink-0 text-gray-500" />
                    <span class="sr-only">{{ __('public.search') }}</span>
                    <input name="search" type="search" autocomplete="off" placeholder="{{ __('public.hero_search_placeholder') }}"
                           class="w-full border-0 bg-transparent p-0 text-base text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-0">
                </label>
                <label class="flex h-14 items-center gap-3 rounded-xl bg-gray-50 px-4 ring-brand focus-within:bg-white focus-within:ring-2">
                    <x-public.icon name="office" class="h-5 w-5 shrink-0 text-gray-500" />
                    <span class="sr-only">{{ __('vacancies.department') }}</span>
                    <select name="department" class="w-full border-0 bg-transparent p-0 text-base text-gray-800 focus:outline-none focus:ring-0">
                        <option value="">{{ __('public.all_departments') }}</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->department }}">{{ $dept->department }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit"
                        class="inline-flex h-14 items-center justify-center gap-2 rounded-xl bg-accent-dark px-7 text-base font-extrabold text-white transition hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                    {{ __('public.search_jobs') }}
                </button>
            </form>

            @if($departments->isNotEmpty())
            <div class="mt-5 flex flex-wrap items-center gap-2 text-sm">
                <span class="font-semibold text-white/60">{{ __('public.popular') }}</span>
                @foreach($departments->take(4) as $dept)
                <a href="{{ route('vacancies.index', ['department' => $dept->department]) }}"
                   class="rounded-full border border-white/20 px-3 py-1.5 font-semibold text-white/90 transition hover:border-white/50 hover:bg-white/10">{{ $dept->department }}</a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Closing soon --}}
        <aside class="lg:col-span-5" aria-labelledby="closing-heading">
            <div class="rounded-2xl bg-white p-5 text-gray-900 shadow-2xl shadow-black/30 sm:p-6">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h2 id="closing-heading" class="text-lg font-extrabold">{{ __('public.closing_soon') }}</h2>
                    @if($closingSoon->isNotEmpty())
                    <a href="{{ route('vacancies.index', ['sort' => 'closing']) }}" class="text-sm font-bold text-brand hover:underline">{{ __('public.see_all') }}</a>
                    @endif
                </div>
                @forelse($closingSoon as $vacancy)
                    @php
                        $days = (int) today()->diffInDays($vacancy->announcement->closing_date, false);
                        $urgent = $days <= 6;
                    @endphp
                    <a href="{{ route('vacancies.show', $vacancy) }}" class="group flex items-center gap-4 border-t border-gray-100 py-3.5">
                        <span class="flex w-14 shrink-0 flex-col items-center rounded-xl py-2 {{ $urgent ? 'bg-accent-muted text-accent-dark' : 'bg-brand-muted text-brand-dark' }}">
                            <span class="text-xl font-extrabold leading-none">{{ $days }}</span>
                            <span class="mt-0.5 text-[11px] font-bold">{{ trans_choice('public.days_word', $days) }}</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-[15px] font-bold group-hover:text-brand group-hover:underline">{{ $tr($vacancy, 'title') }}</span>
                            <span class="block truncate text-sm text-gray-600">
                                {{ $vacancy->department ?: $vacancy->institution?->displayName() }}
                                · {{ trans_choice('public.positions_count', (int) $vacancy->number_of_positions, ['count' => (int) $vacancy->number_of_positions]) }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="border-t border-gray-100 py-6 text-center">
                        <p class="font-semibold">{{ __('public.no_vacancies') }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ __('public.no_vacancies_hint') }}</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</section>

{{-- ═══════════════════════════════════════════════ STATS STRIP ══ --}}
<section class="border-b border-gray-200 bg-white" aria-label="{{ __('public.key_details') }}">
    <dl class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
        @foreach([
            [number_format($stats['vacancies']),    __('public.stat_open_vacancies')],
            [number_format($stats['positions']),    __('public.stat_positions')],
            [number_format($stats['institutions']), __('public.stat_institutions')],
            ['24/7',                                __('public.stat_online')],
        ] as [$value, $label])
        <div class="flex flex-col-reverse gap-1 border-gray-100 px-4 py-6 first:pl-0 lg:border-l lg:px-6 lg:first:border-l-0">
            <dt class="text-sm font-semibold text-gray-600">{{ $label }}</dt>
            <dd class="text-3xl font-extrabold tracking-tight text-ink">{{ $value }}</dd>
        </div>
        @endforeach
    </dl>
</section>

{{-- ══════════════════════════════════════════ LATEST VACANCIES ══ --}}
<section class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-20 lg:px-8" aria-labelledby="open-vacancies-heading">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="{{ $sectionEyebrow }}">{{ __('public.now_hiring') }}</p>
            <h2 id="open-vacancies-heading" class="{{ $sectionTitle }}">{{ __('public.latest_vacancies') }}</h2>
        </div>
        @if($vacancies->isNotEmpty())
        <a href="{{ route('vacancies.index') }}"
           class="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-[15px] font-bold text-gray-900 transition hover:bg-gray-50">
            {{ trans_choice('public.view_all_count', $stats['vacancies'], ['count' => number_format($stats['vacancies'])]) }}
            <x-public.icon name="arrow-right" class="h-4 w-4" />
        </a>
        @endif
    </div>

    @if($vacancies->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                <x-public.icon name="briefcase" class="h-7 w-7" stroke="1.5" />
            </span>
            <p class="font-bold text-gray-900">{{ __('public.no_vacancies') }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ __('public.no_vacancies_hint') }}</p>
            @guest
            <a href="{{ route('applicant.register') }}"
               class="mt-6 inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark">
                {{ __('public.create_account') }}
            </a>
            @endguest
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="hidden grid-cols-[minmax(0,3fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1.2fr)_1.5rem] gap-4 border-b border-gray-200 bg-gray-50 px-6 py-3 text-xs font-extrabold uppercase tracking-wider text-gray-600 md:grid" aria-hidden="true">
                <span>{{ __('public.col_position') }}</span>
                <span>{{ __('vacancies.location') }}</span>
                <span>{{ __('public.positions') }}</span>
                <span>{{ __('public.deadline') }}</span>
                <span></span>
            </div>
            <ul>
                @foreach($vacancies as $vacancy)
                    <x-public.vacancy-row :vacancy="$vacancy" />
                @endforeach
            </ul>
        </div>
    @endif
</section>

{{-- ══════════════════════════════════════ BROWSE BY DEPARTMENT ══ --}}
@if($departments->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 lg:px-8" aria-labelledby="dept-heading">
    <h2 id="dept-heading" class="mb-5 text-xl font-extrabold text-gray-900 sm:text-2xl">{{ __('public.browse_by_department') }}</h2>
    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($departments as $dept)
        <li>
            <a href="{{ route('vacancies.index', ['department' => $dept->department]) }}"
               class="group flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 transition hover:border-brand/50 hover:shadow-card-hover">
                <span class="min-w-0">
                    <span class="block truncate text-base font-bold text-gray-900 group-hover:text-brand">{{ $dept->department }}</span>
                    <span class="mt-0.5 block text-sm text-gray-600">
                        {{ trans_choice('public.vacancies_count', $dept->vacancies_count, ['count' => $dept->vacancies_count]) }}
                        · {{ trans_choice('public.positions_count', (int) $dept->positions_count, ['count' => (int) $dept->positions_count]) }}
                    </span>
                </span>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand transition group-hover:bg-brand group-hover:text-white">
                    <x-public.icon name="arrow-right" class="h-4 w-4" />
                </span>
            </a>
        </li>
        @endforeach
    </ul>
</section>
@endif

{{-- ══════════════════════════════════════════════ HOW TO APPLY ══ --}}
<section id="how-to-apply" class="mx-auto max-w-7xl scroll-mt-24 px-4 pt-16 sm:px-6 sm:pt-20 lg:px-8" aria-labelledby="how-heading">
    <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-10">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="{{ $sectionEyebrow }}">{{ __('public.how_it_works') }}</p>
                <h2 id="how-heading" class="{{ $sectionTitle }}">{{ __('public.how_title') }}</h2>
            </div>
            <p class="max-w-md text-[15px] leading-relaxed text-gray-600">{{ __('public.how_it_works_desc') }}</p>
        </div>
        <ol class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                [__('public.process_step_1_title'), __('public.process_step_1_desc'), 'border-brand'],
                [__('public.process_step_2_title'), __('public.process_step_2_desc'), 'border-brand'],
                [__('public.process_step_3_title'), __('public.process_step_3_desc'), 'border-accent'],
                [__('public.process_step_4_title'), __('public.process_step_4_desc'), 'border-ink'],
            ] as [$stepTitle, $stepDesc, $stepBorder])
            <li class="flex flex-col gap-2 border-t-[3px] {{ $stepBorder }} pt-5">
                <span class="text-sm font-extrabold text-brand">{{ __('public.step_n', ['number' => $loop->iteration]) }}</span>
                <span class="text-lg font-extrabold text-gray-900">{{ $stepTitle }}</span>
                <span class="text-[15px] leading-relaxed text-gray-600">{{ $stepDesc }}</span>
            </li>
            @endforeach
        </ol>
        @guest
        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-6">
            <a href="{{ route('applicant.register') }}"
               class="inline-flex h-12 items-center gap-2 rounded-xl bg-brand px-6 text-[15px] font-bold text-white transition hover:bg-brand-dark">
                {{ __('public.get_started') }}
                <x-public.icon name="arrow-right" class="h-4 w-4" />
            </a>
            <a href="{{ route('login') }}" class="inline-flex h-12 items-center px-3 text-[15px] font-bold text-brand hover:underline">{{ __('public.have_account_sign_in') }}</a>
        </div>
        @endguest
    </div>
</section>

{{-- ═════════════════════════════════════════════ ANNOUNCEMENTS ══ --}}
@if($announcements->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-20 lg:px-8" aria-labelledby="announcements-heading">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 id="announcements-heading" class="{{ $sectionTitle }}">{{ __('public.latest_announcements') }}</h2>
        <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-1.5 text-[15px] font-bold text-brand hover:underline">
            {{ __('public.view_all_announcements') }}
            <x-public.icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
        @foreach($announcements as $ann)
        <article class="group relative flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-brand/50 hover:shadow-card-hover">
            <time datetime="{{ optional($ann->published_at)->toDateString() }}" class="text-sm font-bold text-gray-500">{{ et_date($ann->published_at, 'd M Y') }}</time>
            <h3 class="text-lg font-extrabold leading-snug text-gray-900">
                <a href="{{ route('announcements.show', $ann) }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:text-brand">{{ $ann->subject }}</a>
            </h3>
            <p class="line-clamp-3 text-[15px] leading-relaxed text-gray-600">{{ Str::limit(trim(html_entity_decode(strip_tags($ann->content))), 200) }}</p>
            <span class="mt-auto pt-2 text-[15px] font-bold text-brand">{{ __('public.read_more') }} →</span>
        </article>
        @endforeach
    </div>
</section>
@endif

{{-- ════════════════════════════════════════════ OUR COMMITMENT ══ --}}
<section class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-20 lg:px-8" aria-labelledby="why-heading">
    <h2 id="why-heading" class="sr-only">{{ __('public.why_apply_title') }}</h2>
    <ul class="grid gap-6 md:grid-cols-3">
        @foreach([
            ['shield-check', __('public.why_apply_1_title'), __('public.why_apply_1_desc')],
            ['users',        __('public.why_apply_2_title'), __('public.why_apply_2_desc')],
            ['trending-up',  __('public.why_apply_3_title'), __('public.why_apply_3_desc')],
        ] as [$icon, $whyTitle, $whyDesc])
        <li class="flex gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand">
                <x-public.icon :name="$icon" class="h-5 w-5" />
            </span>
            <span>
                <span class="block font-extrabold text-gray-900">{{ $whyTitle }}</span>
                <span class="mt-1 block text-[15px] leading-relaxed text-gray-600">{{ $whyDesc }}</span>
            </span>
        </li>
        @endforeach
    </ul>
</section>

{{-- ═══════════════════════════════════════ TRACK APPLICATION CTA ══ --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
    <div class="flex flex-col gap-6 rounded-3xl border border-brand/25 bg-brand-muted p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-xl">
            <h2 class="text-2xl font-extrabold tracking-tight text-ink">{{ __('public.track_cta_title') }}</h2>
            <p class="mt-2 text-base leading-relaxed text-gray-700">{{ __('public.track_cta_desc') }}</p>
        </div>
        <form method="GET" action="{{ route('track.show') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
            <label for="track_ref" class="sr-only">{{ __('applications.reference_number') }}</label>
            <input id="track_ref" name="reference_number" type="text" placeholder="APP-2026-000123" autocomplete="off" spellcheck="false"
                   class="h-12 w-full rounded-xl border border-brand/30 bg-white px-4 font-mono text-base uppercase placeholder:normal-case placeholder:font-sans focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20 sm:w-64">
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-ink px-6 text-[15px] font-bold text-white transition hover:bg-ink-soft">
                {{ __('public.track_cta_button') }}
            </button>
        </form>
    </div>
</section>

@endsection
