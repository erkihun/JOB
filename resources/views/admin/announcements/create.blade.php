@extends('layouts.admin')
@section('title', __('messages.add_announcement'))

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('messages.add_announcement')"
                         :description="__('messages.ann_create_intro')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.announcements'), 'url' => route('admin.announcements.index')], ['label' => __('messages.create')]]" />

    <form method="POST" action="{{ route('admin.announcements.store') }}" novalidate>
        @csrf
        @include('admin.announcements._form')
    </form>
</div>
@endsection
