<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'country',
        'country_name',
        'success',
        'user_agent',
        'blocked_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    /**
     * Ensure country never exceeds database column limits (VARCHAR(5))
     */
    public function setCountryAttribute($value): void
    {
        $this->attributes['country'] = $value ? substr(strtoupper(trim((string) $value)), 0, 5) : null;
    }
}
