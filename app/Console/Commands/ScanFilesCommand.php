<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Services\FileScanner;
use Illuminate\Console\Command;

class ScanFilesCommand extends Command
{
    protected $signature = 'security:scan-files {--target=all : Target to scan: all, services, public}';
    protected $description = 'اسکن امنیتی خودکار فایل‌های سرور و شناسایی وب‌شل و کدهای مشکوک';

    public function handle(FileScanner $scanner): int
    {
        $this->info('در حال شروع اسکن امنیتی فایل‌های سرور...');

        $target = $this->option('target');
        $directories = [];

        if ($target === 'public') {
            $directories[] = public_path();
        } elseif ($target === 'services') {
            $directories[] = is_dir('/var/www') ? '/var/www' : base_path();
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
            $this->line("بررسی دایرکتوری: {$dir}");
            $res = $scanner->scanDirectory($dir, true);
            $totalScanned += $res['scanned'];
            $totalInfected += count($res['infected']);
        }

        SecuritySetting::set('last_scan_at', now()->toDateTimeString());

        $this->info("اسکن به پایان رسید. تعداد {$totalScanned} فایل بررسی شد.");

        if ($totalInfected > 0) {
            $this->error("تعداد {$totalInfected} فایل آلوده یا مشکوک شناسایی و قرنطینه گردید.");
            return Command::FAILURE;
        }

        $this->info('هیچ فایل آلوده‌ای یافت نشد. وضعیت سرور امن است.');
        return Command::SUCCESS;
    }
}
