@extends('layouts.admin')
@section('title', __('messages.edit') . ': ' . $template->type->getLabel())
@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.notification-templates.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.notification_templates') }}</a>
    <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ $template->type->getLabel() }}</h1>
        <span class="rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium uppercase text-brand">{{ $template->locale }}</span>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.notification-templates.update', $template) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.subject') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}"
                           class="form-input mt-1 @error('subject') form-input-error @enderror">
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.body') }} <span class="text-red-500">*</span></label>
                    <p class="mt-0.5 text-xs text-gray-400">
                        Placeholders: <code>{{ '{{ applicant_name }}' }}</code>, <code>{{ '{{ vacancy_title }}' }}</code>,
                        <code>{{ '{{ reference_number }}' }}</code>, <code>{{ '{{ date }}' }}</code>,
                        <code>{{ '{{ time }}' }}</code>, <code>{{ '{{ venue }}' }}</code>
                    </p>
                    <textarea name="body" rows="10"
                              class="form-textarea mt-1 font-mono @error('body') form-input-error @enderror">{{ old('body', $template->body) }}</textarea>
                    @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" id="active" name="active" value="1" {{ $template->active ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                    <label for="active" class="text-sm font-medium text-gray-700">{{ __('messages.active') }}</label>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
                <a href="{{ route('admin.notification-templates.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
