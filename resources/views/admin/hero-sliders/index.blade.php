@extends('layouts.admin')
@section('title', __('menus.hero_slider'))

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.hero_slider')"
                         :description="__('messages.slider_intro')"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.hero_slider')]]">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('messages.slider_view_site') }}</a>
        <a href="{{ route('admin.hero-sliders.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.slider_add') }}
        </a>
    </x-admin.page-header>

    @if($sliders->isEmpty())
        <div class="card">
            <x-admin.empty :title="__('messages.slider_empty')" :text="__('messages.slider_empty_hint')">
                <a href="{{ route('admin.hero-sliders.create') }}" class="btn btn-primary btn-sm">{{ __('messages.slider_add') }}</a>
            </x-admin.empty>
        </div>
    @else
    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($sliders as $slider)
        <li class="card flex flex-col overflow-hidden">
            <div class="relative aspect-16/7 bg-gray-100">
                @if($slider->image_path)
                    <img src="{{ Storage::url($slider->image_path) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                @else
                    <div class="flex h-full items-center justify-center text-sm text-gray-500">{{ __('messages.slider_no_image') }}</div>
                @endif
                <span class="absolute left-3 top-3 rounded-md bg-gray-900/75 px-2 py-0.5 text-xs font-bold text-white">#{{ $slider->sort_order }}</span>
                <span class="absolute right-3 top-3">
                    <x-admin.status :tone="$slider->is_active ? 'success' : 'gray'" :label="$slider->is_active ? __('messages.active') : __('messages.inactive')" />
                </span>
            </div>
            <div class="flex flex-1 flex-col gap-1 p-4">
                <p class="font-semibold text-gray-900">{{ $slider->getTranslation('title', 'en', false) ?: '—' }}</p>
                @if($slider->getTranslation('title', 'am', false))
                <p class="text-sm text-gray-600" lang="am">{{ $slider->getTranslation('title', 'am', false) }}</p>
                @endif
            </div>
            <div class="card-footer justify-between">
                <form method="POST" action="{{ route('admin.hero-sliders.destroy', $slider) }}" onsubmit="return confirm(@js(__('messages.confirm_delete')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger-soft btn-sm">{{ __('messages.delete') }}</button>
                </form>
                <a href="{{ route('admin.hero-sliders.edit', $slider) }}" class="btn btn-secondary btn-sm">{{ __('messages.edit') }}</a>
            </div>
        </li>
        @endforeach
    </ul>
    @endif
</div>
@endsection
