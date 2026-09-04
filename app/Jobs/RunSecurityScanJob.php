<?php

namespace App\Jobs;

use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Services\FileScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSecurityScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes timeout for deep background scan

    public function __construct(protected string $target = 'all')
    {
    }

    public function handle(FileScanner $scanner): void
    {
        Log::info("Starting background security file scan for target: {$this->target}");

        $directories = [];
        if ($this->target === 'public') {
            $directories[] = public_path();
        } elseif ($this->target === 'services') {
            $serviceDir = is_dir('/var/www') ? '/var/www' : base_path();
            $directories[] = $serviceDir;
        } else {
            $directories[] = public_path();
            $directories[] = storage_path('app');
            if (is_dir('/var/www')) {
                $directories[] = '/var/www';
            }
        }

        $totalScanned = 0;
        $totalInfected = 0;

        foreach ($directories as $dir) {
            $result = $scanner->scanDirectory($dir, true);
            $totalScanned += $result['scanned'];
            $totalInfected += count($result['infected']);
        }

        SecuritySetting::set('last_scan_at', now()->toDateTimeString());

        SecurityEvent::log(
            'file_scan',
            $totalInfected > 0 ? 'critical' : 'info',
            "اسکن پس‌زمینه فایل‌ها پایان یافت. ({$totalScanned} فایل بررسی شد)",
            $totalInfected > 0 
                ? "تعداد {$totalInfected} فایل مشکوک شناسایی و قرنطینه گردید." 
                : "هیچ تهدیدی در سرور شناسایی نشد.",
            ['scanned' => $totalScanned, 'infected' => $totalInfected]
        );

        Log::info("Background security scan completed: Scanned: {$totalScanned}, Infected: {$totalInfected}");
    }
}
