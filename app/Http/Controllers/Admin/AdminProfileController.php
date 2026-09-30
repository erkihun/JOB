<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use App\Support\EthiopianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function __construct(private readonly LogAuditAction $audit) {}

    public function edit(PasswordPolicyService $passwords): View
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('roles');

        return view('admin.profile.edit', [
            'user' => $user,
            'passwordPolicy' => $passwords->adminPolicy(),
        ]);
    }

    /** Personal details and photo. Changing the sign-in email needs the current password. */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $request->merge([
            'national_id' => $request->filled('national_id') ? preg_replace('/\s+/', '', (string) $request->national_id) : null,
            'phone' => EthiopianPhone::normalize($request->input('phone')),
        ]);
        $emailChanged = mb_strtolower((string) $request->input('email')) !== mb_strtolower((string) $user->email);

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'current_password' => $emailChanged ? ['required', 'current_password'] : ['nullable'],
            'phone' => ['nullable', 'regex:'.EthiopianPhone::PATTERN, 'unique:users,phone,'.$user->id],
            'national_id' => ['nullable', 'digits:16', 'unique:users,national_id,'.$user->id],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'phone.regex' => __('messages.profile_phone_invalid'),
            'current_password.required' => __('messages.profile_email_needs_password'),
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

        $user->fill($updates);
        $changed = array_keys($user->getDirty());
        $user->save();

        if ($changed !== []) {
            $this->audit->handle(
                action: 'profile_updated',
                module: 'users',
                recordId: $user->id,
                newValues: ['fields' => $changed],
            );
        }

        return redirect()->route('admin.profile.edit')->with('success', __('messages.profile_updated'));
    }

    public function updatePassword(Request $request, PasswordPolicyService $passwords): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', 'different:current_password', ...$passwords->adminRules()],
        ]);

        $user->forceFill(['password' => Hash::make($request->input('new_password'))])->save();
        $request->session()->regenerate();

        $this->audit->handle(action: 'password_changed', module: 'users', recordId: $user->id);

        return redirect()->route('admin.profile.edit')->with('success', __('messages.password_changed'));
    }

    public function destroyPhoto(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->forceFill(['profile_photo' => null])->save();
        }

        return redirect()->route('admin.profile.edit')->with('success', __('messages.profile_photo_removed'));
    }
}
