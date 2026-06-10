<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Concerns\HasOrderedUuid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasOrderedUuid, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'national_id',
        'gender',
        'profile_photo',
        'password',
        'status',
        'preferred_locale',
        'created_by',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'google2fa_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google2fa_secret' => 'encrypted',
            'google2fa_recovery_codes' => 'encrypted:array',
            'status' => UserStatus::class,
            'gender' => Gender::class,
        ];
    }

    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class);
    }

    public function createdBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function preferredLocale(): string
    {
        return $this->preferred_locale ?? config('app.locale', 'en');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->google2fa_secret);
    }

    public function canAccessAdminArea(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->roles()
            ->where('name', '!=', 'applicant')
            ->exists();
    }

    public function consumeRecoveryCode(string $code): bool
    {
        $hashes = (array) ($this->google2fa_recovery_codes ?? []);

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, (string) $hash)) {
                unset($hashes[$index]);
                $this->forceFill(['google2fa_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    public function hasRememberedMfaDevice(Request $request): bool
    {
        $token = (string) $request->cookie($this->mfaRememberCookieName());

        if ($token === '') {
            return false;
        }

        $devices = DB::table('mfa_remember_devices')
            ->where('user_id', $this->id)
            ->where('expires_at', '>', now())
            ->get(['id', 'token_hash']);

        foreach ($devices as $device) {
            if (Hash::check($token, (string) $device->token_hash)) {
                return true;
            }
        }

        return false;
    }

    public function rememberMfaDevice(RedirectResponse $response, int $days): RedirectResponse
    {
        $token = Str::random(64);

        DB::table('mfa_remember_devices')->insert([
            'id' => (string) Str::orderedUuid(),
            'user_id' => $this->id,
            'token_hash' => Hash::make($token),
            'expires_at' => now()->addDays($days),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $response->withCookie(cookie(
            name: $this->mfaRememberCookieName(),
            value: $token,
            minutes: $days * 24 * 60,
            secure: config('session.secure'),
            httpOnly: true,
            sameSite: config('session.same_site', 'lax'),
        ));
    }

    public function forgetMfaRememberedDevices(): void
    {
        DB::table('mfa_remember_devices')
            ->where('user_id', $this->id)
            ->delete();
    }

    public function mfaRememberCookieName(): string
    {
        return 'mfa_remember_'.$this->getKey();
    }
}
