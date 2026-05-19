@extends('layouts.admin')
@section('title', __('menus.roles'))
@section('content')
<div class="space-y-5">
    <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.roles') }}</h1>
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.role_name') }}</th>
                    <th class="table-th">{{ __('messages.permissions') }}</th>
                    <th class="table-th">{{ __('menus.users') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($roles as $role)
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $role->name }}</td>
                    <td class="table-td">
                        <span class="rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ $role->permissions_count }}</span>
                    </td>
                    <td class="table-td">
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $role->users_count }}</span>
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs font-medium text-brand hover:text-brand-dark">{{ __('messages.edit_permissions') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
