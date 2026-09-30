@extends('layouts.admin')
@section('title', __('messages.edit_permissions') . ': ' . $role->name)
@section('content')
@php
    $roleName = \Illuminate\Support\Facades\Lang::has('messages.role_names.'.$role->name) ? __('messages.role_names.'.$role->name) : \Illuminate\Support\Str::headline($role->name);
    $granted = $role->permissions->pluck('name')->all();
    $label = fn (string $perm) => ucfirst(str_replace(['.', '_', '-'], [' › ', ' ', ' '], implode('.', array_slice(explode('.', $perm), 1))));
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="$roleName"
                         :description="__('messages.roles_edit_intro')"
                         :crumbs="[['label' => __('menus.access_control')], ['label' => __('menus.roles'), 'url' => route('admin.roles.index')], ['label' => $roleName]]">
        <span class="badge badge-gray font-mono">{{ $role->name }}</span>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-4"
          x-data="{ total: {{ count($granted) }}, recount() { this.total = this.$root.querySelectorAll('input[name=\'permissions[]\']:checked').length } }"
          @change="recount()">
        @csrf @method('PUT')

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach($permissions as $module => $modulePermissions)
            <section class="card" x-data="{
                        count: {{ $modulePermissions->filter(fn ($p) => in_array($p->name, $granted, true))->count() }},
                        size: {{ $modulePermissions->count() }},
                        sync() { this.count = this.$root.querySelectorAll('input[name=\'permissions[]\']:checked').length },
                        toggleAll(on) { this.$root.querySelectorAll('input[name=\'permissions[]\']').forEach(c => c.checked = on); this.sync(); }
                     }" @change="sync()">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ \Illuminate\Support\Str::headline($module) }}</h2>
                        <p class="card-description"><span x-text="count"></span> / {{ $modulePermissions->count() }} {{ __('messages.roles_enabled') }}</p>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" :checked="count === size" @change="toggleAll($event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        {{ __('messages.select_all') }}
                    </label>
                </div>
                <div class="grid gap-1 p-3 sm:grid-cols-2">
                    @foreach($modulePermissions as $permission)
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 hover:bg-gray-50">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, $granted, true))
                               class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm text-gray-800">{{ $label($permission->name) }}</span>
                    </label>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>

        <div class="card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 px-5 py-3 shadow-lg">
            <p class="text-sm text-gray-700"><span class="font-bold text-gray-900" x-text="total"></span> {{ __('messages.roles_permissions_selected') }}</p>
            <div class="flex gap-2">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
