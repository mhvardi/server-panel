<?php

namespace App\Services;

use App\Models\BackupTask;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * BackupTaskService performs the actual backup operations for a BackupTask.
 *
 * It supports backing up files (via tar/gzip) and MySQL databases (via mysqldump).
 * When remote_enabled is true, it uploads the generated archive to a remote FTP server.
 * Logs are written to storage_path('backup-logs') and meta information to storage_path('backup-meta').
 */
class BackupTaskService
{
    /**
     * Execute a backup for the given task.
     *
     * This method returns an array with keys 'status' (success|error) and 'message'.
     */
    public function run(BackupTask $task): array
    {
        $start = now();
        $taskName = Str::slug($task->name, '_');
        $timestamp = $start->format('Ymd_His');
        // directory /var/www/service or override via service_path
        $serviceDir = '/var/www/service/' . $task->service_path;

        // Ensure backup directories exist
        $logDir = storage_path('backup-logs');
        $archiveDir = storage_path('backups');
        @mkdir($logDir, 0755, true);
        @mkdir($archiveDir, 0755, true);

        $logFile = $logDir . "/{$taskName}_{$timestamp}.log";
        $archivePath = $archiveDir . "/{$taskName}_{$timestamp}.tar.gz";
        $dbDumpPath = $archiveDir . "/{$taskName}_{$timestamp}_db.sql.gz";

        $logOutput = [];

        // Backup files
        if ($task->files_enabled) {
            // Use tar to create archive
            $cmd = "tar -czf " . escapeshellarg($archivePath) . " -C " . escapeshellarg($serviceDir) . " .";
            $res = Process::run($cmd);
            $logOutput[] = "Files backup command: {$cmd}";
            if ($res->failed()) {
                $logOutput[] = "Error during files backup: " . $res->errorOutput();
                return $this->writeLogAndResult($task, $start, 'error', $logFile, $logOutput);
            }
            $logOutput[] = "Files archive created at {$archivePath}";
        }

        // Backup database
        if ($task->db_enabled && $task->db_name) {
            $dbCmd = "mysqldump " . escapeshellarg($task->db_name) . " | gzip > " . escapeshellarg($dbDumpPath);
            $logOutput[] = "DB backup command: {$dbCmd}";
            $res = Process::run($dbCmd);
            if ($res->failed()) {
                $logOutput[] = "Error during DB backup: " . $res->errorOutput();
                return $this->writeLogAndResult($task, $start, 'error', $logFile, $logOutput);
            }
            $logOutput[] = "DB dump created at {$dbDumpPath}";
        }

        // If remote copy requested
        if ($task->remote_enabled) {
            // Compose ftp upload command using lftp
            $filesToUpload = [];
            if ($task->files_enabled) {
                $filesToUpload[] = $archivePath;
            }
            if ($task->db_enabled && $task->db_name) {
                $filesToUpload[] = $dbDumpPath;
            }
            foreach ($filesToUpload as $f) {
                $fname = basename($f);
                $remoteCmd = "lftp -e \"set ftp:ssl-allow no; put " . escapeshellarg($f) . " -o " . escapeshellarg($task->remote_path . '/' . $fname) . "; bye\" -u " . escapeshellarg($task->remote_user) . "," . escapeshellarg($task->remote_password) . " " . escapeshellarg($task->remote_host);
                $logOutput[] = "Uploading {$f} to remote: {$remoteCmd}";
                $res = Process::run($remoteCmd);
                if ($res->failed()) {
                    $logOutput[] = "Error during remote upload: " . $res->errorOutput();
                    return $this->writeLogAndResult($task, $start, 'error', $logFile, $logOutput);
                }
                $logOutput[] = "Uploaded {$fname} to remote server";
            }
        }

        // Clean up old backups
        $this->cleanupOldBackups($archiveDir, $task, $logOutput);

        return $this->writeLogAndResult($task, $start, 'success', $logFile, $logOutput);
    }

    /**
     * Clean up local backup files older than retention period.
     */
    protected function cleanupOldBackups(string $archiveDir, BackupTask $task, array & $logOutput): void
    {
        $retention = $task->local_retention_days;
        if ($retention <= 0) {
            return;
        }
        $files = glob($archiveDir . '/' . Str::slug($task->name, '_') . '_*.tar.gz');
        $cutoff = now()->subDays($retention)->timestamp;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $logOutput[] = "Removed old backup: {$file}";
            }
        }
    }

    /**
     * Write log output to file and update task meta.
     */
    protected function writeLogAndResult(BackupTask $task, $start, string $status, string $logFile, array $logOutput): array
    {
        // Write log file
        @file_put_contents($logFile, implode(PHP_EOL, $logOutput));

        $task->last_run_at = now();
        $task->last_status = $status;
        $task->last_log_path = $logFile;
        $task->save();

        return ['status' => $status, 'message' => $status === 'success' ? 'Backup completed' : 'Backup failed'];
    }
}