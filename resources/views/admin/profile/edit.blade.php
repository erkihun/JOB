@extends('layouts.admin')

@section('title', __('messages.edit_profile'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ __('messages.edit_profile') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.edit_profile_sub') }}</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data"
          class="max-w-xl space-y-5 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Profile Photo --}}
        <div x-data="{
            preview: '{{ $user->profile_photo ? asset('storage/'.$user->profile_photo) : '' }}',
            onChange(e) { const f = e.target.files[0]; if (f) this.preview = URL.createObjectURL(f); }
        }">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('fields.profile_photo') }}</label>
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                    <template x-if="preview">
                        <img :src="preview" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <div class="flex h-full w-full items-center justify-center text-sm font-bold text-white bg-brand">
                            {{ mb_substr($user->name, 0, 2) }}
                        </div>
                    </template>
                </div>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png"
                       @change="onChange($event)"
                       class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-muted file:text-brand hover:file:bg-brand/10 cursor-pointer">
            </div>
            @error('profile_photo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <hr class="border-gray-100">

        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="form-input mt-1 @error('name') form-input-error @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Username --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.username') }}</label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}"
                   class="form-input mt-1 @error('username') form-input-error @enderror">
            @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.email') }} <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="form-input mt-1 @error('email') form-input-error @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Phone --}}
        @php
            $rawPhone = old('phone', $user->phone ?? '');
            $displayPhone = str_starts_with($rawPhone, '+251') ? substr($rawPhone, 4) : $rawPhone;
        @endphp
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

        {{-- National ID --}}
        @php
            $rawNid = old('national_id', $user->national_id ?? '');
            $fmtNid = $rawNid ? trim(chunk_split($rawNid, 4, ' ')) : '';
        @endphp
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

        {{-- Gender --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('fields.gender') }}</label>
            <select name="gender" class="form-select mt-1">
                <option value="">—</option>
                @foreach(\App\Enums\Gender::cases() as $g)
                    <option value="{{ $g->value }}" {{ old('gender', $user->gender?->value) === $g->value ? 'selected' : '' }}>
                        {{ $g->getLabel() }}
                    </option>
                @endforeach
            </select>
        </div>

        <hr class="border-gray-100">

        {{-- New Password --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.new_password') }}</label>
            <input type="password" name="new_password"
                   class="form-input mt-1 @error('new_password') form-input-error @enderror"
                   placeholder="{{ __('messages.leave_blank_to_keep') }}">
            @error('new_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('messages.confirm_password') }}</label>
            <input type="password" name="new_password_confirmation" class="form-input mt-1">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
