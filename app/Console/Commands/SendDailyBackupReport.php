<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Service;
use App\Mail\DailyBackupReport;

class SendDailyBackupReport extends Command
{
    protected $signature = 'backup:send-daily-report';
    protected $description = 'Send a daily summary email of all backup statuses';

    public function handle()
    {
        $email = env('BACKUP_REPORT_EMAIL', 'mamad.ershad@yahoo.com');
        
        $services = Service::all();
        $reportData = [];

        foreach ($services as $service) {
            $settingsPath = storage_path('app/backup_settings/service_' . $service->id . '.json');
            $settings = [];
            if (file_exists($settingsPath)) {
                $settings = json_decode(file_get_contents($settingsPath), true) ?: [];
            }

            $reportData[] = [
                'name' => $service->name,
                'status' => $settings['last_backup_status'] ?? 'نامشخص',
                'time' => $settings['last_backup'] ?? 'انجام نشده',
                'size' => $settings['last_backup_size_mb'] ?? 0,
                'ftp' => !empty($settings['last_ftp_uploaded']) ? 'بله' : 'خیر',
            ];
        }

        try {
            Mail::to($email)->send(new DailyBackupReport($reportData));
            $this->info('Daily backup report sent successfully to ' . $email);
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
}
