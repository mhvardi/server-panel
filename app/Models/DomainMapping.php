<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainMapping extends Model
{
    use HasFactory;

    const TYPE_ARVAN  = 'arvan';
    const TYPE_PARKED = 'parked';
    const TYPE_DIRECT = 'direct';

    protected $fillable = [
        'service_id',
        'arvan_domain',
        'source_domain',
        'destination_domain',
        'mapping_type',
        'parent_mapping_id',
        'dns_record_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /** دامنه‌ای که این رکورد روی آن پارک شده (parked domain only) */
    public function parentMapping()
    {
        return $this->belongsTo(DomainMapping::class, 'parent_mapping_id');
    }

    /** دامنه‌های پارک‌شده روی این دامنه */
    public function parkedMappings()
    {
        return $this->hasMany(DomainMapping::class, 'parent_mapping_id');
    }

    /** آیا این رکورد از طریق ابرآروان ساخته شده */
    public function isArvan(): bool
    {
        return $this->mapping_type === self::TYPE_ARVAN;
    }

    /** آیا این رکورد یک دامنه پارک‌شده است */
    public function isParked(): bool
    {
        return $this->mapping_type === self::TYPE_PARKED;
    }

    /** آیا این رکورد اتصال مستقیم است */
    public function isDirect(): bool
    {
        return $this->mapping_type === self::TYPE_DIRECT;
    }
}
