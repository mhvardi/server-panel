<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory;

    // ─── Status constants ──────────────────────────────────────────
    const STATUS_PARKED_DEFAULT = 'parked_default'; // registered, not yet fully configured
    const STATUS_CONNECTED      = 'connected';       // active — pointing to a service
    const STATUS_PARKED_ON      = 'parked_on';       // alias/parked on top of another domain

    // ─── DNS provider constants ────────────────────────────────────
    const DNS_ARVAN    = 'arvan';    // managed through ArvanCloud API
    const DNS_SELF_NS  = 'self_ns';  // NS delegated to our own nameservers
    const DNS_EXTERNAL = 'external'; // externally managed DNS

    // ─── SSL status constants ──────────────────────────────────────
    const SSL_NONE    = 'none';
    const SSL_PENDING = 'pending';
    const SSL_ACTIVE  = 'active';
    const SSL_EXPIRED = 'expired';

    protected $fillable = [
        'domain',
        'status',
        'service_id',
        'parked_on_id',
        'dns_provider',
        'arvan_zone',
        'arvan_record_id',
        'nginx_config_path',
        'ssl_status',
        'ssl_expires_at',
        'notes',
    ];

    protected $casts = [
        'ssl_expires_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────

    /**
     * The service this domain is connected to.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The primary domain this domain is parked on (if status = parked_on).
     */
    public function parkedOnDomain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'parked_on_id');
    }

    /**
     * Domains parked on top of this domain.
     */
    public function parkedDomains(): HasMany
    {
        return $this->hasMany(Domain::class, 'parked_on_id');
    }

    // ─── Helper methods ────────────────────────────────────────────

    /**
     * Is this domain actively connected to a service?
     */
    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }

    /**
     * Is this domain in the default parked state (pending assignment)?
     */
    public function isParkedDefault(): bool
    {
        return $this->status === self::STATUS_PARKED_DEFAULT;
    }

    /**
     * Is this domain parked on top of another domain?
     */
    public function isParkedOn(): bool
    {
        return $this->status === self::STATUS_PARKED_ON;
    }

    /**
     * Returns the full public URL for this domain.
     * Uses https if SSL is active, otherwise http.
     */
    public function getFullUrl(): string
    {
        $scheme = ($this->ssl_status === self::SSL_ACTIVE) ? 'https' : 'http';
        return $scheme . '://' . $this->domain;
    }
}
