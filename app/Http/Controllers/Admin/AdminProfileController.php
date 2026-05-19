<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($request->national_id) {
            $request->merge(['national_id' => preg_replace('/\s+/', '', $request->national_id)]);
        }

        if ($request->filled('phone')) {
            $digits = preg_replace('/\D/', '', $request->phone);
            $request->merge(['phone' => '+251'.substr($digits, -10)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'regex:/^\+251\d{10}$/', 'unique:users,phone,'.$user->id],
            'national_id' => ['nullable', 'digits:16', 'unique:users,national_id,'.$user->id],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'new_password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $updates = [
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
        ];

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $updates['profile_photo'] = $request->file('profile_photo')->store('users/photos', 'public');
        }

        $user->update($updates);

        if (! empty($data['new_password'])) {
            $user->update(['password' => Hash::make($data['new_password'])]);
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', __('messages.profile_updated'));
    }
}
