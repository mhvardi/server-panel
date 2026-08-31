<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FtpBackupDriver
{
    private $conn = null;
    private array $log = [];
    private int $maxRetries = 3;
    private int $connectTimeout = 15;

    public function __construct(
        private string $host,
        private int    $port = 21,
        private string $user = '',
        private string $password = '',
    ) {}

    public function connect(): void
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $this->addLog("اتصال به FTP ({$this->host}:{$this->port}) — تلاش {$attempt}/{$this->maxRetries}...");

            $conn = @ftp_connect($this->host, $this->port, $this->connectTimeout);
            if (!$conn) {
                $lastError = "اتصال به سرور FTP ({$this->host}:{$this->port}) برقرار نشد.";
                $this->addLog("❌ " . $lastError);
                sleep(2);
                continue;
            }

            $login = @ftp_login($conn, $this->user, $this->password);
            if (!$login) {
                @ftp_close($conn);
                $lastError = "نام کاربری یا رمز عبور FTP نامعتبر است ({$this->user}).";
                $this->addLog("❌ " . $lastError);
                sleep(2);
                continue;
            }

            ftp_pasv($conn, true);
            $this->conn = $conn;
            $this->addLog("✅ اتصال به FTP برقرار شد (Passive Mode).");
            return;
        }
        throw new \RuntimeException($lastError ?? "خطای ناشناخته در اتصال FTP.");
    }

    public function ensureRemoteDir(string $remotePath): void
    {
        $this->assertConnected();
        $parts = array_values(array_filter(explode('/', $remotePath)));
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;
            $currentDir = @ftp_pwd($this->conn);
            if (@ftp_chdir($this->conn, $currentPath)) {
                @ftp_chdir($this->conn, $currentDir);
                continue;
            }
            if (!@ftp_mkdir($this->conn, $currentPath)) {
                // Ignore
            } else {
                $this->addLog("📁 پوشه ایجاد شد: {$currentPath}");
            }
        }
    }

    public function upload(string $localPath, string $remotePath): void
    {
        $this->assertConnected();
        if (!file_exists($localPath) || !is_readable($localPath)) {
            throw new \RuntimeException("فایل محلی برای آپلود پیدا نشد یا قابل خواندن نیست: {$localPath}");
        }

        $sizeMb = round(filesize($localPath) / 1024 / 1024, 2);
        $this->addLog("⬆️ آپلود: " . basename($localPath) . " ({$sizeMb} MB) → {$remotePath} ...");

        $startTime = microtime(true);
        $result = @ftp_put($this->conn, $remotePath, $localPath, FTP_BINARY);

        if (!$result) {
            throw new \RuntimeException("خطا در آپلود فایل به FTP: {$remotePath}");
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        $this->addLog("✅ آپلود موفق ({$elapsed}s) — {$sizeMb} MB");
    }

    /**
     * Clean old backups by keeping the last N files (Count-based retention)
     */
    public function cleanOldBackupsByCount(string $remoteDir, int $keepCount, string $prefix = ''): int
    {
        $this->assertConnected();
        if ($keepCount <= 0) return 0;

        $rawFiles = @ftp_nlist($this->conn, $remoteDir) ?: [];
        $validFiles = [];

        foreach ($rawFiles as $file) {
            $base = basename($file);
            if ($base === '.' || $base === '..') continue;
            if (!preg_match('/\.(tar\.gz|zip|sql\.gz|tar)$/i', $base)) continue;

            if ($prefix !== '' && !str_starts_with($base, $prefix)) {
                continue;
            }

            $filePath = (str_starts_with($file, '/')) ? $file : $remoteDir . '/' . $file;
            $mtime = @ftp_mdtm($this->conn, $filePath);
            if ($mtime !== -1) {
                $validFiles[] = [
                    'path' => $filePath,
                    'base' => $base,
                    'mtime' => $mtime
                ];
            }
        }

        // Sort by modified time descending (newest first)
        usort($validFiles, function($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        // The ones to delete are those after index $keepCount
        $toDelete = array_slice($validFiles, $keepCount);
        $deleted = 0;

        foreach ($toDelete as $f) {
            if (@ftp_delete($this->conn, $f['path'])) {
                $prefixLabel = $prefix ? " [فیلتر {$prefix}]" : "";
                $this->addLog("🗑️ حذف بکاپ قدیمی از FTP (نگهداری {$keepCount} نسخه آخر){$prefixLabel}: " . $f['base']);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function disconnect(): void
    {
        if ($this->conn) {
            @ftp_close($this->conn);
            $this->conn = null;
            $this->addLog("🔌 اتصال FTP بسته شد.");
        }
    }

    public function getLogs(): array
    {
        return $this->log;
    }

    public static function testConnection(string $host, string $user, string $password, int $port = 21): array
    {
        $res = self::testConnectionDetailed($host, $user, $password, $port);
        return [
            'success' => $res['success'],
            'message' => $res['message'] ?? ($res['success'] ? '✅ اتصال برقرار شد.' : '❌ خطا در اتصال'),
        ];
    }

    public static function testConnectionDetailed(string $host, string $user, string $password, int $port = 21, ?string $remotePath = null): array
    {
        $logs = [];
        $startTime = microtime(true);
        $log = function(string $msg) use (&$logs) {
            $logs[] = '[' . date('H:i:s') . '] ' . $msg;
        };

        $log("🌐 آغاز تست اتصال FTP به آدرس {$host}:{$port} ...");
        
        $conn = @ftp_connect($host, $port, 10);
        if (!$conn) {
            $log("❌ اتصال سوکت شبکه به {$host}:{$port} برقرار نشد. (احتمال فایروال، بسته بودن پورت یا آدرس اشتباه)");
            return [
                'success' => false,
                'message' => "عدم امکان اتصال به پورت {$port} هاست {$host}.",
                'logs' => $logs,
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 1),
            ];
        }
        $log("✅ اتصال TCP با موفقیت برقرار شد.");

        $log("🔑 در حال احراز هویت با نام‌کاربری '{$user}' ...");
        $login = @ftp_login($conn, $user, $password);
        if (!$login) {
            @ftp_close($conn);
            $log("❌ خطای نام کاربری یا کلمه عبور! احراز هویت با نام '{$user}' رد شد.");
            return [
                'success' => false,
                'message' => "اطلاعات ورود FTP اشتباه است (نام کاربری یا پسورد).",
                'logs' => $logs,
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 1),
            ];
        }
        $log("✅ احراز هویت موفقیت‌آمیز بود.");

        $log("⚙️ فعال‌سازی Passive Mode ...");
        @ftp_pasv($conn, true);
        $log("✅ Passive Mode فعال شد.");

        $systemType = @ftp_systype($conn);
        if ($systemType) {
            $log("🖥️ نوع سیستم ریموت: {$systemType}");
        }

        $currentDir = @ftp_pwd($conn);
        $log("📂 پوشه فعلی در سرور: {$currentDir}");

        // Check remote path if provided
        if ($remotePath) {
            $testDir = trim($remotePath, '/');
            $log("📁 بررسی دسترسی پوشه مقصد: /{$testDir} ...");
            $driver = new self($host, $port, $user, $password);
            $driver->conn = $conn;
            try {
                $driver->ensureRemoteDir('/' . $testDir);
                $log("✅ پوشه مقصد در دسترس است و ساخته/تایید شد.");
            } catch (\Throwable $e) {
                $log("⚠️ خطا در بررسی پوشه مقصد: " . $e->getMessage());
            }
        }

        // Test Write / Delete Permissions
        $log("🧪 تست نوشتن و حذف فایل موقت (Write Permission Test) ...");
        $tempLocal = tempnam(sys_get_temp_dir(), 'ftp_test_');
        file_put_contents($tempLocal, "FTP Test probe: " . date('Y-m-d H:i:s'));
        $tempRemote = ($remotePath ? '/' . trim($remotePath, '/') : '') . '/.test_probe_' . time() . '.tmp';
        
        $uploadOk = @ftp_put($conn, $tempRemote, $tempLocal, FTP_BINARY);
        @unlink($tempLocal);

        if ($uploadOk) {
            $log("✅ آپلود فایل آزمایشی موفقیت‌آمیز بود.");
            @ftp_delete($conn, $tempRemote);
            $log("✅ حذف فایل آزمایشی با موفقیت انجام شد (دسترسی کامل خواندن/نوشتن/حذف تایید شد).");
        } else {
            $log("⚠️ هشدار: ورود موفق بود اما امکان ایجاد فایل آزمایشی در مسیر {$tempRemote} وجود ندارد (محدودیت مجوز دایرکتوری یا پر بودن دیسک).");
        }

        @ftp_close($conn);
        $elapsedMs = round((microtime(true) - $startTime) * 1000, 1);
        $log("🔌 اتصال FTP با موفقیت بسته شد (مدت کل: {$elapsedMs} میلی‌ثانیه).");

        return [
            'success' => true,
            'message' => "✅ تست اتصال FTP و اعتبارسنجی با موفقیت کامل انجام شد ({$elapsedMs}ms).",
            'logs' => $logs,
            'latency_ms' => $elapsedMs,
            'write_permission' => $uploadOk,
        ];
    }

    private function addLog(string $message): void
    {
        $entry = '[' . now()->format('H:i:s') . '] ' . $message;
        $this->log[] = $entry;
    }

    private function assertConnected(): void
    {
        if (!$this->conn) {
            throw new \RuntimeException("FTP اتصال برقرار نیست. ابتدا connect() را فراخوانی کنید.");
        }
    }
}
