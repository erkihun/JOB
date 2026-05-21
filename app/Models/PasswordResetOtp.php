<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    public $timestamps = false;

    protected $table = 'password_reset_otps';

    protected $fillable = ['email', 'otp', 'expires_at'];

    /** @var array<string,string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
