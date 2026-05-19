@extends('layouts.admin')
@section('title', __('vacancies.edit_vacancy'))

@section('content')
<form method="POST" action="{{ route('admin.vacancies.update', $vacancy) }}" class="space-y-4">
    @csrf @method('PUT')
    @include('admin.vacancies._form')
</form>
@endsection
