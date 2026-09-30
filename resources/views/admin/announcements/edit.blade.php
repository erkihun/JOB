@extends('layouts.admin')
@section('title', __('messages.edit') . ': ' . $announcement->subject)

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="$announcement->subject"
                         :description="$announcement->code"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.announcements'), 'url' => route('admin.announcements.index')], ['label' => __('messages.edit')]]">
        @if($announcement->isPublished())
        <a href="{{ route('announcements.show', $announcement) }}" target="_blank" rel="noopener" class="btn btn-secondary">
            {{ __('messages.ann_view_public') }}
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" novalidate>
        @csrf @method('PUT')
        @include('admin.announcements._form')
    </form>
</div>
@endsection
