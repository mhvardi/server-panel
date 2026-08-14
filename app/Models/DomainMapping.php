<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'arvan_domain',
        'source_domain',
        'destination_domain',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
