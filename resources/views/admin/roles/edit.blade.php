@extends('layouts.admin')
@section('title', __('messages.edit_permissions') . ': ' . $role->name)
@section('content')
<div class="space-y-5">
    <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.roles') }}</a>
    <h1 class="text-lg font-semibold text-gray-900">{{ $role->name }}</h1>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
        @csrf @method('PUT')
        <div class="space-y-4">
            @foreach($permissions as $module => $modulePermissions)
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ $module }}</h3>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                    @foreach($modulePermissions as $permission)
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                               {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm text-gray-700">{{ explode('.', $permission->name)[1] ?? $permission->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection
