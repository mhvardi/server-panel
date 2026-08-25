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
     * Clean old backups by Days
     */
    public function cleanOldBackupsByDays(string $remoteDir, int $days): int
    {
        $this->assertConnected();
        if ($days <= 0) return 0;

        $rawFiles = @ftp_nlist($this->conn, $remoteDir) ?: [];
        $deleted = 0;
        $cutoff = time() - ($days * 86400);

        foreach ($rawFiles as $file) {
            $base = basename($file);
            if ($base === '.' || $base === '..') continue;
            if (!preg_match('/\.(tar\.gz|zip|sql\.gz|tar)$/i', $base)) continue;

            $filePath = (str_starts_with($file, '/')) ? $file : $remoteDir . '/' . $file;
            $mtime = @ftp_mdtm($this->conn, $filePath);
            
            if ($mtime !== -1 && $mtime < $cutoff) {
                if (@ftp_delete($this->conn, $filePath)) {
                    $this->addLog("🗑️ حذف بکاپ قدیمی از FTP (بیشتر از {$days} روز): " . $base);
                    $deleted++;
                }
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
        $conn = @ftp_connect($host, $port, 10);
        if (!$conn) {
            return ['success' => false, 'message' => "اتصال به سرور FTP ({$host}:{$port}) برقرار نشد."];
        }
        $login = @ftp_login($conn, $user, $password);
        @ftp_close($conn);

        if (!$login) {
            return ['success' => false, 'message' => "نام کاربری یا رمز عبور FTP نامعتبر است."];
        }
        return ['success' => true, 'message' => "✅ اتصال به سرور FTP با موفقیت انجام شد."];
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
