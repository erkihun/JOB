{{-- Empty state for tables and lists. Put an optional action in the slot. --}}
@props([
    'title' => null,
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <svg class="mx-auto mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
    </svg>
    <p class="empty-state-title">{{ $title ?? __('messages.no_records') }}</p>
    @if($text)
    <p class="empty-state-text">{{ $text }}</p>
    @endif
    @if($slot->isNotEmpty())
    <div class="mt-4 flex justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
