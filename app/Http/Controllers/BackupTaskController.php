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
    public function __construct(private readonly CronJobService $cron)
    {
    }

    public function index()
    {
        $services = Service::all()->map(function ($service) {
            $settings = $this->getBackupSettings($service);
            $service->backup_enabled = $settings['enabled'] ?? false;
            $service->last_backup = !empty($settings['last_backup']) ? Carbon::parse($settings['last_backup']) : null;
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
        ];

        return view('backup_tasks.settings', compact('service', 'settings', 'recent_backups', 'last_backup_status'));
    }

    public function saveSettings(Request $request, Service $service)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'include_files' => 'required|boolean',
            'include_db' => 'required|boolean',
            'db_name' => 'nullable|string|required_if:include_db,true',
            'cron_expression' => 'nullable|string',
            'remote_enabled' => 'required|boolean',
            'remote_host' => 'nullable|string|required_if:remote_enabled,true',
            'remote_user' => 'nullable|string|required_if:remote_enabled,true',
            'remote_password' => 'nullable|string',
            'remote_path' => 'nullable|string|required_if:remote_enabled,true',
            'local_retention' => 'required|integer|min:1',
            'remote_retention' => 'required|integer|min:1',
        ]);

        $existingSettings = $this->getBackupSettings($service);

        // If password is left empty on update, retain existing password
        if (empty($data['remote_password']) && !empty($existingSettings['remote_password'])) {
            $data['remote_password'] = $existingSettings['remote_password'];
        }

        // Preserve previous backup date and status
        $data['last_backup'] = $existingSettings['last_backup'] ?? null;
        $data['last_backup_status'] = $existingSettings['last_backup_status'] ?? null;

        $saved = $this->saveBackupSettings($service, $data);
        if (!$saved) {
            return back()->with('error', 'خطا در ذخیره تنظیمات: عدم دسترسی (Permission Denied). لطفاً روی سرور دسترسی‌های پوشه را با chown اصلاح کنید.');
        }

        $this->updateCronJob($service, $data);

        return redirect()->route('backup_tasks.index')->with('success', 'تنظیمات پشتیبان‌گیری برای ' . $service->name . ' با موفقیت ذخیره شد.');
    }

    public function run(Service $service)
    {
        try {
            $exitCode = Artisan::call('backup:run-service', ['service_id' => $service->id]);
            $output = Artisan::output();
            
            if ($exitCode === 0) {
                return back()->with('success', 'عملیات پشتیبان‌گیری با موفقیت انجام شد.' . ($output ? ' ' . trim($output) : ''));
            } else {
                return back()->with('error', 'خطا در اجرای پشتیبان‌گیری: ' . trim($output));
            }
        } catch (\Exception $e) {
            Log::error('Backup execution failed for service ' . $service->id . ': ' . $e->getMessage());
            return back()->with('error', 'خطا در پشتیبان‌گیری: ' . $e->getMessage());
        }
    }

    public function testFtp(Request $request)
    {
        $request->validate([
            'remote_host' => 'required|string',
            'remote_user' => 'required|string',
            'remote_password' => 'required|string',
        ]);

        try {
            $conn = @ftp_connect($request->remote_host, 21, 10);
            if (!$conn) {
                return response()->json(['success' => false, 'message' => 'اتصال به سرور FTP برقرار نشد (هاست یا پورت در دسترس نیست).']);
            }

            $login = @ftp_login($conn, $request->remote_user, $request->remote_password);
            if (!$login) {
                @ftp_close($conn);
                return response()->json(['success' => false, 'message' => 'نام کاربری یا رمز عبور FTP اشتباه است.']);
            }

            ftp_pasv($conn, true);
            @ftp_close($conn);
            return response()->json(['success' => true, 'message' => 'اتصال به سرور FTP با موفقیت انجام شد.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطا در اتصال: ' . $e->getMessage()]);
        }
    }

    public function backupDatabaseNow(Service $service, Request $request)
    {
        $settings = $this->getBackupSettings($service);
        $dbName = $settings['db_name'] ?? null;
        
        if (empty($dbName)) {
             return back()->with('error', 'پایگاه‌داده‌ای برای این سرویس تنظیم نشده است.');
        }

        $backupDir = storage_path('app/backups/' . $service->id);
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fileName = 'db_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filePath = $backupDir . '/' . $fileName;

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($filePath);
        $process = \Illuminate\Support\Facades\Process::run($cmd);
        
        if ($process->successful()) {
            return response()->download($filePath);
        }

        return back()->with('error', 'خطا در بکاپ‌گیری دیتابیس: ' . $process->errorOutput());
    }

    public function backupFilesNow(Service $service)
    {
        $backupDir = storage_path('app/backups/' . $service->id);
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fileName = 'files_' . date('Y-m-d_H-i-s') . '.zip';
        $filePath = $backupDir . '/' . $fileName;

        $servicePath = $service->path;
        
        $cmd = "cd " . escapeshellarg($servicePath) . " && zip -r " . escapeshellarg($filePath) . " . -x '*.git*' '*node_modules*' '*.backup*'";
        $process = \Illuminate\Support\Facades\Process::run($cmd);
        
        if ($process->successful()) {
            return response()->download($filePath);
        }

        return back()->with('error', 'خطا در بکاپ‌گیری فایل‌ها: ' . $process->errorOutput());
    }
    
    public function downloadBackup(Service $service, $filename)
    {
        $filePath = storage_path('app/backups/' . $service->id . '/' . $filename);
        if (File::exists($filePath)) {
            return response()->download($filePath);
        }
        return back()->with('error', 'فایل بکاپ یافت نشد.');
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
        if (!File::isDirectory($backupDir)) {
            return [];
        }

        try {
            $files = File::files($backupDir);
        } catch (\Exception $e) {
            Log::warning("Could not read backup directory for service {$service->id}: " . $e->getMessage());
            return [];
        }
        
        $valid_files = [];
        foreach ($files as $file) {
            try {
                if ($file->isReadable()) {
                    $valid_files[] = $file;
                }
            } catch (\Exception $e) {
                // Ignore unreadable files
            }
        }

        $recent_backups = [];

        // Sort files by modification time, newest first
        usort($valid_files, function ($a, $b) {
            try {
                return $b->getMTime() - $a->getMTime();
            } catch (\Exception $e) {
                return 0;
            }
        });

        foreach (array_slice($valid_files, 0, 5) as $file) {
            try {
                $recent_backups[] = [
                    'name' => $file->getFilename(),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                ];
            } catch (\Exception $e) {
                // Ignore files that fail stat
            }
        }

        return $recent_backups;
    }

    private function getBackupSettings(Service $service): array
    {
        $settingsPath = $this->getSettingsPath($service);
        try {
            if (File::exists($settingsPath) && is_readable($settingsPath)) {
                $settings = json_decode(File::get($settingsPath), true);
                if (is_array($settings)) {
                    return $settings;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Could not read backup settings for service {$service->id}: " . $e->getMessage());
        }

        return [
            'enabled' => false,
            'include_files' => true,
            'include_db' => false,
            'db_name' => '',
            'cron_expression' => '0 2 * * *',
            'remote_enabled' => false,
            'remote_host' => '80.249.115.114',
            'remote_user' => 'mhvardi@backup.vardicrm.ir',
            'remote_password' => 'pqDd2PZ1V8Pkq6r3',
            'remote_path' => '/public_html',
            'local_retention' => 7,
            'remote_retention' => 2,
            'last_backup' => null,
            'last_backup_status' => 'نامشخص',
        ];
    }

    private function saveBackupSettings(Service $service, array $settings): bool
    {
        $settingsPath = $this->getSettingsPath($service);
        $dir = dirname($settingsPath);

        try {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to save backup settings for service {$service->id}: " . $e->getMessage());
            return false;
        }
    }

    private function getSettingsPath(Service $service): string
    {
        return $service->path . '/.backup/settings.json';
    }
}
