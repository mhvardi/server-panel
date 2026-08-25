<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Services\FtpBackupDriver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunServiceBackup extends Command
{
    protected $signature = 'backup:run-service {service_id} {--type=all} {--dry-run} {--ftp-only}';
    protected $description = 'Run an automated backup for a specific service (type: all, db, files).';

    private array $runLogs = [];

    public function handle()
    {
        $serviceId = $this->argument('service_id');
        $service = Service::find($serviceId);

        if (!$service) {
            $this->error("Service with ID {$serviceId} not found.");
            return 1;
        }

        $isMock = env('BACKUP_MOCK_ENABLED', false);
        $mockBase = env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services'));
        
        if ($isMock) {
            $service->path = rtrim($mockBase, '/') . '/' . $service->domain;
            File::ensureDirectoryExists($service->path . '/.backup');
        }

        $settingsPath = $service->path . '/.backup/settings.json';

        if (!File::exists($settingsPath)) {
            if ($isMock) {
                $settings = [
                    'db_enabled' => true,
                    'db_cron_expression' => '0 2 * * *',
                    'db_local_retention_days' => 3,
                    'db_remote_retention_days' => 3,
                    'files_enabled' => true,
                    'files_cron_expression' => '0 2 * * 5',
                    'files_local_retention_days' => 14,
                    'files_remote_retention_days' => 14,
                    'local_enabled' => true,
                    'remote_enabled' => true,
                    'remote_host' => '80.249.115.114',
                    'remote_user' => 'mhvardi@backup.vardicrm.ir',
                    'remote_password' => 'pqDd2PZ1V8Pkq6r3',
                    'remote_path' => '/public_html'
                ];
                File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
            } else {
                $this->info("No backup settings found for service {$service->name}. Skipping.");
                return 0;
            }
        }

        $settings = json_decode(File::get($settingsPath), true) ?: [];

        $type = $this->option('type') ?: 'all';
        $isDryRun = $this->option('dry-run');

        $this->addLog("🚀 شروع عملیات بکاپ (نوع: {$type}) برای سرویس: {$service->name} ({$service->domain})");
        if ($isDryRun) {
            $this->addLog("⚠️ اجرای حالت Dry-Run (شبیه‌سازی)");
        }

        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $timestamp = now()->format('Y-m-d_H-i-s');

        $totalSizeMb = 0;
        $ftpUploaded = false;
        $hasError = false;

        $ftpDriver = null;
        if (!empty($settings['remote_enabled'])) {
            try {
                $ftpDriver = new FtpBackupDriver(
                    $settings['remote_host'], 21,
                    $settings['remote_user'], $settings['remote_password']
                );
                $ftpDriver->connect();
                $remoteDir = rtrim($settings['remote_path'] ?? '/public_html', '/') . '/' . ($service->domain ?: $service->name);
                $ftpDriver->ensureRemoteDir($remoteDir);
            } catch (\Exception $e) {
                $this->addLog("❌ خطای اولیه اتصال FTP: " . $e->getMessage());
                $hasError = true;
                $ftpDriver = null;
            }
        }

        // 1. BACKUP DATABASE (if requested and enabled)
        if (($type === 'all' || $type === 'db') && !$this->option('ftp-only')) {
            $dbEnabled = $settings['db_enabled'] ?? ($settings['include_db'] ?? true);
            $dbName = $service->getDatabaseName();

            if ($dbEnabled && !empty($dbName)) {
                $this->addLog("🗄️ بکاپ‌گیری از پایگاه‌داده: {$dbName} ...");
                $dbFile = "{$backupDir}/db_{$dbName}_{$timestamp}.sql.gz";

                try {
                    if (!$isDryRun) {
                        if ($isMock) {
                            $sql = "-- Mock DB Backup for {$dbName}\n";
                            file_put_contents($dbFile, gzencode($sql));
                        } else {
                            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
                            $dbPort = config('database.connections.mysql.port', '3306');
                            $dbUser = config('database.connections.mysql.username', 'root');
                            $dbPass = config('database.connections.mysql.password', '');
                            $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
                            $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($dbFile);
                            $process = \Illuminate\Support\Facades\Process::run($cmd);
                            if (!$process->successful()) {
                                throw new \Exception('خطا در mysqldump: ' . $process->errorOutput());
                            }
                        }
                    } else {
                        file_put_contents($dbFile, "mock");
                    }

                    $sizeMb = round(filesize($dbFile) / 1024 / 1024, 2);
                    $totalSizeMb += $sizeMb;
                    $this->addLog("✅ بکاپ دیتابیس آماده شد ({$sizeMb} MB).");

                    // FTP Upload for DB
                    if ($ftpDriver) {
                        $remoteFileName = basename($dbFile);
                        $this->addLog("🌐 در حال آپلود دیتابیس به FTP...");
                        $ftpDriver->upload($dbFile, $remoteDir . '/' . $remoteFileName);
                        $retentionDays = intval($settings['db_remote_retention_days'] ?? 3);
                        $ftpDriver->cleanOldBackupsByDays($remoteDir, $retentionDays, 'db_');
                        $ftpUploaded = true;
                    }

                    // Local Retention for DB
                    if (empty($settings['local_enabled'])) {
                        File::delete($dbFile);
                    } else {
                        $localDays = intval($settings['db_local_retention_days'] ?? 3);
                        $cutoff = now()->subDays($localDays)->getTimestamp();
                        $deleted = 0;
                        foreach (File::files($backupDir) as $f) {
                            if (str_starts_with($f->getFilename(), 'db_') && $f->getMTime() < $cutoff) {
                                File::delete($f->getPathname());
                                $deleted++;
                            }
                        }
                        $this->addLog("🧹 پاک‌سازی دیتابیس‌های قدیمی محلی (بیشتر از {$localDays} روز): {$deleted} فایل حذف شد.");
                    }

                } catch (\Exception $e) {
                    $this->addLog("❌ خطا در بکاپ دیتابیس: " . $e->getMessage());
                    $hasError = true;
                }
            }
        }

        // 2. BACKUP FILES (if requested and enabled)
        if (($type === 'all' || $type === 'files') && !$this->option('ftp-only')) {
            $filesEnabled = $settings['files_enabled'] ?? ($settings['include_files'] ?? true);

            if ($filesEnabled) {
                $this->addLog("📁 بکاپ‌گیری از فایل‌های سورس پروژه (حذف خودکار vendor و node_modules)...");
                $filesArchive = "{$backupDir}/files_{$service->name}_{$timestamp}.tar.gz";

                try {
                    if (!$isDryRun) {
                        $process = new Process([
                            'tar', '-czf', $filesArchive, '-C', $service->path,
                            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '--exclude=vendor',
                            '--exclude=storage/app/backups', '--exclude=storage/app/mock-services',
                            '--exclude=storage/framework/cache', '--exclude=storage/framework/sessions',
                            '--exclude=storage/framework/views', '--exclude=storage/logs', '.'
                        ]);
                        $process->setTimeout(600);
                        $process->mustRun();
                    } else {
                        file_put_contents($filesArchive, "mock");
                    }

                    $sizeMb = round(filesize($filesArchive) / 1024 / 1024, 2);
                    $totalSizeMb += $sizeMb;
                    $this->addLog("✅ فایل‌های پروژه با موفقیت فشرده شدند ({$sizeMb} MB).");

                    // FTP Upload for Files
                    if ($ftpDriver) {
                        $remoteFileName = basename($filesArchive);
                        $this->addLog("🌐 در حال آپلود فایل‌های پروژه به FTP...");
                        $ftpDriver->upload($filesArchive, $remoteDir . '/' . $remoteFileName);
                        $retentionDays = intval($settings['files_remote_retention_days'] ?? 14);
                        $ftpDriver->cleanOldBackupsByDays($remoteDir, $retentionDays, 'files_');
                        $ftpUploaded = true;
                    }

                    // Local Retention for Files
                    if (empty($settings['local_enabled'])) {
                        File::delete($filesArchive);
                    } else {
                        $localDays = intval($settings['files_local_retention_days'] ?? 14);
                        $cutoff = now()->subDays($localDays)->getTimestamp();
                        $deleted = 0;
                        foreach (File::files($backupDir) as $f) {
                            if (str_starts_with($f->getFilename(), 'files_') && $f->getMTime() < $cutoff) {
                                File::delete($f->getPathname());
                                $deleted++;
                            }
                        }
                        $this->addLog("🧹 پاک‌سازی فایل‌های قدیمی محلی (بیشتر از {$localDays} روز): {$deleted} فایل حذف شد.");
                    }

                } catch (\Exception $e) {
                    $this->addLog("❌ خطا در بکاپ فایل‌ها: " . $e->getMessage());
                    $hasError = true;
                }
            }
        }

        // Close FTP
        if ($ftpDriver) {
            $this->runLogs = array_merge($this->runLogs, $ftpDriver->getLogs());
            $ftpDriver->disconnect();
        }

        // Permissions fix
        if (!$isMock) {
            @exec("chown -R www-data:www-data " . escapeshellarg($backupDir) . " 2>/dev/null");
            @exec("chown www-data:www-data " . escapeshellarg($settingsPath) . " 2>/dev/null");
        }

        // Update settings stats
        $settings['last_backup'] = now()->toDateTimeString();
        $settings['last_backup_status'] = $hasError ? 'با خطا' : 'موفق';
        $settings['last_backup_size_mb'] = $totalSizeMb;
        $settings['last_ftp_uploaded'] = $ftpUploaded;

        // Save Logs
        $logDir = storage_path('app/backup-logs');
        File::ensureDirectoryExists($logDir);
        $logFile = $logDir . "/service_{$service->id}_" . date('Y-m-d') . ".log";
        $logContent = implode("\n", $this->runLogs) . "\n-------------------------\n";
        File::append($logFile, $logContent);
        
        if (!$isMock) {
            @exec("chown -R www-data:www-data " . escapeshellarg($logDir) . " 2>/dev/null");
        }

        $settings['backup_log'] = $logFile;
        File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

        foreach ($this->runLogs as $l) {
            $this->info($l);
        }

        return $hasError ? 1 : 0;
    }

    private function addLog(string $message): void
    {
        $entry = '[' . now()->format('H:i:s') . '] ' . $message;
        $this->runLogs[] = $entry;
    }
}
