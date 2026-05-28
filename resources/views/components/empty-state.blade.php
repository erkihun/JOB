@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center shadow-sm']) }}>
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
        {{ $icon ?? '' }}
    </div>
    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
    @if($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-5 flex justify-center">{{ $slot }}</div>
    @endif
</div>
