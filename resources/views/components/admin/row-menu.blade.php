{{--
    "More actions" (⋯) menu at the end of a table row. Items go in the slot:
      <x-admin.row-menu>
          <a href="…" class="menu-item">Edit</a>
          <form method="POST" action="…">@csrf @method('DELETE')
              <button class="menu-item menu-item-danger">Delete</button></form>
      </x-admin.row-menu>
--}}
@props(['label' => null])

<div class="relative inline-block text-left" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu"
            class="btn btn-ghost btn-sm btn-icon text-gray-600" aria-label="{{ $label ?? __('messages.more_actions') }}">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition.opacity.duration.100ms class="menu" role="menu">
        {{ $slot }}
    </div>
</div>
