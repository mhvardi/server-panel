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
    protected $signature = 'backup:run-service {service_id} {--dry-run} {--ftp-only}';
    protected $description = 'Run a backup for a specific service based on its JSON settings.';

    private array $runLogs = [];

    public function handle()
    {
        $serviceId = $this->argument('service_id');
        $service = Service::find($serviceId);

        if (!$service) {
            $this->error("Service with ID {$serviceId} not found.");
            return 1;
        }

        $settingsPath = $service->path . '/.backup/settings.json';
        
        // Handle MOCK mode for local testing
        $isMock = env('BACKUP_MOCK_ENABLED', false);
        $mockBase = env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services'));
        
        if ($isMock) {
            $service->path = rtrim($mockBase, '/') . '/' . $service->domain;
            $settingsPath = $service->path . '/.backup/settings.json';
            File::ensureDirectoryExists($service->path . '/.backup');
        }

        if (!File::exists($settingsPath)) {
            // Default mock settings if running mock and it doesn't exist
            if ($isMock) {
                $settings = [
                    'enabled' => true,
                    'include_files' => true,
                    'include_db' => false,
                    'local_enabled' => true,
                    'local_retention_days' => 7,
                    'remote_enabled' => true,
                    'remote_retention_days' => 7,
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

        if (empty($settings['enabled']) && !$this->option('dry-run')) {
            $this->info("Backup is disabled for service {$service->name}. Skipping.");
            return 0;
        }

        $this->addLog("🚀 شروع عملیات بکاپ برای سرویس: {$service->name} ({$service->domain})");

        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $finalArchive = "{$backupDir}/backup_{$timestamp}.tar.gz";

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->addLog("⚠️ اجرای حالت Dry-Run (شبیه‌سازی)");
        }

        $tempFiles = [];
        $hasError = false;

        try {
            // 1. BACKUP CREATION
            if (!$this->option('ftp-only')) {
                // Backup files
                if (!empty($settings['include_files'])) {
                    $this->addLog("📁 در حال فشرده‌سازی فایل‌های پروژه...");
                    $filesArchive = "{$backupDir}/files_{$timestamp}.tar";
                    $tempFiles[] = $filesArchive;

                    if (!$isDryRun) {
                        $process = new Process([
                            'tar', '-cf', $filesArchive, '-C', $service->path, 
                            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '.'
                        ]);
                        $process->setTimeout(300);
                        $process->mustRun();
                    }
                    $this->addLog("✅ فایل‌های پروژه فشرده شدند.");
                }

                // Backup database
                $dbName = $service->getDatabaseName();
                if (!empty($settings['include_db']) && !empty($dbName)) {
                    $this->addLog("🗄️ در حال بکاپ از پایگاه‌داده: " . $dbName);
                    $dbDump = "{$backupDir}/db_{$timestamp}.sql";
                    $tempFiles[] = $dbDump;

                    if (!$isDryRun && !$isMock) {
                        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
                        $dbPort = config('database.connections.mysql.port', '3306');
                        $dbUser = config('database.connections.mysql.username', 'root');
                        $dbPass = config('database.connections.mysql.password', '');

                        $dumpCmd = ['mysqldump', '-h', $dbHost, '-P', (string)$dbPort, '-u', $dbUser];
                        if ($dbPass !== '' && $dbPass !== null) {
                            $dumpCmd[] = '-p' . $dbPass;
                        }
                        $dumpCmd[] = $dbName;

                        $process = new Process($dumpCmd);
                        $process->setTimeout(300);
                        $process->setOutputFile($dbDump);
                        $process->mustRun();
                    }
                    $this->addLog("✅ پایگاه‌داده بکاپ گرفته شد.");
                }

                if (empty($tempFiles) && !$isDryRun) {
                    throw new \Exception("هیچ منبعی (فایل یا دیتابیس) برای بکاپ انتخاب نشده است.");
                }

                $this->addLog("📦 در حال ساخت آرشیو نهایی...");
                if (!$isDryRun) {
                    $tarCmd = ['tar', '-czf', $finalArchive, '-C', $backupDir];
                    foreach ($tempFiles as $tf) {
                        $tarCmd[] = basename($tf);
                    }
                    $process = new Process($tarCmd);
                    $process->setTimeout(300);
                    $process->mustRun();
                } else {
                    file_put_contents($finalArchive, "dummy"); // mock file for FTP upload
                }
                
                $sizeMb = round(filesize($finalArchive) / 1024 / 1024, 2);
                $this->addLog("✅ آرشیو نهایی آماده شد ({$sizeMb} MB)");
                $settings['last_backup_size_mb'] = $sizeMb;
            } else {
                // Find latest backup to upload in ftp-only mode
                $files = glob("{$backupDir}/backup_*.tar.gz");
                if (empty($files)) {
                    throw new \Exception("هیچ فایل بکاپی برای آپلود یافت نشد.");
                }
                rsort($files);
                $finalArchive = $files[0];
                $this->addLog("🔄 حالت FTP-Only: انتخاب آخرین فایل بکاپ: " . basename($finalArchive));
            }

            // 2. FTP UPLOAD
            $ftpUploaded = false;
            if (!empty($settings['remote_enabled'])) {
                $this->addLog("🌐 شروع عملیات FTP...");
                try {
                    $ftp = new FtpBackupDriver(
                        $settings['remote_host'], 21, 
                        $settings['remote_user'], $settings['remote_password']
                    );
                    $ftp->connect();
                    
                    $remoteDir = rtrim($settings['remote_path'] ?? '/public_html', '/') . '/' . ($service->domain ?: $service->name);
                    $ftp->ensureRemoteDir($remoteDir);
                    
                    $remoteFileName = basename($finalArchive);
                    $ftp->upload($finalArchive, $remoteDir . '/' . $remoteFileName);
                    
                    $remoteDays = intval($settings['remote_retention_days'] ?? 7);
                    $ftp->cleanOldBackupsByDays($remoteDir, $remoteDays);
                    $ftp->disconnect();
                    
                    $this->runLogs = array_merge($this->runLogs, $ftp->getLogs());
                    $ftpUploaded = true;
                    $this->addLog("✅ عملیات FTP با موفقیت به پایان رسید.");
                } catch (\Exception $e) {
                    $this->addLog("❌ خطای FTP: " . $e->getMessage());
                    $hasError = true;
                    if (isset($ftp)) {
                        $this->runLogs = array_merge($this->runLogs, $ftp->getLogs());
                    }
                }
            }

            // 3. LOCAL RETENTION
            if (empty($settings['local_enabled']) && !$this->option('ftp-only')) {
                // If local backup is not needed, delete the created archive after FTP upload
                $this->addLog("🗑️ تنظیمات نگهداری لوکال غیرفعال است. فایل حذف می‌شود.");
                File::delete($finalArchive);
            } else {
                $localDays = intval($settings['local_retention_days'] ?? 7);
                $this->addLog("🧹 در حال بررسی و پاک‌سازی بکاپ‌های قدیمی لوکال (بیشتر از {$localDays} روز)...");
                $cutoff = now()->subDays($localDays)->getTimestamp();
                $deleted = 0;
                foreach (File::files($backupDir) as $file) {
                    if (str_starts_with($file->getFilename(), 'backup_') && $file->getMTime() < $cutoff) {
                        File::delete($file->getPathname());
                        $deleted++;
                    }
                }
                $this->addLog("✅ پاک‌سازی لوکال انجام شد ({$deleted} فایل حذف شد).");
            }

            // 4. FIX PERMISSIONS (Since command might be run as root)
            if (!$isMock) {
                exec("chown -R www-data:www-data " . escapeshellarg($backupDir) . " 2>/dev/null");
                exec("chown www-data:www-data " . escapeshellarg($settingsPath) . " 2>/dev/null");
            }

            if ($isDryRun) {
                File::delete($finalArchive); // cleanup mock file
            }

            $settings['last_backup'] = now()->toDateTimeString();
            $settings['last_backup_status'] = $hasError ? 'با خطا' : 'موفق';
            $settings['last_ftp_uploaded'] = $ftpUploaded;
            
        } catch (\Exception $e) {
            $this->addLog("❌ خطای غیرمنتظره: " . $e->getMessage());
            $settings['last_backup_status'] = 'شکست کامل';
            $hasError = true;
        } finally {
            if (is_array($tempFiles)) {
                foreach ($tempFiles as $tf) {
                    if (File::exists($tf)) File::delete($tf);
                }
            }
        }

        // Save Logs
        $logDir = storage_path('app/backup-logs');
        File::ensureDirectoryExists($logDir);
        $logFile = $logDir . "/service_{$service->id}_" . date('Y-m-d') . ".log";
        $logContent = implode("\n", $this->runLogs) . "\n-------------------------\n";
        File::append($logFile, $logContent);
        
        if (!$isMock) {
            exec("chown -R www-data:www-data " . escapeshellarg($logDir) . " 2>/dev/null");
        }

        $settings['backup_log'] = $logFile;
        File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

        // Output to console
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
