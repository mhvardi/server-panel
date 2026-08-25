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

        $settings = json_decode(File::get($settingsPath), true) ?: [];

        if (empty($settings['enabled'])) {
            $this->info("Backup is disabled for service {$service->name}. Skipping.");
            return 0;
        }

        $this->info("Starting backup for service: {$service->name} ({$service->domain})");
        Log::info("Starting backup for service: {$service->name} ({$service->domain})");

        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $finalArchive = "{$backupDir}/backup_{$timestamp}.tar.gz";

        $tempFiles = [];

        try {
            // Backup files
            if (!empty($settings['include_files'])) {
                $this->info('Backing up files...');
                $filesArchive = "{$backupDir}/files_{$timestamp}.tar";
                $tempFiles[] = $filesArchive;

                $process = new Process([
                    'tar',
                    '-cf',
                    $filesArchive,
                    '-C',
                    $service->path,
                    '--exclude=.backup',
                    '--exclude=.git',
                    '--exclude=node_modules',
                    '.'
                ]);
                $process->setTimeout(300);
                $process->mustRun();
                $this->info('File backup created.');
            }

            // Backup database
            if (!empty($settings['include_db']) && !empty($settings['db_name'])) {
                $this->info('Backing up database: ' . $settings['db_name']);
                $dbDump = "{$backupDir}/db_{$timestamp}.sql";
                $tempFiles[] = $dbDump;

                $dbHost = config('database.connections.mysql.host', '127.0.0.1');
                $dbPort = config('database.connections.mysql.port', '3306');
                $dbUser = config('database.connections.mysql.username', 'root');
                $dbPass = config('database.connections.mysql.password', '');

                $dumpCmd = ['mysqldump', '-h', $dbHost, '-P', (string)$dbPort, '-u', $dbUser];
                if ($dbPass !== '' && $dbPass !== null) {
                    $dumpCmd[] = '-p' . $dbPass;
                }
                $dumpCmd[] = $settings['db_name'];

                $process = new Process($dumpCmd);
                $process->setTimeout(300);
                $process->setOutputFile($dbDump);
                $process->mustRun();
                $this->info('Database backup created.');
            }

            if (empty($tempFiles)) {
                $this->warn('Neither files nor database was selected for backup.');
                return 0;
            }

            // Create final archive
            $this->info('Creating final archive...');
            $tarCmd = ['tar', '-czf', $finalArchive, '-C', $backupDir];
            foreach ($tempFiles as $tf) {
                $tarCmd[] = basename($tf);
            }

            $process = new Process($tarCmd);
            $process->setTimeout(300);
            $process->mustRun();

            $this->info("Final archive created: {$finalArchive}");

            // Upload to remote FTP if enabled
            if (!empty($settings['remote_enabled'])) {
                $this->info('Uploading to remote FTP server...');
                $this->uploadToFtp($service, $settings, $finalArchive);
            }

            // Cleanup old local backups
            $localRetentionDays = intval($settings['local_retention'] ?? 7);
            $this->cleanupLocalBackups($backupDir, $localRetentionDays);

            $settings['last_backup'] = now()->toDateTimeString();
            $settings['last_backup_status'] = 'موفق';
            File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            $this->info("Backup for service {$service->name} completed successfully.");
            Log::info("Backup for service {$service->name} completed successfully.");

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            Log::error("Backup failed for service {$service->name}: " . $e->getMessage());

            $settings['last_backup_status'] = 'خطا: ' . $e->getMessage();
            File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
            return 1;
        } finally {
            File::delete($tempFiles);
        }

        return 0;
    }

    /**
     * Upload backup to remote FTP and keep only 2 (or configured) recent backups for this domain.
     */
    private function uploadToFtp(Service $service, array $settings, string $localFilePath): void
    {
        $host = $settings['remote_host'] ?? '';
        $user = $settings['remote_user'] ?? '';
        $pass = $settings['remote_password'] ?? '';
        $baseRemotePath = rtrim($settings['remote_path'] ?? '/public_html', '/');
        
        if (empty($host) || empty($user)) {
            throw new \RuntimeException('اطلاعات هاست و نام کاربری FTP ناقص است.');
        }

        $conn = @ftp_connect($host, 21, 15);
        if (!$conn) {
            throw new \RuntimeException("اتصال به سرور FTP ({$host}) برقرار نشد.");
        }

        try {
            $login = @ftp_login($conn, $user, $pass);
            if (!$login) {
                throw new \RuntimeException("نام کاربری یا رمز عبور FTP نامعتبر است.");
            }

            ftp_pasv($conn, true);

            // Folder based on domain name (or service name fallback)
            $folderName = $service->domain ?: $service->name;
            $remoteTargetDir = $baseRemotePath . '/' . $folderName;

            // Ensure directory structure exists on FTP
            $parts = array_filter(explode('/', $remoteTargetDir));
            $currPath = '';
            foreach ($parts as $part) {
                $currPath .= '/' . $part;
                @ftp_mkdir($conn, $currPath);
            }

            // Upload the backup file
            $remoteFileName = basename($localFilePath);
            $remoteDestination = $remoteTargetDir . '/' . $remoteFileName;

            $this->info("Uploading to FTP: {$remoteDestination} ...");
            if (!@ftp_put($conn, $remoteDestination, $localFilePath, FTP_BINARY)) {
                throw new \RuntimeException("خطا در ارسال فایل بکاپ به سرور FTP ({$remoteDestination})");
            }
            $this->info("FTP upload completed.");

            // Retention: keep only latest 2 backups (or remote_retention count) in this domain folder
            $keepCount = max(1, intval($settings['remote_retention'] ?? 2));
            $this->cleanupRemoteFtpBackups($conn, $remoteTargetDir, $keepCount);

        } finally {
            @ftp_close($conn);
        }
    }

    /**
     * Cleanup older backups on remote FTP so only the newest $keepCount backups remain.
     */
    private function cleanupRemoteFtpBackups($conn, string $remoteDir, int $keepCount): void
    {
        $rawFiles = @ftp_nlist($conn, $remoteDir) ?: [];
        $backupFiles = [];

        foreach ($rawFiles as $file) {
            $base = basename($file);
            if ($base === '.' || $base === '..') {
                continue;
            }

            if (preg_match('/\.(tar\.gz|zip|sql\.gz|tar)$/i', $base)) {
                $filePath = (strpos($file, '/') === 0) ? $file : $remoteDir . '/' . $file;
                $mtime = @ftp_mdtm($conn, $filePath);
                if ($mtime === -1) {
                    $mtime = 0;
                }
                $backupFiles[] = [
                    'path' => $filePath,
                    'name' => $base,
                    'mtime' => $mtime
                ];
            }
        }

        // Sort descending: newest first
        usort($backupFiles, function ($a, $b) {
            if ($a['mtime'] === $b['mtime']) {
                return strcmp($b['name'], $a['name']);
            }
            return $b['mtime'] - $a['mtime'];
        });

        if (count($backupFiles) > $keepCount) {
            $toDelete = array_slice($backupFiles, $keepCount);
            foreach ($toDelete as $f) {
                $this->info("Deleting old remote FTP backup: " . $f['name']);
                @ftp_delete($conn, $f['path']);
            }
        }
    }

    private function cleanupLocalBackups(string $directory, int $retentionDays)
    {
        if ($retentionDays <= 0) {
            return;
        }

        $files = File::files($directory);
        $cutoff = now()->subDays($retentionDays);

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff->getTimestamp()) {
                File::delete($file->getPathname());
                $this->info("Deleted old local backup: " . $file->getFilename());
            }
        }
    }
}
