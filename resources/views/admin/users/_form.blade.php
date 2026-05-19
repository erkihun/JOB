@php $isEdit = isset($user) && $user->exists; @endphp

<div class="space-y-6 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">

    {{-- Profile Photo --}}
    <div x-data="{
        preview: '{{ $isEdit && $user->profile_photo ? asset('storage/'.$user->profile_photo) : '' }}',
        onChange(e) {
            const file = e.target.files[0];
            if (file) this.preview = URL.createObjectURL(file);
        }
    }">
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('fields.profile_photo') }}</label>
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                <template x-if="preview">
                    <img :src="preview" class="h-full w-full object-cover">
                </template>
                <template x-if="!preview">
                    <svg class="h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </template>
            </div>
            <input type="file" name="profile_photo" accept="image/jpeg,image/png"
                   @change="onChange($event)"
                   class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-muted file:text-brand hover:file:bg-brand/10 cursor-pointer">
        </div>
        @error('profile_photo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <hr class="border-gray-100">

    {{-- Row 1: Name · Username · Email --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
                   class="form-input mt-1 @error('name') form-input-error @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.username') }}</label>
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}"
                   class="form-input mt-1 @error('username') form-input-error @enderror">
            @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.email') }} <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                   class="form-input mt-1 @error('email') form-input-error @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Row 2: Phone · National ID · Gender --}}
    @php
        $rawPhone = old('phone', $user->phone ?? '');
        $displayPhone = str_starts_with($rawPhone, '+251') ? substr($rawPhone, 4) : $rawPhone;
        $rawNid = old('national_id', $user->national_id ?? '');
        $fmtNid = $rawNid ? trim(chunk_split($rawNid, 4, ' ')) : '';
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.phone') }}</label>
            <div class="mt-1 flex">
                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 select-none">+251</span>
                <input type="tel" name="phone" value="{{ $displayPhone }}"
                       maxlength="10" placeholder="XXXXXXXXXX"
                       @input="this.value = this.value.replace(/\D/g,'').slice(0,10)"
                       class="form-input rounded-l-none @error('phone') form-input-error @enderror">
            </div>
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-data="{
            val: '{{ $fmtNid }}',
            onInput(e) {
                let digits = e.target.value.replace(/\D/g, '').slice(0, 16);
                let fmt = digits.replace(/(.{4})(?=.)/g, '$1 ');
                this.val = fmt;
                e.target.value = fmt;
            }
        }">
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.national_id') }}</label>
            <input type="text" name="national_id"
                   x-model="val" @input="onInput($event)"
                   maxlength="19" placeholder="XXXX XXXX XXXX XXXX"
                   class="form-input mt-1 font-mono tracking-widest @error('national_id') form-input-error @enderror">
            @error('national_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.gender') }}</label>
            <select name="gender" class="form-select mt-1">
                <option value="">—</option>
                @foreach(\App\Enums\Gender::cases() as $g)
                    <option value="{{ $g->value }}"
                        {{ old('gender', $user->gender?->value ?? '') === $g->value ? 'selected' : '' }}>
                        {{ $g->getLabel() }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Row 3: Role · Status · (spacer) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('menus.roles') }} <span class="text-red-500">*</span></label>
            <select name="role" class="form-select mt-1 @error('role') form-input-error @enderror">
                <option value="">—</option>
                @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name ?? '') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('vacancies.status') }} <span class="text-red-500">*</span></label>
            <select name="status" class="form-select mt-1">
                <option value="active"   {{ old('status', $user->status->value ?? 'active') === 'active'   ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                <option value="inactive" {{ old('status', $user->status->value ?? '') === 'inactive'        ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
            </select>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Row 4: Password · Confirm Password · (spacer) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ $isEdit ? __('messages.new_password') : __('fields.password') }} {{ $isEdit ? '' : '*' }}</label>
            <input type="password" name="{{ $isEdit ? 'new_password' : 'password' }}"
                   class="form-input mt-1 @error('password') @error('new_password') form-input-error @enderror @enderror"
                   {{ $isEdit ? 'placeholder="'.__('messages.leave_blank_to_keep').'"' : '' }}>
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('new_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.confirm_password') }}</label>
            <input type="password" name="{{ $isEdit ? 'new_password_confirmation' : 'password_confirmation' }}"
                   class="form-input mt-1">
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('messages.save_changes') : __('messages.create') }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>
</div>
