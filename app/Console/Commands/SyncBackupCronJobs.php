<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Services\CronJobService;
use Illuminate\Support\Facades\File;

class SyncBackupCronJobs extends Command
{
    protected $signature = 'backup:sync-cron';
    protected $description = 'Synchronize and upgrade all service backup cron jobs in /etc/cron.d/server-panel with --queue flag for sequential execution';

    public function handle(CronJobService $cronService)
    {
        $this->info("Starting synchronization of all backup cron jobs to sequential queue mode...");

        $services = Service::all();
        $updatedCount = 0;

        foreach ($services as $service) {
            $settingsPath = $service->path . '/.backup/settings.json';
            $settings = [];

            if (File::exists($settingsPath)) {
                $settings = json_decode(File::get($settingsPath), true) ?: [];
            }

            // 1. DB Cron
            $dbCronName = 'backup-service-' . $service->id . '-db';
            $dbCommand = "php " . base_path('artisan') . " backup:run-service " . $service->id . " --type=db --queue";
            $existingDbJob = $cronService->findJobByName($dbCronName);

            if (!empty($settings['db_enabled']) && !empty($settings['db_cron_expression'])) {
                if ($existingDbJob) {
                    $cronService->update($existingDbJob['id'], $dbCronName, $settings['db_cron_expression'], $dbCommand, null, true);
                } else {
                    $cronService->create($dbCronName, $settings['db_cron_expression'], $dbCommand, null, true);
                }
                $this->line("  [+] Service #{$service->id} ({$service->name}): DB Cron synced ({$settings['db_cron_expression']})");
                $updatedCount++;
            } elseif ($existingDbJob) {
                $cronService->delete($existingDbJob['id']);
            }

            // 2. Files Cron
            $filesCronName = 'backup-service-' . $service->id . '-files';
            $filesCommand = "php " . base_path('artisan') . " backup:run-service " . $service->id . " --type=files --queue";
            $existingFilesJob = $cronService->findJobByName($filesCronName);

            if (!empty($settings['files_enabled']) && !empty($settings['files_cron_expression'])) {
                if ($existingFilesJob) {
                    $cronService->update($existingFilesJob['id'], $filesCronName, $settings['files_cron_expression'], $filesCommand, null, true);
                } else {
                    $cronService->create($filesCronName, $settings['files_cron_expression'], $filesCommand, null, true);
                }
                $this->line("  [+] Service #{$service->id} ({$service->name}): Files Cron synced ({$settings['files_cron_expression']})");
                $updatedCount++;
            } elseif ($existingFilesJob) {
                $cronService->delete($existingFilesJob['id']);
            }

            // Delete legacy single job if exists
            $legacyJob = $cronService->findJobByName('backup-service-' . $service->id);
            if ($legacyJob) {
                $cronService->delete($legacyJob['id']);
            }
        }

        $this->info("Synchronization finished. {$updatedCount} cron jobs configured to use sequential queue.");
        return 0;
    }
}
