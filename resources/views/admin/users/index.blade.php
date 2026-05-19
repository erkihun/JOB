@extends('layouts.admin')
@section('title', __('menus.users'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('menus.users') }}</h1>
        <a href="{{ route('admin.users.create') }}" class="btn-primary btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.add_user') }}
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..."
               class="form-input w-full sm:w-56">
        <select name="role" class="form-select w-auto">
            <option value="">{{ __('messages.all_roles') }}</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select w-auto">
            <option value="">{{ __('messages.all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-navy btn">{{ __('messages.filter') }}</button>
        @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('admin.users.index') }}" class="btn-secondary btn">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('fields.name') }}</th>
                    <th class="hidden table-th sm:table-cell">{{ __('fields.email') }}</th>
                    <th class="hidden table-th md:table-cell">{{ __('menus.roles') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                @php $userBadge = $user->status->value === 'active' ? 'badge-green' : 'badge-red'; @endphp
                <tr class="table-row">
                    <td class="table-td font-medium text-gray-900">{{ $user->name }}</td>
                    <td class="hidden table-td text-gray-500 sm:table-cell">{{ $user->email }}</td>
                    <td class="hidden table-td md:table-cell">
                        @foreach($user->roles as $r)
                        <span class="mr-1 rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">{{ $r->name }}</span>
                        @endforeach
                    </td>
                    <td class="table-td">
                        <span class="{{ $userBadge }}">{{ $user->status->getLabel() }}</span>
                    </td>
                    <td class="table-td text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="text-xs font-medium text-accent hover:text-accent-dark transition">{{ __('messages.edit') }}</a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 transition">{{ __('messages.delete') }}</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-muted">
                                <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ __('messages.no_records') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
