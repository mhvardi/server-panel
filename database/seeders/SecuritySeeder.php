<?php

namespace Database\Seeders;

use App\Models\CronJob;
use App\Models\SecuritySetting;
use App\Services\CronJobService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SecuritySeeder extends Seeder
{
    /**
     * Run the database seeds for Security module & automated Cron Jobs.
     */
    public function run(): void
    {
        // 1. Initialise baseline security settings
        $defaults = [
            'iran_ip_restriction'    => 'false',
            'max_login_attempts'     => '3',
            'lockout_minutes'        => '1440',
            'whitelisted_ips'        => "94.183.100.3\n127.0.0.1",
            'upload_file_scan'       => 'true',
            'quarantine_infected'    => 'true',
            'server_monitor_enabled' => 'true',
        ];

        foreach ($defaults as $key => $val) {
            if (SecuritySetting::where('key', $key)->doesntExist()) {
                SecuritySetting::create(['key' => $key, 'value' => $val]);
            }
        }

        // 2. Automatically register essential Security Cron Jobs
        $cronService = app(CronJobService::class);

        $securityCrons = [
            [
                'name'     => 'Security: اسکن دوره‌ای فایل‌ها و وب‌شل‌ها',
                'schedule' => '0 */6 * * *', // Every 6 hours
                'command'  => 'php ' . base_path('artisan') . ' security:scan-files --target=all',
                'run_as'   => 'www-data',
            ],
            [
                'name'     => 'Security: ممیزی پورت‌ها و پروسس‌های سرور',
                'schedule' => '*/30 * * * *', // Every 30 minutes
                'command'  => 'php ' . base_path('artisan') . ' security:audit-server',
                'run_as'   => 'www-data',
            ],
        ];

        try {
            $existingJobs = $cronService->listJobs();
            $existingNames = array_column($existingJobs, 'name');

            foreach ($securityCrons as $cron) {
                if (!in_array($cron['name'], $existingNames, true)) {
                    $cronService->create(
                        $cron['name'],
                        $cron['schedule'],
                        $cron['command'],
                        $cron['run_as'],
                        true
                    );
                }
            }
        } catch (\Throwable $e) {
            // If cron system write fails in testing env, gracefully continue
        }
    }
}
