{{--
    One admin sidebar link (layouts/admin). `sub` = inside a category dropdown: shorter row and no
    icon while the sidebar is expanded (the category already has one); in the collapsed icon rail
    every link shows its icon with a tooltip.
--}}
@props(['item', 'sub' => false])
@php
    $active = request()->routeIs($item['match']);
    $count = (int) ($item['count'] ?? 0);
@endphp
<a href="{{ route($item['route']) }}"
   @if($active) aria-current="page" @endif
   class="nav-item group relative mb-0.5 flex items-center rounded-lg {{ $sub ? 'text-[13.5px]' : 'text-sm' }} {{ $active ? 'nav-active font-semibold' : 'nav-inactive font-medium' }}"
   :class="sidebarCollapsed && !sidebarOpen ? 'h-10 justify-center' : '{{ $sub ? 'h-9 gap-3 px-3' : 'h-10 gap-3 px-3' }}'">
    <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'nav-icon-active' : 'nav-icon-inactive' }}" aria-hidden="true"
         @if($sub) x-show="sidebarCollapsed && !sidebarOpen" x-cloak @endif
         fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="{{ $item['icon'] }}"/>
    </svg>
    <span class="flex-1 truncate" x-show="!sidebarCollapsed || sidebarOpen">{{ $item['label'] }}</span>
    @if($count > 0)
    <span class="nav-count" x-show="!sidebarCollapsed || sidebarOpen"
          title="{{ trans_choice('messages.waiting_count', $count, ['count' => $count]) }}">{{ $count > 99 ? '99+' : $count }}</span>
    <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full" style="background: var(--color-accent);" x-show="sidebarCollapsed && !sidebarOpen" x-cloak aria-hidden="true"></span>
    @endif
    <span class="nav-tooltip" x-show="sidebarCollapsed && !sidebarOpen" x-cloak>{{ $item['label'] }}</span>
</a>
