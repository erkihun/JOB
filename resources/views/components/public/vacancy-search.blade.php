@props([
    'compact' => false,
])

<form method="GET" action="{{ route('vacancies.index') }}"
      {{ $attributes->merge(['class' => 'mx-auto w-full max-w-4xl rounded-2xl border border-white/20 bg-white p-2 shadow-2xl shadow-slate-950/20 ring-1 ring-white/30']) }}>
    <div class="grid gap-2 md:grid-cols-[1fr_auto]">
        <label class="sr-only" for="hero_search">{{ __('public.search') }}</label>
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input id="hero_search" name="search" value="{{ request('search') }}"
                   class="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-12 pr-4 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                   placeholder="{{ __('public.hero_search_placeholder') }}">
        </div>
        <button type="submit"
                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2">
            <span>{{ __('public.search_jobs') }}</span>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
            </svg>
        </button>
    </div>
</form>
