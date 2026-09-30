@extends('layouts.admin')
@section('title', __('menus.roles'))
@section('content')
@php
    $roleName = fn (string $role) => \Illuminate\Support\Facades\Lang::has('messages.role_names.'.$role) ? __('messages.role_names.'.$role) : \Illuminate\Support\Str::headline($role);
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.roles')"
                         :description="__('messages.roles_intro')"
                         :crumbs="[['label' => __('menus.access_control')], ['label' => __('menus.roles')]]" />

    <x-admin.table-card :meta="trans_choice('messages.records_count', $roles->count(), ['count' => $roles->count()])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.role_name') }}</th>
                    <th class="table-th-right">{{ __('messages.permissions') }}</th>
                    <th class="table-th-right">{{ __('menus.users') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($roles as $role)
                @php $locked = $role->name === 'super_admin'; @endphp
                <tr class="table-row">
                    <td class="table-td">
                        <p class="font-semibold text-gray-900">{{ $roleName($role->name) }}</p>
                        <p class="font-mono text-xs text-gray-500">{{ $role->name }}</p>
                    </td>
                    <td class="table-td text-right">
                        @if($locked)
                            <span class="badge badge-gray">{{ __('messages.roles_all_access') }}</span>
                        @else
                            <span class="tabular-nums font-semibold text-gray-900">{{ $role->permissions_count }}</span>
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="tabular-nums font-semibold text-brand hover:underline">{{ $role->users_count }}</a>
                    </td>
                    <td class="table-td">
                        <div class="table-actions">
                            @if($locked)
                                <span class="text-[13px] text-gray-500">{{ __('messages.roles_locked') }}</span>
                            @else
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit_permissions') }}</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.table-card>
</div>
@endsection
