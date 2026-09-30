@props([
    'number' => null,
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'card']) }}>
    <header class="card-header items-start justify-start">
        @if($number)
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand text-[13px] font-bold text-white">{{ $number }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <h2 class="card-title">{{ $title }}</h2>
            @if($description)
            <p class="card-description leading-relaxed">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </header>
    <div class="space-y-5 px-5 py-5">
        {{ $slot }}
    </div>
</section>
