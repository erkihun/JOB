{{--
    Standard filter card above a table: labelled fields in a responsive grid,
    then Apply / Reset. Put the fields (label + control) in the slot.
      <x-admin.filters :reset="route('admin.users.index')" :active="request()->hasAny(['search','role'])">
          <div><label class="form-label" for="search">…</label><input …></div>
      </x-admin.filters>
--}}
@props([
    'action' => null,
    'reset' => null,
    'active' => false,
])

<form method="GET" action="{{ $action ?? url()->current() }}" role="search"
      {{ $attributes->merge(['class' => 'card card-body']) }}>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] lg:items-end">
        {{ $slot }}
        <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
            <button type="submit" class="btn btn-primary flex-1 justify-center lg:flex-none">{{ __('messages.filter') }}</button>
            @if($active && $reset)
            <a href="{{ $reset }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
            @endif
        </div>
    </div>
</form>
