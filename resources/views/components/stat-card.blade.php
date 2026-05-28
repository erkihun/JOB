@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'blue',
])

@php
    $tones = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'green' => 'bg-green-50 text-green-700 ring-green-100',
        'red' => 'bg-red-50 text-red-700 ring-red-100',
        'slate' => 'bg-slate-50 text-slate-700 ring-slate-100',
    ];
    $toneClass = $tones[$tone] ?? $tones['blue'];
@endphp

<article {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ $value }}</p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
            {{ $icon ?? '' }}
        </div>
    </div>
    @if($description)
        <p class="mt-3 text-sm text-gray-500">{{ $description }}</p>
    @endif
</article>
