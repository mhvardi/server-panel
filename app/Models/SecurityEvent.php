<?php

namespace App\Models;

use App\Mail\SecurityAlertMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityEvent extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'title',
        'description',
        'data',
        'source_ip',
        'resolved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'resolved_at' => 'datetime',
    ];

    public static function log(string $type, string $severity, string $title, ?string $description = null, ?array $data = null, ?string $sourceIp = null): self
    {
        $event = static::create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'source_ip' => $sourceIp,
        ]);

        // If event is critical or warning, send email alert to admin
        if (in_array($severity, ['critical', 'warning'], true)) {
            static::dispatchEmailAlert($event);
        }

        return $event;
    }

    /**
     * Send email notification to server admin
     */
    protected static function dispatchEmailAlert(self $event): void
    {
        try {
            $adminEmail = env('BACKUP_REPORT_EMAIL', 'mamad.ershad@yahoo.com');
            if (!empty($adminEmail)) {
                Mail::to($adminEmail)->send(new SecurityAlertMail($event));
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch security email alert: " . $e->getMessage());
        }
    }
}
