{{--
    A card holding a data table: optional header (title, meta such as the row count,
    actions), the table itself in the slot, and an optional footer (pagination).
      <x-admin.table-card :title="__('menus.users')" :meta="trans_choice(…)">
          <x-slot:actions>…</x-slot:actions>
          <table class="min-w-full text-sm">…</table>
          <x-slot:footer>{{ $users->links() }}</x-slot:footer>
      </x-admin.table-card>
--}}
@props([
    'title' => null,
    'meta' => null,
])

<section {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if($title || $meta || isset($actions))
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3.5">
        <div class="flex min-w-0 items-baseline gap-2">
            @if($title)<h2 class="card-title">{{ $title }}</h2>@endif
            @if($meta)<span class="text-sm text-gray-600">{{ $meta }}</span>@endif
        </div>
        @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </header>
    @endif
    <div class="table-scroll">
        {{ $slot }}
    </div>
    @isset($footer)
    @if(trim((string) $footer) !== '')
    <div class="border-t border-gray-100 px-4 py-3">{{ $footer }}</div>
    @endif
    @endisset
</section>
