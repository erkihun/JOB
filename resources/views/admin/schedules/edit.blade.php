@extends('layouts.admin')
@section('title', __('messages.edit_schedule'))
@section('content')
<form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="space-y-5">
    @csrf @method('PUT')
    <x-admin.page-header :title="$schedule->title" :description="__('messages.schedule_form_intro')"
                         :crumbs="[['label' => __('menus.schedules'), 'url' => route('admin.schedules.index')], ['label' => __('messages.edit_schedule')]]" />
    @include('admin.schedules._form')
</form>
@endsection
