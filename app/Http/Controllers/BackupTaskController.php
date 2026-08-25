<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CronJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupTaskController extends Controller
{
    public function __construct(private readonly CronJobService $cron) {}

    public function index()
    {
        $services = Service::all()->map(function ($service) {
            $settings = $this->getBackupSettings($service);
            $service->backup_enabled = $settings['enabled'] ?? false;
            $service->last_backup = !empty($settings['last_backup']) ? Carbon::parse($settings['last_backup']) : null;
            $service->local_enabled = $settings['local_enabled'] ?? true;
            $service->remote_enabled = $settings['remote_enabled'] ?? false;
            $service->last_backup_size = $settings['last_backup_size_mb'] ?? null;
            $service->last_backup_status = $settings['last_backup_status'] ?? 'نامشخص';
            return $service;
        });

        return view('backup_tasks.index', compact('services'));
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

        return view('backup_tasks.settings', compact('service', 'settings', 'recent_backups', 'last_backup_status'));
    }

    public function saveSettings(Request $request, Service $service)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'cron_preset' => 'required|string',
            'cron_custom' => 'nullable|string',
            
            'include_files' => 'required|boolean',
            'include_db' => 'required|boolean',
            'db_name' => 'nullable|string|required_if:include_db,true',
            
            'local_enabled' => 'required|boolean',
            'local_retention_days' => 'required|integer|min:1',
            
            'remote_enabled' => 'required|boolean',
            'remote_host' => 'nullable|string|required_if:remote_enabled,true',
            'remote_user' => 'nullable|string|required_if:remote_enabled,true',
            'remote_password' => 'nullable|string',
            'remote_path' => 'nullable|string|required_if:remote_enabled,true',
            'remote_retention_days' => 'required|integer|min:1',
        ]);

        if ($data['cron_preset'] === 'custom') {
            $data['cron_expression'] = $data['cron_custom'];
        } else {
            $data['cron_expression'] = $data['cron_preset'];
        }
        
        unset($data['cron_preset'], $data['cron_custom']);

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

        $this->updateCronJob($service, $data);

        return redirect()->route('backup_tasks.settings', $service->id)->with('success', 'تنظیمات پشتیبان‌گیری با موفقیت ذخیره شد.');
    }

    public function run(Service $service, Request $request)
    {
        try {
            $args = ['service_id' => $service->id];
            if ($request->has('ftp_only')) {
                $args['--ftp-only'] = true;
            }
            if ($request->has('dry_run')) {
                $args['--dry-run'] = true;
            }
            
            $exitCode = Artisan::call('backup:run-service', $args);
            $output = Artisan::output();
            
            if ($exitCode === 0) {
                return back()->with('success', 'عملیات بکاپ با موفقیت انجام شد.');
            } else {
                return back()->with('error', 'خطا در اجرای پشتیبان‌گیری.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در پشتیبان‌گیری: ' . $e->getMessage());
        }
    }

    public function getLog(Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $logFile = $settings['backup_log'] ?? null;
        
        if ($logFile && File::exists($logFile) && is_readable($logFile)) {
            $content = File::get($logFile);
            // Get last run only by splitting
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
        
        $result = \App\Services\FtpBackupDriver::testConnection(
            $request->remote_host, 
            $request->remote_user, 
            $request->remote_password
        );
        return response()->json($result);
    }
    
    // ... dbNow & filesNow omitted for brevity, keeping old logic via exec
    public function backupDatabaseNow(Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $dbName = $settings['db_name'] ?? null;
        if (empty($dbName)) return back()->with('error', 'پایگاه‌داده تنظیم نشده است.');

        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $fileName = 'db_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filePath = $backupDir . '/' . $fileName;

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');
        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($filePath);
        
        $process = \Illuminate\Support\Facades\Process::run($cmd);
        if ($process->successful()) return response()->download($filePath);
        return back()->with('error', 'خطا: ' . $process->errorOutput());
    }

    public function backupFilesNow(Service $service)
    {
        $backupDir = storage_path('app/backups/' . $service->id);
        File::ensureDirectoryExists($backupDir);
        $fileName = 'files_' . date('Y-m-d_H-i-s') . '.zip';
        $filePath = $backupDir . '/' . $fileName;
        $cmd = "cd " . escapeshellarg($service->path) . " && zip -r " . escapeshellarg($filePath) . " . -x '*.git*' '*node_modules*' '*.backup*'";
        
        $process = \Illuminate\Support\Facades\Process::run($cmd);
        if ($process->successful()) return response()->download($filePath);
        return back()->with('error', 'خطا: ' . $process->errorOutput());
    }

    public function downloadBackup(Service $service, $filename)
    {
        $filePath = storage_path('app/backups/' . $service->id . '/' . $filename);
        if (File::exists($filePath)) return response()->download($filePath);
        return back()->with('error', 'فایل یافت نشد.');
    }

    private function updateCronJob(Service $service, array $settings)
    {
        $cronJobName = 'backup-service-' . $service->id;
        $command = "php " . base_path('artisan') . " backup:run-service " . $service->id;
        $existingJob = $this->cron->findJobByName($cronJobName);

        if ($settings['enabled'] && !empty($settings['cron_expression'])) {
            if ($existingJob) {
                $this->cron->update($existingJob['id'], $cronJobName, $settings['cron_expression'], $command, null, true);
            } else {
                $this->cron->create($cronJobName, $settings['cron_expression'], $command, null, true);
            }
        } elseif ($existingJob) {
            $this->cron->delete($existingJob['id']);
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
            if (str_starts_with($file->getFilename(), 'backup_')) {
                try { if ($file->isReadable()) $valid_files[] = $file; } catch (\Exception $e) {}
            }
        }

        usort($valid_files, function ($a, $b) {
            try { return $b->getMTime() - $a->getMTime(); } catch (\Exception $e) { return 0; }
        });

        $recent = [];
        foreach (array_slice($valid_files, 0, 10) as $file) {
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
                if (is_array($settings)) return $settings;
            }
        } catch (\Exception $e) {}

        return [
            'enabled' => false,
            'cron_expression' => '0 2 * * *',
            'include_files' => true,
            'include_db' => false,
            'db_name' => '',
            'local_enabled' => true,
            'local_retention_days' => 7,
            'remote_enabled' => false,
            'remote_host' => '80.249.115.114',
            'remote_user' => 'mhvardi@backup.vardicrm.ir',
            'remote_password' => 'pqDd2PZ1V8Pkq6r3',
            'remote_path' => '/public_html',
            'remote_retention_days' => 7,
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
}
