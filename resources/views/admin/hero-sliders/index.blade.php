@extends('layouts.admin')
@section('title', __('menus.hero_slider'))

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('menus.hero_slider') }}</h1>
        <a href="{{ route('admin.hero-sliders.create') }}" class="btn btn-primary">
            + {{ __('messages.add') }}
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.order') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('vacancies.status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sliders as $slider)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $slider->getTranslation('title', 'en', false) ?: '—' }}
                        </p>
                        @if($slider->getTranslation('title', 'am', false))
                            <p class="text-xs text-gray-400">{{ $slider->getTranslation('title', 'am', false) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($slider->image_path)
                            <img src="{{ Storage::url($slider->image_path) }}"
                                 class="h-12 w-20 rounded-md object-cover border border-gray-200" alt="">
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $slider->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if($slider->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __('messages.active') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">{{ __('messages.inactive') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.hero-sliders.edit', $slider) }}"
                               class="text-xs font-medium text-brand hover:underline">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('admin.hero-sliders.destroy', $slider) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-500 hover:underline">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">{{ __('messages.no_records') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
