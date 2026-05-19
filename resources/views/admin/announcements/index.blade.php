@extends('layouts.admin')
@section('title', __('menus.announcements'))

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('menus.announcements') }}</h1>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
            + {{ __('messages.add_announcement') }}
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="{{ __('messages.search') }}..."
               class="form-input max-w-xs">
        <button class="btn btn-secondary">{{ __('messages.search') }}</button>
        @if(request('search'))
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.subject') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('vacancies.status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.published_at') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.performed_by') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($announcements as $i => $ann)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-sm text-gray-400">{{ $announcements->firstItem() + $i }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.announcements.show', $ann) }}"
                           class="text-sm font-medium text-gray-900 hover:text-brand">{{ $ann->subject }}</a>
                    </td>
                    <td class="px-4 py-3">
                        @if($ann->isPublished())
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __('messages.published') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">{{ __('messages.draft') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $ann->published_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ann->author?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.announcements.edit', $ann) }}"
                               class="text-xs font-medium text-brand hover:underline">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-500 hover:underline">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $announcements->links() }}
</div>
@endsection
