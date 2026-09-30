@extends('layouts.admin')
@section('title', __('messages.add_schedule'))
@section('content')
<form method="POST" action="{{ route('admin.schedules.store') }}" class="space-y-5">
    @csrf
    <x-admin.page-header :title="__('messages.add_schedule')" :description="__('messages.schedule_form_intro')"
                         :crumbs="[['label' => __('menus.schedules'), 'url' => route('admin.schedules.index')], ['label' => __('messages.add_schedule')]]" />
    @include('admin.schedules._form')
</form>
@endsection
