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
}
