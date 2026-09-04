<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileQuarantine extends Model
{
    protected $fillable = [
        'filename',
        'original_path',
        'quarantine_path',
        'reason',
        'threat_type',
        'file_hash',
        'file_size',
    ];
}
