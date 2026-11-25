{{--
    "More actions" (⋯) menu at the end of a table row. Items go in the slot:
      <x-admin.row-menu>
          <a href="…" class="menu-item">Edit</a>
          <form method="POST" action="…">@csrf @method('DELETE')
              <button class="menu-item menu-item-danger">Delete</button></form>
      </x-admin.row-menu>

    The menu is teleported to <body> and placed with fixed positioning, so the table's
    scroll box (overflow-x-auto) and the card's overflow-hidden can never clip it. It opens
    below the button, or above it when there is more room there (e.g. on the last rows).
--}}
@props(['label' => null])

@php($menuId = 'row-menu-'.\Illuminate\Support\Str::uuid())
<div class="relative inline-block text-left" x-data="{
    open: false,
    menu() { return document.getElementById('{{ $menuId }}'); },
    toggle() { this.open ? this.close() : this.show(); },
    show() { this.open = true; this.$nextTick(() => this.place()); },
    close() { this.open = false; },
    place() {
        const menu = this.menu();
        if (!this.open || !menu) return;
        const trigger = this.$refs.trigger.getBoundingClientRect();
        // The button scrolled out of view: close instead of leaving the menu floating.
        if (trigger.bottom < 0 || trigger.top > window.innerHeight) { this.close(); return; }
        const gap = 4, edge = 8;
        menu.style.maxHeight = '';
        const below = window.innerHeight - trigger.bottom - gap - edge;
        const above = trigger.top - gap - edge;
        const openAbove = menu.scrollHeight > below && above > below;
        menu.style.maxHeight = Math.max(0, openAbove ? above : below) + 'px';
        menu.style.top = (openAbove ? trigger.top - gap - menu.offsetHeight : trigger.bottom + gap) + 'px';
        menu.style.left = Math.max(edge, Math.min(trigger.right - menu.offsetWidth, window.innerWidth - edge - menu.offsetWidth)) + 'px';
    },
}"
     @click.window="if (open && !$refs.trigger.contains($event.target) && !menu()?.contains($event.target)) close()"
     @keydown.escape.window="close()"
     @resize.window="place()"
     @scroll.window.capture="if (!menu()?.contains($event.target)) place()">
    <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open.toString()" aria-haspopup="menu"
            aria-controls="{{ $menuId }}"
            class="btn btn-ghost btn-sm btn-icon text-gray-600" aria-label="{{ $label ?? __('messages.more_actions') }}">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </button>
    <template x-teleport="body">
        <div id="{{ $menuId }}" x-show="open" x-cloak x-transition.opacity.duration.100ms class="menu" role="menu"
             style="position:fixed; top:0; left:0; right:auto; margin:0; z-index:60; max-width:calc(100vw - 16px); overflow-y:auto">
            {{ $slot }}
        </div>
    </template>
</div>
