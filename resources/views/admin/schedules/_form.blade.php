@php $isEdit = isset($schedule); @endphp
<div class="max-w-2xl space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.title') }} <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $schedule->title ?? '') }}"
                   class="form-input mt-1 @error('title') form-input-error @enderror">
            @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('menus.vacancies') }} <span class="text-red-500">*</span></label>
            <select name="vacancy_id" class="form-select mt-1">
                <option value="">—</option>
                @foreach($vacancies as $v)
                <option value="{{ $v->id }}" {{ old('vacancy_id', $schedule->vacancy_id ?? '') == $v->id ? 'selected' : '' }}>{{ $v->code }} — {{ $v->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.type') }} <span class="text-red-500">*</span></label>
            <select name="type" class="form-select mt-1">
                @foreach($types as $t)
                <option value="{{ $t->value }}" {{ old('type', $schedule->type?->value ?? '') === $t->value ? 'selected' : '' }}>{{ $t->getLabel() }}</option>
                @endforeach
            </select>
        </div>
        @if(app()->getLocale() === 'am')
            <x-ethiopian-datepicker
                name="date"
                :label="__('dashboard.table.date')"
                :value="old('date', $schedule->date?->format('Y-m-d') ?? '')"
                required/>
        @else
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.date') }} <span class="text-red-500">*</span></label>
            <input type="date" name="date" value="{{ old('date', $schedule->date?->format('Y-m-d') ?? '') }}"
                   class="form-input mt-1 @error('date') form-input-error @enderror">
        </div>
        @endif
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.time') }} <span class="text-red-500">*</span></label>
            <div class="mt-1 flex gap-2">
                <input type="time" name="start_time" value="{{ old('start_time', $schedule->start_time ?? '') }}"
                       class="form-input flex-1">
                <input type="time" name="end_time" value="{{ old('end_time', $schedule->end_time ?? '') }}"
                       class="form-input flex-1">
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.table.venue') }} <span class="text-red-500">*</span></label>
            <input type="text" name="venue" value="{{ old('venue', $schedule->venue ?? '') }}"
                   class="form-input mt-1 @error('venue') form-input-error @enderror">
            @error('venue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.instructions') }}</label>
            <textarea name="instruction" rows="4"
                      class="form-textarea mt-1">{{ old('instruction', $schedule->instruction ?? '') }}</textarea>
        </div>
    </div>
    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
        </button>
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>
</div>
