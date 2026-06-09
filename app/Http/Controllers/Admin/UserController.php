<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Roles that may only be granted by a Super Admin. Prevents a privilege
     * escalation where a user holding users.create / users.update assigns the
     * super_admin role to themselves or another account.
     */
    private const PRIVILEGED_ROLES = ['super_admin'];

    /**
     * Guard role assignment so a non-Super-Admin can never grant a privileged
     * role. Throws a validation error rather than silently dropping the role.
     */
    private function guardRoleAssignment(string $role): void
    {
        if (in_array($role, self::PRIVILEGED_ROLES, true) && ! auth()->user()->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role' => [__('auth.not_authorized')],
            ]);
        }
    }

    public function index(Request $request): View
    {
        $query = User::with('roles')
            ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'applicant'))
            ->latest();

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
            );
        }
        if (($role = $request->query('role')) && $role !== 'applicant') {
            $query->role($role);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::whereNotIn('name', ['applicant'])->get();
        $statuses = UserStatus::cases();

        return view('admin.users.index', compact('users', 'roles', 'statuses'));
    }

    public function create(): View
    {
        $roles = Role::whereNotIn('name', ['applicant'])->get();
        $user = new User;

        return view('admin.users.create', compact('roles', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($request->national_id) {
            $request->merge(['national_id' => preg_replace('/\s+/', '', $request->national_id)]);
        }

        if ($request->filled('phone')) {
            $digits = preg_replace('/\D/', '', $request->phone);
            $request->merge(['phone' => '+251'.substr($digits, -10)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^\+251\d{10}$/', 'unique:users,phone'],
            'national_id' => ['nullable', 'digits:16', 'unique:users,national_id'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['required', 'confirmed', ...app(PasswordPolicyService::class)->adminRules()],
        ]);

        $this->guardRoleAssignment($data['role']);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('users/photos', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'profile_photo' => $photoPath,
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
            'created_by' => auth()->id(),
        ]);

        $user->syncRoles([$data['role']]);

        AuditLog::record('user_created', 'users', (string) $user->id, null, [
            'email' => $user->email,
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_created'));
    }

    public function edit(User $user): View
    {
        $roles = Role::whereNotIn('name', ['applicant'])->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

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
            'status' => ['required', Rule::enum(UserStatus::class)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'new_password' => ['nullable', 'confirmed', ...app(PasswordPolicyService::class)->adminRules()],
        ]);

        $this->guardRoleAssignment($data['role']);

        $updates = [
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'status' => $data['status'],
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

        $user->syncRoles([$data['role']]);

        AuditLog::record('user_updated', 'users', (string) $user->id, null, [
            'role' => $data['role'],
            'status' => $data['status'],
            'password_changed' => ! empty($data['new_password']),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $deletedEmail = $user->email;
        $deletedRoles = $user->getRoleNames()->all();

        // The model-level `User::deleting` guard returns false (halting the
        // delete) when this is the last active super admin — even for an actor
        // who bypasses the policy gate. Honour that result instead of reporting
        // a deletion that did not happen.
        if ($user->delete() === false) {
            return redirect()->route('admin.users.index')
                ->with('error', __('messages.cannot_delete_last_super_admin'));
        }

        AuditLog::record('user_deleted', 'users', (string) $user->id, [
            'email' => $deletedEmail,
            'roles' => $deletedRoles,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_deleted'));
    }
}
