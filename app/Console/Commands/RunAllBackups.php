<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunAllBackups extends Command
{
    protected $signature = 'backup:run-all {--dry-run} {--ftp-only}';
    protected $description = 'Run backups for all enabled services sequentially';

    public function handle()
    {
        $this->info("Starting global backup task...");
        Log::info("Global backup task started.");

        $services = Service::all();
        $count = 0;
        $failed = 0;

        foreach ($services as $service) {
            $settingsPath = $service->path . '/.backup/settings.json';
            
            // Mock mode support
            if (env('BACKUP_MOCK_ENABLED', false)) {
                $mockBase = env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services'));
                $settingsPath = rtrim($mockBase, '/') . '/' . $service->domain . '/.backup/settings.json';
            }

            if (!file_exists($settingsPath)) continue;

            $settings = json_decode(file_get_contents($settingsPath), true);
            if (empty($settings['enabled']) && !$this->option('dry-run')) {
                continue;
            }

            $count++;
            $this->info("=========================================");
            $this->info("Running backup for: {$service->name}");
            
            $args = ['service_id' => $service->id];
            if ($this->option('dry-run')) $args['--dry-run'] = true;
            if ($this->option('ftp-only')) $args['--ftp-only'] = true;

            $exitCode = Artisan::call('backup:run-service', $args, $this->output);
            
            if ($exitCode !== 0) {
                $failed++;
                $this->error("Backup failed for {$service->name}");
            } else {
                $this->info("Backup successful for {$service->name}");
            }
        }

        $this->info("=========================================");
        $this->info("Finished! Processed: {$count} services. Failed: {$failed}");
        Log::info("Global backup task finished. Processed: {$count}, Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
