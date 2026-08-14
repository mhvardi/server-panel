<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunServiceBackup extends Command
{
    protected $signature = 'backup:run-service {service_id}';
    protected $description = 'Run a backup for a specific service based on its JSON settings.';

    public function handle()
    {
        $serviceId = $this->argument('service_id');
        $service = Service::find($serviceId);

        if (!$service) {
            $this->error("Service with ID {$serviceId} not found.");
            return 1;
        }

        $settingsPath = $service->path . '/.backup/settings.json';
        if (!File::exists($settingsPath)) {
            $this->info("No backup settings found for service {$service->name}. Skipping.");
            return 0;
        }

        $settings = json_decode(File::get($settingsPath), true);

        if (empty($settings['enabled'])) {
            $this->info("Backup is disabled for service {$service->name}. Skipping.");
            return 0;
        }

        $this->info("Starting backup for service: {$service->name}");
        Log::info("Starting backup for service: {$service->name}");

        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $finalArchive = "{$backupDir}/backup_{$timestamp}.tar.gz";

        $tempFiles = [];

        try {
            // Backup files
            if ($settings['include_files']) {
                $this->info('Backing up files...');
                $filesArchive = "{$backupDir}/files_{$timestamp}.tar";
                $tempFiles[] = $filesArchive;

                $process = new Process(['tar', '-cf', $filesArchive, '-C', $service->path, '.', '--exclude=.backup']);
                $process->mustRun();
                $this->info('File backup created.');
            }

            // Backup database
            if ($settings['include_db'] && !empty($settings['db_name'])) {
                $this->info('Backing up database...');
                $dbDump = "{$backupDir}/db_{$timestamp}.sql";
                $tempFiles[] = $dbDump;

                $process = new Process([
                    'mysqldump',
                    '-u' . env('DB_USERNAME'),
                    '-p' . env('DB_PASSWORD'),
                    $settings['db_name']
                ]);
                $process->setOutputFile($dbDump);
                $process->mustRun();
                $this->info('Database backup created.');
            }

            // Create final archive
            $this->info('Creating final archive...');
            $process = new Process(['tar', '-czf', $finalArchive, '-C', $backupDir, ...array_map('basename', $tempFiles)]);
            $process->mustRun();

            $this->info("Final archive created: {$finalArchive}");

            // Upload to remote if enabled
            if ($settings['remote_enabled']) {
                $this->info('Uploading to remote FTP...');
                // Add FTP upload logic here
            }

            // Cleanup old local backups
            $this->cleanupBackups($backupDir, $settings['local_retention']);

            $settings['last_backup'] = now()->toDateTimeString();
            File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            $this->info("Backup for service {$service->name} completed successfully.");
            Log::info("Backup for service {$service->name} completed successfully.");

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            Log::error("Backup failed for service {$service->name}: " . $e->getMessage());
        } finally {
            File::delete($tempFiles);
        }

        return 0;
    }

    private function cleanupBackups(string $directory, int $retentionDays)
    {
        $files = File::files($directory);
        $cutoff = now()->subDays($retentionDays);

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff->getTimestamp()) {
                File::delete($file->getPathname());
                $this->info("Deleted old backup: " . $file->getFilename());
            }
        }
    }
}
