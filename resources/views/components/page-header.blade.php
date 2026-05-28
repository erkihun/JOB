@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<header {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6']) }}>
    @if($eyebrow)
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-blue-600">{{ $eyebrow }}</p>
    @endif
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ $title }}</h1>
            @if($description)
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">{{ $description }}</p>
            @endif
        </div>
        @if($slot->isNotEmpty())
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
        @endif
    </div>
</header>
