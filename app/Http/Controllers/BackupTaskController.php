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

        $dbConfig = $service->getDatabaseConfig();
        $dbHost = !empty($dbConfig['host']) ? $dbConfig['host'] : config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = !empty($dbConfig['port']) ? $dbConfig['port'] : config('database.connections.mysql.port', '3306');
        $dbUser = !empty($dbConfig['username']) ? $dbConfig['username'] : config('database.connections.mysql.username', 'root');
        $dbPass = $dbConfig['password'] ?? config('database.connections.mysql.password', '');
        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($destPath);
        
        $process = \Illuminate\Support\Facades\Process::timeout(1800)->run($cmd);
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
            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '--exclude=vendor',
            '--exclude=storage/app/backups', '--exclude=storage/app/mock-services',
            '--exclude=storage/framework/cache', '--exclude=storage/framework/sessions',
            '--exclude=storage/framework/views', '--exclude=storage/logs', '.'
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

        // 1. Files tar (excluding vendor & node_modules & existing backups)
        $filesTar = "{$backupDir}/temp_files_{$timestamp}.tar";
        $tempFiles[] = $filesTar;
        $process = new Process([
            'tar', '-cf', $filesTar, '-C', $servicePath,
            '--exclude=.backup', '--exclude=.git', '--exclude=node_modules', '--exclude=vendor',
            '--exclude=storage/app/backups', '--exclude=storage/app/mock-services',
            '--exclude=storage/framework/cache', '--exclude=storage/framework/sessions',
            '--exclude=storage/framework/views', '--exclude=storage/logs', '.'
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
                $dbConfig = $service->getDatabaseConfig();
                $dbHost = !empty($dbConfig['host']) ? $dbConfig['host'] : config('database.connections.mysql.host', '127.0.0.1');
                $dbPort = !empty($dbConfig['port']) ? $dbConfig['port'] : config('database.connections.mysql.port', '3306');
                $dbUser = !empty($dbConfig['username']) ? $dbConfig['username'] : config('database.connections.mysql.username', 'root');
                $dbPass = $dbConfig['password'] ?? config('database.connections.mysql.password', '');
                $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
                $cmd = "mysqldump --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " > " . escapeshellarg($dbDump);
                $p = \Illuminate\Support\Facades\Process::timeout(1800)->run($cmd);
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
            'remote_port' => 'nullable|integer',
        ]);
        
        $result = FtpBackupDriver::testConnection(
            $request->remote_host, 
            $request->remote_user, 
            $request->remote_password,
            (int) ($request->remote_port ?? 21)
        );
        return response()->json($result);
    }

    public function bulkBackup(Request $request)
    {
        $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'type' => 'required|in:db,files,full',
            'target' => 'required|in:local,ftp',
        ]);

        $serviceIds = $request->service_ids;
        $type = $request->type;
        $target = $request->target;

        $typeLabels = ['db' => 'پایگاه‌داده', 'files' => 'فایل‌های سورس', 'full' => 'کامل (دیتابیس + فایل‌ها)'];
        $targetLabels = ['local' => 'سرور محلی', 'ftp' => 'هاست FTP مرکزی'];

        $count = 0;
        foreach ($serviceIds as $id) {
            $service = Service::find($id);
            if ($service) {
                RunServiceBackupJob::dispatch($service, $type, $target)->onQueue('backups');
                $count++;
            }
        }

        return back()->with('success', "تعداد {$count} درخواست بکاپ «{$typeLabels[$type]}» ({$targetLabels[$target]}) با موفقیت در صف پردازش ترتیبی قرار گرفتند و به نوبت یکی‌یکی اجرا خواهند شد.");
    }

    public function testGlobalHealth()
    {
        $logs = [];
        $startTime = microtime(true);
        $log = function(string $msg) use (&$logs) {
            $logs[] = '[' . date('H:i:s') . '] ' . $msg;
        };

        $log("🚀 آغاز تست سلامت کلی سیستم پشتیبان‌گیری...");

        // 1. MySQL & mysqldump Health
        $dbOk = false;
        try {
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');
            $dbUser = config('database.connections.mysql.username', 'root');
            $dbPass = config('database.connections.mysql.password', '');
            
            $pdo = new \PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
            $log("✅ اتصال پایگاه‌داده مرکزی (MySQL {$ver}) در وضعیت آماده‌به‌کار است.");
            $dbOk = true;
        } catch (\Throwable $e) {
            $log("❌ خطا در اتصال به پایگاه‌داده مرکزی: " . $e->getMessage());
        }

        // 2. FTP Connection Health (test with central FTP from first active service or default credentials)
        $ftpOk = false;
        $activeServiceWithFtp = Service::all()->first(function($s) {
            $st = $this->getBackupSettings($s);
            return !empty($st['remote_enabled']) && !empty($st['remote_host']);
        });

        if ($activeServiceWithFtp) {
            $st = $this->getBackupSettings($activeServiceWithFtp);
            $ftpRes = FtpBackupDriver::testConnectionDetailed(
                $st['remote_host'],
                $st['remote_user'] ?? '',
                $st['remote_password'] ?? '',
                intval($st['remote_port'] ?? 21)
            );
            $ftpOk = $ftpRes['success'];
            if ($ftpOk) {
                $log("✅ اتصال به هاست FTP مرکزی ({$st['remote_host']}) برقرار و تایید شد ({$ftpRes['latency_ms']}ms).");
            } else {
                $log("❌ خطا در اتصال به هاست FTP مرکزی: " . $ftpRes['message']);
            }
        } else {
            $log("ℹ️ هیچ هاست FTP فعالی در تنظیمات سرویس‌ها ذخیره نشده است.");
        }

        // 3. Cron Daemon Status
        $cronOk = false;
        $check = \Illuminate\Support\Facades\Process::run('systemctl is-active cron 2>/dev/null');
        if (trim($check->output()) === 'active') {
            $cronOk = true;
            $log("✅ سرویس کران‌جاب سیستم (cron daemon) فعال و در حال اجراست.");
        } else {
            $psCheck = \Illuminate\Support\Facades\Process::run("ps -eo comm | grep -E '^(cron|crond)$'");
            if (!empty(trim($psCheck->output()))) {
                $cronOk = true;
                $log("✅ پروسه کران در سیستم فعال است.");
            } else {
                $log("⚠️ سرویس کران در حال حاضر متوقف است.");
            }
        }

        // 4. Queue Worker Status / Queue Count
        $queueCount = 0;
        try {
            $queueCount = DB::table('jobs')->where('queue', 'backups')->count();
            $log("📋 تعداد وظایف در صف ترتیبی (Queue): {$queueCount} مورد");
        } catch (\Throwable $e) {}

        $elapsed = round((microtime(true) - $startTime) * 1000, 1);
        $log("🏁 پایان تست کلی سلامت سیستم ({$elapsed}ms).");

        return response()->json([
            'success' => $dbOk && $cronOk,
            'db_ok' => $dbOk,
            'ftp_ok' => $ftpOk,
            'cron_ok' => $cronOk,
            'queue_count' => $queueCount,
            'logs' => $logs,
            'latency_ms' => $elapsed,
        ]);
    }

    public function testFtpFull(Request $request, Service $service)
    {
        $settings = $this->getBackupSettings($service);
        $host = $request->remote_host ?: ($settings['remote_host'] ?? '');
        $port = intval($request->remote_port ?: ($settings['remote_port'] ?? 21));
        $user = $request->remote_user ?: ($settings['remote_user'] ?? '');
        $pass = $request->remote_password ?: ($settings['remote_password'] ?? '');
        $path = $request->remote_path ?: ($settings['remote_path'] ?? '');

        if (empty($host) || empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'آدرس سرور یا نام کاربری FTP وارد نشده است.',
                'logs' => ['[' . date('H:i:s') . '] ❌ لطفا اطلاعات هاست FTP را کامل وارد کنید.'],
            ]);
        }

        $remoteDir = trim($path, '/');
        if ($remoteDir !== '') {
            $remoteDir = '/' . $remoteDir;
        }
        $remoteDir .= '/' . $service->domain;

        $res = FtpBackupDriver::testConnectionDetailed($host, $user, $pass, $port, $remoteDir);
        return response()->json($res);
    }

    public function testDatabase(Service $service)
    {
        $logs = [];
        $startTime = microtime(true);
        $log = function(string $msg) use (&$logs) {
            $logs[] = '[' . date('H:i:s') . '] ' . $msg;
        };

        $dbName = $service->getDatabaseName();
        $dbConfig = $service->getDatabaseConfig();
        $dbHost = !empty($dbConfig['host']) ? $dbConfig['host'] : config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = !empty($dbConfig['port']) ? $dbConfig['port'] : config('database.connections.mysql.port', '3306');
        $dbUser = !empty($dbConfig['username']) ? $dbConfig['username'] : config('database.connections.mysql.username', 'root');
        $dbPass = $dbConfig['password'] ?? config('database.connections.mysql.password', '');

        $log("🔌 تست اتصال به پایگاه‌داده: سرور {$dbHost}:{$dbPort} با کاربر '{$dbUser}' ...");

        if (empty($dbName)) {
            $log("⚠️ هشدار: نام دیتابیس در فایل .env یا تنظیمات سرویس یافت نشد.");
            return response()->json([
                'success' => false,
                'message' => 'نام پایگاه‌داده مشخص نیست.',
                'logs' => $logs,
            ]);
        }

        $log("🗄️ نام پایگاه‌داده شناسایی‌شده: {$dbName}");

        $tableCount = 0;
        $sizeMb = 0;

        // 1. PDO Connection Test
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdoStart = microtime(true);
            $pdo = new \PDO($dsn, $dbUser, $dbPass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdoLatency = round((microtime(true) - $pdoStart) * 1000, 1);
            $log("✅ اتصال مستقیم PDO به MySQL موفق بود (تاخیر: {$pdoLatency}ms).");

            // Fetch server version
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            $log("ℹ️ نسخه MySQL سرور: {$version}");

            // Count tables & database size
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as table_count, 
                           ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                ");
                $stmt->execute([$dbName]);
                $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
                $tableCount = $stats['table_count'] ?? 0;
                $sizeMb = $stats['size_mb'] ?? 0;
                $log("📊 آمار دیتابیس: {$tableCount} جدول | حجم تقریبی داده‌ها: {$sizeMb} MB");
            } catch (\Throwable $ex) {
                $log("ℹ️ امکان خواندن آمار information_schema وجود نداشت: " . $ex->getMessage());
            }

        } catch (\PDOException $e) {
            $log("❌ خطا در اتصال PDO به دیتابیس: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در اتصال به پایگاه‌داده: ' . $e->getMessage(),
                'logs' => $logs,
            ]);
        }

        // 2. mysqldump Probe / Dry-run Test
        $log("🧪 اجرای تست آزمایشی mysqldump (با فلگ‌های --single-transaction, --quick, --skip-lock-tables) ...");
        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} --no-data " . escapeshellarg($dbName);

        $dumpStart = microtime(true);
        $process = \Illuminate\Support\Facades\Process::timeout(15)->run($cmd);
        $dumpLatency = round((microtime(true) - $dumpStart) * 1000, 1);

        if (!$process->successful()) {
            $errorOutput = trim($process->errorOutput());
            $log("❌ خطا در اجرای mysqldump: {$errorOutput}");
            return response()->json([
                'success' => false,
                'message' => 'دستور mysqldump با خطا متوقف شد: ' . $errorOutput,
                'logs' => $logs,
            ]);
        }

        $log("✅ تست خروجی mysqldump با موفقیت ساختار دیتابیس را خواند ({$dumpLatency}ms).");

        $totalElapsed = round((microtime(true) - $startTime) * 1000, 1);
        $log("🎉 تمام تست‌های دیتابیس با موفقیت پشت سر گذاشته شدند (کل زمان: {$totalElapsed}ms).");

        return response()->json([
            'success' => true,
            'message' => "✅ پایگاه‌داده و ابزار mysqldump در سلامت کامل هستند ({$totalElapsed}ms).",
            'logs' => $logs,
            'table_count' => $tableCount,
            'size_mb' => $sizeMb,
        ]);
    }

    public function testCron(Service $service)
    {
        $logs = [];
        $log = function(string $msg) use (&$logs) {
            $logs[] = '[' . date('H:i:s') . '] ' . $msg;
        };

        $log("⏰ آغاز بررسی وضعیت کران‌جاب (Cron Service) و زمان‌بندی‌های سرور...");

        // 1. Check Cron Service / Daemon
        $isCronActive = false;
        $cronDaemonMsg = '';
        
        $check1 = \Illuminate\Support\Facades\Process::run('systemctl is-active cron 2>/dev/null');
        if (trim($check1->output()) === 'active') {
            $isCronActive = true;
            $cronDaemonMsg = 'سرویس cron در systemd فعال (active) است.';
        } else {
            $check2 = \Illuminate\Support\Facades\Process::run('systemctl is-active crond 2>/dev/null');
            if (trim($check2->output()) === 'active') {
                $isCronActive = true;
                $cronDaemonMsg = 'سرویس crond در systemd فعال (active) است.';
            } else {
                $psCheck = \Illuminate\Support\Facades\Process::run("ps -eo comm | grep -E '^(cron|crond)$'");
                if (!empty(trim($psCheck->output()))) {
                    $isCronActive = true;
                    $cronDaemonMsg = 'پروسه cron در پس‌زمینه سرور در حال اجراست.';
                }
            }
        }

        if ($isCronActive) {
            $log("✅ وضعیت سرویس کران سرور: {$cronDaemonMsg}");
        } else {
            $log("⚠️ هشدار: به نظر می‌رسد سرویس cron روی سرور غیرفعال یا متوقف است. (دستور پیشنهادی: systemctl start cron)");
        }

        // 2. Check /etc/cron.d/server-panel file
        $cronService = new CronJobService();
        $cronConfig = $cronService->getConfig();
        $cronFile = $cronConfig['cron_file'] ?? '/etc/cron.d/server-panel';

        if (File::exists($cronFile)) {
            $perms = substr(sprintf('%o', fileperms($cronFile)), -4);
            $log("📁 فایل کران پنل: {$cronFile} (مجوز دسترسی: {$perms})");
            if ($perms !== '0644' && $perms !== '644') {
                $log("⚠️ توجه: استاندارد مجوزهای فایل‌های داخل /etc/cron.d برابر با 0644 است.");
            }
            $content = File::get($cronFile);
            if (!str_ends_with($content, "\n")) {
                $log("⚠️ توجه: فایل کران با خط جدید (Newline) خاتمه نیافته است که ممکن است باعث عدم اجرای خط آخر توسط crond شود.");
            }
        } else {
            $log("📁 فایل اختصاصی {$cronFile} هنوز ایجاد نشده است (با ذخیره تنظیمات ساخته می‌شود).");
        }

        // 3. Search for scheduled jobs of this service
        $jobs = $cronService->listJobs();
        $serviceJobs = [];
        foreach ($jobs as $j) {
            if (str_contains($j['command'] ?? '', "backup:run-service {$service->id}") || str_contains($j['name'] ?? '', "backup_{$service->id}")) {
                $serviceJobs[] = $j;
            }
        }

        if (count($serviceJobs) > 0) {
            $log("📋 تعداد " . count($serviceJobs) . " دستور زمان‌بندی‌شده برای سرویس {$service->name} در فایل کران ثبت شده است:");
            foreach ($serviceJobs as $sj) {
                $statusStr = ($sj['enabled'] ?? true) ? '🟢 فعال' : '🔴 غیرفعال';
                $log("   • {$statusStr} [{$sj['schedule']}] کاربر: {$sj['run_as']} | نام: {$sj['name']}");
            }
        } else {
            $log("ℹ️ در حال حاضر هیچ رکورد مستقیمی برای این سرویس در فایل کران ثبت نشده است (در صورت فعال بودن در تب تنظیمات، دکمه ذخیره را بزنید).");
        }

        // 4. Test Dry-Run Execution of the Backup Command
        $log("🧪 اجرای تست خشک (Dry-Run Simulation) دستور بکاپ با آرتیسان...");
        $artisanPath = base_path('artisan');
        $dryRunCmd = "php {$artisanPath} backup:run-service {$service->id} --dry-run";
        $p = \Illuminate\Support\Facades\Process::timeout(60)->run($dryRunCmd);
        
        if ($p->successful()) {
            $log("✅ شبیه‌سازی اجرای دستور بکاپ با موفقیت انجام شد:");
            $outLines = array_filter(explode("\n", trim($p->output())));
            foreach (array_slice($outLines, 0, 8) as $ol) {
                $log("   " . $ol);
            }
        } else {
            $log("❌ خطایی در شبیه‌سازی اجرای دستور بکاپ رخ داد:");
            $log("   " . ($p->errorOutput() ?: $p->output()));
        }

        $log("🏁 بررسی و تست کران‌جاب به پایان رسید.");

        return response()->json([
            'success' => $isCronActive && $p->successful(),
            'message' => $isCronActive ? '✅ وضعیت کران و شبیه‌سازی تایید شد.' : '⚠️ سرویس کران غیرفعال است یا دستور با خطا مواجه شد.',
            'logs' => $logs,
            'is_cron_active' => $isCronActive,
            'jobs_count' => count($serviceJobs),
        ]);
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
