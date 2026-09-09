<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\Service;
use App\Models\User;
use App\Mail\DailyBackupReport;

class SendDailyBackupReport extends Command
{
    protected $signature = 'backup:send-daily-report {--email= : Email address to send the report to}';
    protected $description = 'Send a daily summary email of all backup statuses';

    public function handle()
    {
        // 1. Resolve recipient email
        $email = $this->option('email') ?: env('BACKUP_REPORT_EMAIL');
        if (empty($email)) {
            $adminUser = User::first();
            $email = $adminUser?->email ?: 'mamad.ershad@yahoo.com';
        }

        $services = Service::all();
        $reportData = [];

        foreach ($services as $service) {
            $settingsPath = $service->path . '/.backup/settings.json';

            if (!File::exists($settingsPath) && env('BACKUP_MOCK_ENABLED', false)) {
                $mockBase = env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services'));
                $mockPath = rtrim($mockBase, '/') . '/' . $service->domain . '/.backup/settings.json';
                if (File::exists($mockPath)) {
                    $settingsPath = $mockPath;
                }
            }

            $settings = [];
            if (File::exists($settingsPath)) {
                $settings = json_decode(File::get($settingsPath), true) ?: [];
            }

            $reportData[] = [
                'name'   => $service->name,
                'status' => $settings['last_backup_status'] ?? 'انجام نشده',
                'time'   => $settings['last_backup'] ?? 'انجام نشده',
                'size'   => $settings['last_backup_size_mb'] ?? 0,
                'ftp'    => !empty($settings['last_ftp_uploaded']) ? 'بله' : 'خیر',
            ];
        }

        try {
            Mail::to($email)->send(new DailyBackupReport($reportData));
            $msg = 'گزارش روزانه پشتیبان‌گیری با موفقیت به ' . $email . ' ارسال شد.';
            Log::info($msg);
            $this->info($msg);
            return 0;
        } catch (\Exception $e) {
            $err = 'خطا در ارسال ایمیل گزارش روزانه به ' . $email . ': ' . $e->getMessage();
            Log::error($err);
            $this->error($err);
            return 1;
        }
    }
}
