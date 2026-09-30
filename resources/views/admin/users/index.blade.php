@extends('layouts.admin')
@section('title', __('menus.users'))

@section('content')
@php
    $roleName = fn (string $role) => \Illuminate\Support\Facades\Lang::has('messages.role_names.'.$role) ? __('messages.role_names.'.$role) : \Illuminate\Support\Str::headline($role);
    $filtersActive = request()->hasAny(['search', 'role', 'status']);
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.users')"
                         :description="__('messages.users_intro')"
                         :crumbs="[['label' => __('menus.access_control')], ['label' => __('menus.users')]]">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add_user') }}
        </a>
    </x-admin.page-header>

    <x-admin.filters :reset="route('admin.users.index')" :active="$filtersActive">
        <div class="lg:col-span-2">
            <label for="search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.users_search_placeholder') }}" class="form-input">
        </div>
        <div>
            <label for="role" class="form-label">{{ __('menus.roles') }}</label>
            <select id="role" name="role" class="form-select">
                <option value="">{{ __('messages.all_roles') }}</option>
                @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $roleName($role->name) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="form-label">{{ __('vacancies.status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('messages.all_statuses') }}</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->getLabel() }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $users->total(), ['count' => number_format($users->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('fields.name') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.roles') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('messages.users_2fa') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="table-row">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :name="$user->name" :photo="$user->profile_photo ? asset('storage/'.$user->profile_photo) : null" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.edit', $user) }}" class="block truncate font-semibold text-gray-900 hover:text-brand">
                                    {{ $user->name }}@if($user->id === auth()->id())<span class="ml-1.5 text-xs font-medium text-gray-500">({{ __('messages.you') }})</span>@endif
                                </a>
                                <p class="truncate text-[13px] text-gray-600">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="table-td hidden md:table-cell">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles as $r)
                            <span class="badge badge-blue">{{ $roleName($r->name) }}</span>
                            @empty
                            <span class="text-gray-500">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="table-td"><x-admin.status :status="$user->status" /></td>
                    <td class="table-td hidden lg:table-cell">
                        @if($user->hasTwoFactorEnabled())
                            <x-admin.status tone="success" :label="__('messages.profile_2fa_on')" />
                        @else
                            <span class="text-[13px] text-gray-500">{{ __('messages.profile_2fa_off') }}</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <div class="table-actions">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
                            @if($user->id !== auth()->id())
                            <x-admin.row-menu>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="menu-item menu-item-danger">{{ __('messages.delete') }}</button>
                                </form>
                            </x-admin.row-menu>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><x-admin.empty :text="$filtersActive ? __('messages.empty_filtered') : null" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $users->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
