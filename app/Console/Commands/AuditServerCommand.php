<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Services\ServerAuditService;
use Illuminate\Console\Command;

class AuditServerCommand extends Command
{
    protected $signature = 'security:audit-server';
    protected $description = 'بررسی خودکار پورت‌های باز، پروسس‌های مشکوک و سطح دسترسی فایل‌های سرور';

    public function handle(ServerAuditService $audit): int
    {
        $this->info('در حال مانیتورینگ و ممیزی امنیتی سرور...');

        $summary = $audit->getAuditSummary();

        // 1. Check suspicious processes
        if (!empty($summary['suspicious_processes'])) {
            $count = count($summary['suspicious_processes']);
            $this->warn("تعداد {$count} پروسس مشکوک شناسایی شد!");

            SecurityEvent::log(
                'process',
                'critical',
                "پروسس‌های مشکوک یا مصرف CPU غیرعادی شناسایی شد ({$count} مورد)",
                json_encode($summary['suspicious_processes'], JSON_UNESCAPED_UNICODE)
            );
        }

        // 2. Check permission warnings
        if (!empty($summary['permission_warnings'])) {
            $count = count($summary['permission_warnings']);
            $this->warn("تعداد {$count} دسترسی ناامن به فایل‌ها شناسایی شد!");

            SecurityEvent::log(
                'permission',
                'warning',
                "دسترسی فایل‌های حساس ناامن است ({$count} مورد)",
                json_encode($summary['permission_warnings'], JSON_UNESCAPED_UNICODE)
            );
        }

        // 3. Check sensitive file leaks
        if (!empty($summary['sensitive_files'])) {
            $count = count($summary['sensitive_files']);
            $this->error("تعداد {$count} آسیب‌پذیری بحرانی در فایل‌های حساس وجود دارد!");

            SecurityEvent::log(
                'server',
                'critical',
                "آسیب‌پذیری افشای فایل‌های حساس در سرور ({$count} مورد)",
                json_encode($summary['sensitive_files'], JSON_UNESCAPED_UNICODE)
            );
        }

        $this->info('ممیزی امنیتی سرور با موفقیت انجام و گزارش شد.');
        return Command::SUCCESS;
    }
}
