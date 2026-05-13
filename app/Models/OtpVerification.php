<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $table = 'otp_verification';

    protected $fillable = [
        'email',
        'otp',
        'attempts',
        'date_time',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'attempts' => 'integer',
    ];

    public const MAX_ATTEMPTS = 5;
    public const TTL_HOURS = 24;

    public function isExpired(): bool
    {
        return $this->date_time->addHours(self::TTL_HOURS)->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /** Find a valid (non-expired) OTP record for an email. */
    public static function findValid(string $email): ?self
    {
        return static::where('email', $email)
            ->where('date_time', '>', now()->subHours(self::TTL_HOURS))
            ->latest('id')
            ->first();
    }
}
