<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CronJobService;
use App\Services\FtpBackupDriver;
use App\Jobs\RunServiceBackupJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class BackupTaskController extends Controller
{
    public function __construct(private readonly CronJobService $cron) {}

    public function index()
    {
        $services = Service::orderByRaw("CASE WHEN type = 'main' THEN 0 ELSE 1 END")->orderBy('id')->get()->map(function ($service) {
            $settings = $this->getBackupSettings($service);
            $service->db_enabled = $settings['db_enabled'] ?? false;
            $service->files_enabled = $settings['files_enabled'] ?? false;
            $service->backup_enabled = $service->db_enabled || $service->files_enabled;
            $service->last_backup = !empty($settings['last_backup']) ? Carbon::parse($settings['last_backup']) : null;
            $service->local_enabled = $settings['local_enabled'] ?? true;
            $service->remote_enabled = $settings['remote_enabled'] ?? false;
            $service->last_backup_size = $settings['last_backup_size_mb'] ?? null;
            $service->last_backup_status = $settings['last_backup_status'] ?? 'نامشخص';
            return $service;
        });

        $queueCount = 0;
        try {
            $queueCount = DB::table('jobs')->where('queue', 'backups')->count();
        } catch (\Exception $e) {}

        return view('backup_tasks.index', compact('services', 'queueCount'));
    }

    public function settings(Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $recent_backups = $this->getRecentBackups($service);
        
        $last_backup_status = [
            'status' => $settings['last_backup_status'] ?? 'نامشخص',
            'date' => !empty($settings['last_backup']) ? Carbon::parse($settings['last_backup'])->toDateTimeString() : 'هرگز',
            'size' => $settings['last_backup_size_mb'] ?? 0,
            'ftp_uploaded' => $settings['last_ftp_uploaded'] ?? false,
        ];

        $queueCount = 0;
        try {
            $queueCount = DB::table('jobs')->where('queue', 'backups')->count();
        } catch (\Exception $e) {}

        return view('backup_tasks.settings', compact('service', 'settings', 'recent_backups', 'last_backup_status', 'queueCount'));
    }

    public function saveSettings(Request $request, Service $service)
    {
        $data = $request->validate([
            // Database Backup Settings
            'db_enabled' => 'required|boolean',
            'db_cron_preset' => 'required|string',
            'db_cron_custom' => 'nullable|string',
            'db_local_retention_days' => 'required|integer|min:1',
            'db_remote_retention_days' => 'required|integer|min:1',

            // Files Backup Settings
            'files_enabled' => 'required|boolean',
            'files_cron_preset' => 'required|string',
            'files_cron_custom' => 'nullable|string',
            'files_local_retention_days' => 'required|integer|min:1',
            'files_remote_retention_days' => 'required|integer|min:1',

            // Storage Destinations
            'local_enabled' => 'required|boolean',
            'remote_enabled' => 'required|boolean',
            'remote_host' => 'nullable|string|required_if:remote_enabled,true',
            'remote_user' => 'nullable|string|required_if:remote_enabled,true',
            'remote_password' => 'nullable|string',
            'remote_path' => 'nullable|string|required_if:remote_enabled,true',
        ]);

        // Process DB Cron
        if ($data['db_cron_preset'] === 'custom') {
            $data['db_cron_expression'] = $data['db_cron_custom'];
        } else {
            $data['db_cron_expression'] = $data['db_cron_preset'];
        }
        unset($data['db_cron_preset'], $data['db_cron_custom']);

        // Process Files Cron
        if ($data['files_cron_preset'] === 'custom') {
            $data['files_cron_expression'] = $data['files_cron_custom'];
        } else {
            $data['files_cron_expression'] = $data['files_cron_preset'];
        }
        unset($data['files_cron_preset'], $data['files_cron_custom']);

        $existingSettings = $this->getBackupSettings($service);

        // Retain password if empty
        if (empty($data['remote_password']) && !empty($existingSettings['remote_password'])) {
            $data['remote_password'] = $existingSettings['remote_password'];
        }

        // Preserve stats
        $data['last_backup'] = $existingSettings['last_backup'] ?? null;
        $data['last_backup_status'] = $existingSettings['last_backup_status'] ?? null;
        $data['last_backup_size_mb'] = $existingSettings['last_backup_size_mb'] ?? null;
        $data['last_ftp_uploaded'] = $existingSettings['last_ftp_uploaded'] ?? null;
        $data['backup_log'] = $existingSettings['backup_log'] ?? null;

        $saved = $this->saveBackupSettings($service, $data);
        if (!$saved) {
            return back()->with('error', 'خطا در ذخیره تنظیمات: عدم دسترسی (Permission Denied).');
        }

        $this->updateCronJobs($service, $data);

        return redirect()->route('backup_tasks.settings', $service->id)->with('success', 'تنظیمات پشتیبان‌گیری هوشمند با موفقیت ذخیره شد.');
    }

    /**
     * Run backup via Background Queue (Sequential, Non-blocking, No 504 Timeout)
     */
    public function run(Service $service, Request $request)
    {
        $type = $request->type ?? 'all';

        // Synchronous debug option
        if ($request->has('sync')) {
            try {
                $args = ['service_id' => $service->id, '--type' => $type];
                if ($request->has('ftp_only')) $args['--ftp-only'] = true;
                if ($request->has('dry_run')) $args['--dry-run'] = true;
                Artisan::call('backup:run-service', $args);
                return back()->with('success', 'عملیات بکاپ همگام انجام شد.');
            } catch (\Exception $e) {
                return back()->with('error', 'خطا: ' . $e->getMessage());
            }
        }

        // Dispatch to Sequential Queue
        RunServiceBackupJob::dispatch($service, $type)->onQueue('backups');

        $typeLabels = ['db' => 'پایگاه‌داده', 'files' => 'فایل‌های سورس', 'all' => 'کامل (دیتابیس + فایل‌ها)'];
        return back()->with('success', "عملیات بکاپ «{$typeLabels[$type]}» با موفقیت در صف پس‌زمینه قرار گرفت و بدون فشار به سرور و پهنای باند، به نوبت اجرا خواهد شد.");
    }

    /**
     * Manual Backup Handler
     */
    public function manualBackup(Request $request, Service $service)
    {
        $request->validate([
            'target' => 'required|in:download,local,ftp',
            'type' => 'required|in:db,files,full',
        ]);

        $target = $request->target;
        $type = $request->type;
        $settings = $this->getBackupSettings($service);
        $servicePath = $this->getServicePath($service);
        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $timestamp = now()->format('Y-m-d_H-i-s');

        if ($target === 'ftp') {
            if (empty($settings['remote_enabled']) || empty($settings['remote_host']) || empty($settings['remote_user'])) {
                return back()->with('error', 'ذخیره‌سازی ریموت (FTP) فعال یا کامل تنظیم نشده است. ابتدا در تب تنظیمات، FTP را فعال و اطلاعات آن را ذخیره کنید.');
            }
        }

        $typeLabels = ['db' => 'پایگاه‌داده', 'files' => 'فایل‌های سورس', 'full' => 'کامل (دیتابیس + فایل‌ها)'];
        $targetLabels = ['local' => 'سرور محلی', 'ftp' => 'هاست FTP مرکزی'];

        // If target is Local or FTP, dispatch to queue so web connection never hangs (No 504 Timeout)
        if ($target === 'local' || $target === 'ftp') {
            RunServiceBackupJob::dispatch($service, $type, $target)->onQueue('backups');
            return back()->with('success', "درخواست بکاپ «{$typeLabels[$type]}» ({$targetLabels[$target]}) با موفقیت در صف پس‌زمینه قرار گرفت و به نوبت انجام می‌شود.");
        }

        // Direct Download: Generate directly and stream to browser
        try {
            $filePath = null;
            $fileName = null;

            if ($type === 'db') {
                $dbName = $service->getDatabaseName();
                if (empty($dbName)) {
                    return back()->with('error', 'نام پایگاه‌داده در فایل .env سرویس یافت نشد.');
                }
                $fileName = 'db_' . $dbName . '_' . $timestamp . '.sql.gz';
                $filePath = $backupDir . '/' . $fileName;
                $this->createDbBackup($dbName, $filePath);
            } elseif ($type === 'files') {
                $fileName = 'files_' . $service->name . '_' . $timestamp . '.tar.gz';
                $filePath = $backupDir . '/' . $fileName;
                $this->createFilesBackup($servicePath, $filePath);
            } elseif ($type === 'full') {
                $fileName = 'backup_full_' . $service->name . '_' . $timestamp . '.tar.gz';
                $filePath = $backupDir . '/' . $fileName;
                $this->createFullBackup($service, $filePath);
            }

            if (!File::exists($filePath) || filesize($filePath) === 0) {
                throw new \Exception('فایل بکاپ ایجاد نشد یا خالی است.');
            }

            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در دانلود بکاپ: ' . $e->getMessage());
        }
    }

    public function queueStatus()
    {
        $pending = 0;
        try {
            $pending = DB::table('jobs')->where('queue', 'backups')->count();
        } catch (\Exception $e) {}

        return response()->json([
            'pending_jobs' => $pending,
            'message' => $pending > 0 ? "تعداد {$pending} بکاپ در صف انتظار قرار دارد." : "صف بکاپ خالی است.",
        ]);
    }

    private function createDbBackup(string $dbName, string $destPath): void
    {
        $isMock = env('BACKUP_MOCK_ENABLED', false);
        if ($isMock) {
            $sql = "-- Mock DB Backup for {$dbName}\n-- Created: " . date('Y-m-d H:i:s') . "\nCREATE TABLE IF NOT EXISTS `sample` (`id` int(11));\n";
            file_put_contents($destPath, gzencode($sql));
            return;
        }

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');
        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($destPath);
        
        $process = \Illuminate\Support\Facades\Process::run($cmd);
        if (!$process->successful()) {
            throw new \Exception('خطا در اجرای mysqldump: ' . $process->errorOutput());
        }
    }

    private function createFilesBackup(string $servicePath, string $destPath): void
    {
        if (!is_dir($servicePath)) {
            throw new \Exception("پوشه سرویس یافت نشد: {$servicePath}");
        }

        $process = new Process([
            'tar', '-czf', $destPath, '-C', $servicePath,
            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '--exclude=vendor', '.'
        ]);
        $process->setTimeout(600);
        $process->mustRun();
    }

    private function createFullBackup(Service $service, string $destPath): void
    {
        $servicePath = $this->getServicePath($service);
        $backupDir = storage_path('app/backups/' . $service->id);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $tempFiles = [];

        // 1. Files tar (excluding vendor & node_modules)
        $filesTar = "{$backupDir}/temp_files_{$timestamp}.tar";
        $tempFiles[] = $filesTar;
        $process = new Process([
            'tar', '-cf', $filesTar, '-C', $servicePath,
            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '--exclude=vendor', '.'
        ]);
        $process->setTimeout(600);
        $process->mustRun();

        // 2. DB dump
        $dbName = $service->getDatabaseName();
        if (!empty($dbName)) {
            $dbDump = "{$backupDir}/temp_db_{$timestamp}.sql";
            $tempFiles[] = $dbDump;
            $isMock = env('BACKUP_MOCK_ENABLED', false);
            if ($isMock) {
                file_put_contents($dbDump, "-- Mock DB Backup for {$dbName}\n");
            } else {
                $dbHost = config('database.connections.mysql.host', '127.0.0.1');
                $dbPort = config('database.connections.mysql.port', '3306');
                $dbUser = config('database.connections.mysql.username', 'root');
                $dbPass = config('database.connections.mysql.password', '');
                $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
                $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " > " . escapeshellarg($dbDump);
                $p = \Illuminate\Support\Facades\Process::run($cmd);
                if (!$p->successful()) {
                    throw new \Exception('خطا در mysqldump: ' . $p->errorOutput());
                }
            }
        }

        // 3. Final tar.gz archive
        try {
            $tarCmd = ['tar', '-czf', $destPath, '-C', $backupDir];
            foreach ($tempFiles as $tf) {
                $tarCmd[] = basename($tf);
            }
            $p = new Process($tarCmd);
            $p->setTimeout(600);
            $p->mustRun();
        } finally {
            foreach ($tempFiles as $tf) {
                if (File::exists($tf)) File::delete($tf);
            }
        }
    }

    public function getLog(Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $logFile = $settings['backup_log'] ?? null;
        
        if ($logFile && File::exists($logFile) && is_readable($logFile)) {
            $content = File::get($logFile);
            $parts = explode("-------------------------", $content);
            $lastLog = trim($parts[count($parts) - 2] ?? $content);
            return response()->json(['log' => $lastLog]);
        }
        return response()->json(['log' => 'لاگی یافت نشد.']);
    }

    public function testFtp(Request $request)
    {
        $request->validate([
            'remote_host' => 'required|string',
            'remote_user' => 'required|string',
            'remote_password' => 'required|string',
        ]);
        
        $result = FtpBackupDriver::testConnection(
            $request->remote_host, 
            $request->remote_user, 
            $request->remote_password
        );
        return response()->json($result);
    }
    
    public function backupDatabaseNow(Service $service)
    {
        $request = new Request(['target' => 'download', 'type' => 'db']);
        return $this->manualBackup($request, $service);
    }

    public function backupFilesNow(Service $service)
    {
        $request = new Request(['target' => 'download', 'type' => 'files']);
        return $this->manualBackup($request, $service);
    }

    public function downloadBackup(Service $service, $filename)
    {
        $filePath = storage_path('app/backups/' . $service->id . '/' . $filename);
        if (File::exists($filePath)) return response()->download($filePath);
        return back()->with('error', 'فایل یافت نشد.');
    }

    private function updateCronJobs(Service $service, array $settings)
    {
        try {
            // 1. Database Backup Cron (Dispatches via queue or artisan)
            $dbCronName = 'backup-service-' . $service->id . '-db';
            $dbCommand = "php " . base_path('artisan') . " backup:run-service " . $service->id . " --type=db";
            $existingDbJob = $this->cron->findJobByName($dbCronName);

            if (!empty($settings['db_enabled']) && !empty($settings['db_cron_expression'])) {
                if ($existingDbJob) {
                    $this->cron->update($existingDbJob['id'], $dbCronName, $settings['db_cron_expression'], $dbCommand, null, true);
                } else {
                    $this->cron->create($dbCronName, $settings['db_cron_expression'], $dbCommand, null, true);
                }
            } elseif ($existingDbJob) {
                $this->cron->delete($existingDbJob['id']);
            }

            // 2. Files Backup Cron
            $filesCronName = 'backup-service-' . $service->id . '-files';
            $filesCommand = "php " . base_path('artisan') . " backup:run-service " . $service->id . " --type=files";
            $existingFilesJob = $this->cron->findJobByName($filesCronName);

            if (!empty($settings['files_enabled']) && !empty($settings['files_cron_expression'])) {
                if ($existingFilesJob) {
                    $this->cron->update($existingFilesJob['id'], $filesCronName, $settings['files_cron_expression'], $filesCommand, null, true);
                } else {
                    $this->cron->create($filesCronName, $settings['files_cron_expression'], $filesCommand, null, true);
                }
            } elseif ($existingFilesJob) {
                $this->cron->delete($existingFilesJob['id']);
            }

            // Delete legacy single job if exists
            $legacyName = 'backup-service-' . $service->id;
            $legacyJob = $this->cron->findJobByName($legacyName);
            if ($legacyJob) {
                $this->cron->delete($legacyJob['id']);
            }
        } catch (\Exception $e) {
            Log::warning("Could not update cron jobs for service {$service->id}: " . $e->getMessage());
        }
    }

    private function getRecentBackups(Service $service): array
    {
        $backupDir = storage_path('app/backups/' . $service->id);
        if (!File::isDirectory($backupDir)) return [];
        
        try {
            $files = File::files($backupDir);
        } catch (\Exception $e) { return []; }
        
        $valid_files = [];
        foreach ($files as $file) {
            if (preg_match('/^(backup|db|files)_/i', $file->getFilename())) {
                try { if ($file->isReadable()) $valid_files[] = $file; } catch (\Exception $e) {}
            }
        }

        usort($valid_files, function ($a, $b) {
            try { return $b->getMTime() - $a->getMTime(); } catch (\Exception $e) { return 0; }
        });

        $recent = [];
        foreach (array_slice($valid_files, 0, 20) as $file) {
            try {
                $recent[] = [
                    'name' => $file->getFilename(),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                ];
            } catch (\Exception $e) {}
        }
        return $recent;
    }

    private function getBackupSettings(Service $service): array
    {
        $settingsPath = $this->getSettingsPath($service);
        try {
            if (File::exists($settingsPath) && is_readable($settingsPath)) {
                $settings = json_decode(File::get($settingsPath), true);
                if (is_array($settings)) {
                    $settings['db_enabled'] = $settings['db_enabled'] ?? ($settings['include_db'] ?? true);
                    $settings['db_cron_expression'] = $settings['db_cron_expression'] ?? ($settings['cron_expression'] ?? '0 2 * * *');
                    $settings['db_local_retention_days'] = $settings['db_local_retention_days'] ?? ($settings['local_retention_days'] ?? 3);
                    $settings['db_remote_retention_days'] = $settings['db_remote_retention_days'] ?? ($settings['remote_retention_days'] ?? 3);

                    $settings['files_enabled'] = $settings['files_enabled'] ?? ($settings['include_files'] ?? true);
                    $settings['files_cron_expression'] = $settings['files_cron_expression'] ?? '0 2 * * 5';
                    $settings['files_local_retention_days'] = $settings['files_local_retention_days'] ?? 14;
                    $settings['files_remote_retention_days'] = $settings['files_remote_retention_days'] ?? 14;

                    $settings['local_enabled'] = $settings['local_enabled'] ?? true;
                    $settings['remote_enabled'] = $settings['remote_enabled'] ?? false;
                    return $settings;
                }
            }
        } catch (\Exception $e) {}

        return [
            'db_enabled' => true,
            'db_cron_expression' => '0 2 * * *',
            'db_local_retention_days' => 3,
            'db_remote_retention_days' => 3,

            'files_enabled' => true,
            'files_cron_expression' => '0 2 * * 5',
            'files_local_retention_days' => 14,
            'files_remote_retention_days' => 14,

            'local_enabled' => true,
            'remote_enabled' => false,
            'remote_host' => '80.249.115.114',
            'remote_user' => 'mhvardi@backup.vardicrm.ir',
            'remote_password' => 'pqDd2PZ1V8Pkq6r3',
            'remote_path' => '/public_html',
        ];
    }

    private function saveBackupSettings(Service $service, array $settings): bool
    {
        $settingsPath = $this->getSettingsPath($service);
        $dir = dirname($settingsPath);
        try {
            File::ensureDirectoryExists($dir, 0755, true);
            File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
            return true;
        } catch (\Exception $e) { return false; }
    }

    private function getSettingsPath(Service $service): string
    {
        if (env('BACKUP_MOCK_ENABLED', false)) {
            return rtrim(env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services')), '/') . '/' . $service->domain . '/.backup/settings.json';
        }
        return $service->path . '/.backup/settings.json';
    }

    private function getServicePath(Service $service): string
    {
        if (env('BACKUP_MOCK_ENABLED', false)) {
            return rtrim(env('BACKUP_MOCK_SERVICE_BASE', storage_path('app/mock-services')), '/') . '/' . $service->domain;
        }
        return $service->path;
    }
}
