<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CronJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
            $service->last_backup = $settings['last_backup'] ?? null;
            return $service;
        });

        return view('backup_tasks.index', compact('services'));
    }

    public function settings(Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $recent_backups = $this->getRecentBackups($service);
        $last_backup_status = ['status' => 'نامشخص', 'date' => 'هرگز']; // Placeholder

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

        $this->saveBackupSettings($service, $data);
        $this->updateCronJob($service, $data);

        return redirect()->route('backup_tasks.index')->with('success', 'Backup settings saved for ' . $service->name);
    }

    public function run(Service $service)
    {
        \App\Jobs\RunServiceBackupJob::dispatch($service);
        return back()->with('success', 'Backup job for ' . $service->name . ' has been dispatched.');
    }

    public function testFtp(Request $request)
    {
        $request->validate([
            'remote_host' => 'required|string',
            'remote_user' => 'required|string',
            'remote_password' => 'required|string',
        ]);

        try {
            $conn = @ftp_connect($request->remote_host, 21, 5);
            if (!$conn) {
                return response()->json(['success' => false, 'message' => 'اتصال به سرور FTP برقرار نشد.']);
            }

            $login = @ftp_login($conn, $request->remote_user, $request->remote_password);
            if (!$login) {
                @ftp_close($conn);
                return response()->json(['success' => false, 'message' => 'نام کاربری یا رمز عبور FTP اشتباه است.']);
            }

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

        $cmd = "mysqldump " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($filePath);
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
        
        $cmd = "cd " . escapeshellarg($servicePath) . " && zip -r " . escapeshellarg($filePath) . " .";
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

        $files = File::files($backupDir);
        $recent_backups = [];

        // Sort files by modification time, newest first
        usort($files, function ($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });

        foreach (array_slice($files, 0, 5) as $file) {
            $recent_backups[] = [
                'name' => $file->getFilename(),
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
            ];
        }

        return $recent_backups;
    }

    private function getBackupSettings(Service $service): array
    {
        $settingsPath = $this->getSettingsPath($service);
        if (File::exists($settingsPath)) {
            return json_decode(File::get($settingsPath), true);
        }

        return [
            'enabled' => false,
            'include_files' => true,
            'include_db' => false,
            'db_name' => '',
            'cron_expression' => '0 2 * * *',
            'remote_enabled' => false,
            'remote_host' => '',
            'remote_user' => '',
            'remote_password' => '',
            'remote_path' => '/',
            'local_retention' => 7,
            'remote_retention' => 30,
            'last_backup' => null,
        ];
    }

    private function saveBackupSettings(Service $service, array $settings)
    {
        $settingsPath = $this->getSettingsPath($service);
        $dir = dirname($settingsPath);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
    }

    private function getSettingsPath(Service $service): string
    {
        return $service->path . '/.backup/settings.json';
    }
}
