<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronJob extends Model
{
    /**
     * The primary key type is string since it's an ID from the system.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'schedule',
        'command',
        'run_as',
        'enabled',
        'last_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];
}