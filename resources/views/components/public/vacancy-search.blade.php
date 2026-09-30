@props([
    'compact' => false,
])

<form method="GET" action="{{ route('vacancies.index') }}" role="search"
      {{ $attributes->merge(['class' => 'w-full rounded-2xl bg-white p-2 shadow-2xl shadow-gray-950/20 ring-1 ring-gray-900/5']) }}>
    <div class="flex flex-col gap-2 sm:flex-row">
        <label class="sr-only" for="hero_search">{{ __('public.search') }}</label>
        <div class="relative flex-1">
            <x-public.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input id="hero_search" name="search" type="search" value="{{ request('search') }}"
                   autocomplete="off"
                   class="h-12 w-full rounded-xl border-0 bg-gray-50 pl-12 pr-4 text-sm text-gray-900 placeholder:text-gray-400 outline-none ring-1 ring-inset ring-transparent transition focus:bg-white focus:ring-2 focus:ring-brand"
                   placeholder="{{ __('public.hero_search_placeholder') }}">
        </div>
        <button type="submit"
                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-accent px-6 text-sm font-bold text-white transition hover:bg-accent-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
            <span>{{ __('public.search_jobs') }}</span>
            <x-public.icon name="arrow-right" />
        </button>
    </div>
</form>
