@php $isEdit = isset($announcement); @endphp

<div class="space-y-5 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">

    {{-- Subject --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('messages.subject') }} <span class="text-red-500">*</span></label>
        <input type="text" name="subject"
               value="{{ old('subject', $announcement->subject ?? '') }}"
               class="form-input mt-1 @error('subject') form-input-error @enderror">
        @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Published At --}}
    @php
        $pubDate = '';
        $pubTime = '';
        if (old('_pub_date')) {
            $pubDate = old('_pub_date');
            $pubTime = old('_pub_time', '');
        } elseif (isset($announcement) && $announcement->published_at) {
            $pubDate = $announcement->published_at->format('Y-m-d');
            $pubTime = $announcement->published_at->format('H:i');
        }
    @endphp

    @if(app()->getLocale() === 'am')
    <div class="space-y-3">
        <x-ethiopian-datepicker
            name="_pub_date"
            :label="__('messages.published_at')"
            :value="$pubDate"/>
        <div class="max-w-xs">
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.time') }}</label>
            <input type="time" name="_pub_time" value="{{ $pubTime }}"
                   class="form-input mt-1">
        </div>
        <p class="text-xs text-gray-400">{{ __('messages.leave_blank_draft') }}</p>
    </div>
    @else
    <div class="max-w-xs">
        <label class="block text-sm font-medium text-gray-700">{{ __('messages.published_at') }}</label>
        <input type="datetime-local" name="published_at"
               value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}"
               class="form-input mt-1">
        <p class="mt-1 text-xs text-gray-400">{{ __('messages.leave_blank_draft') }}</p>
    </div>
    @endif

    {{-- Content (TinyMCE) --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.content') }} <span class="text-red-500">*</span></label>
        <textarea name="content" id="tinymce-content"
                  class="@error('content') form-input-error @enderror">{{ old('content', $announcement->content ?? '') }}</textarea>
        @error('content')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
        </button>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#tinymce-content',
    height: 420,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'wordcount'
    ],
    toolbar:
        'undo redo | blocks | bold italic underline | forecolor backcolor | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | link image table | code fullscreen',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
    branding: false,
    promotion: false,
});
</script>
@endpush
