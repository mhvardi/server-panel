<?php

namespace App\Services;

use App\Models\FileQuarantine;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileScanner
{
    /**
     * Dangerous extensions that are generally risky if uploaded to public/web folders
     */
    protected array $blockedExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'inc',
        'sh', 'bash', 'zsh', 'bat', 'cmd', 'exe', 'msi', 'bin',
        'py', 'pl', 'cgi', 'asp', 'aspx', 'jsp', 'jspx', 'htaccess'
    ];

    /**
     * Dangerous PHP function/pattern signatures typically found in webshells & backdoors
     */
    protected array $dangerousPatterns = [
        '/eval\s*\(\s*(base64_decode|gzinflate|gzuncompress|str_rot13)/i' => 'Encoded Eval Webshell',
        '/(passthru|shell_exec|exec|system|popen|proc_open)\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i' => 'Remote Command Execution Backdoor',
        '/assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i' => 'Assert Code Execution',
        '/preg_replace\s*\(\s*["\'].*\/e["\']/i' => 'Preg_replace /e code execution',
        '/\$_(GET|POST|REQUEST|COOKIE)\[.*\]\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i' => 'Dynamic function invocation shell',
        '/(c99shell|r57shell|b374k|WSO\s*shell|FilesMan|alfa-team|weevely)/i' => 'Known Webshell Signature',
        '/<\?php\s*eval/i' => 'Direct PHP eval block',
        '/unserialize\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i' => 'PHP Object Injection via Unserialize',
        '/file_put_contents\s*\(\s*.*\$_(GET|POST|REQUEST)/i' => 'Arbitrary File Write Backdoor',
        '/(curl_exec|fsockopen|pfsockopen)\s*\(.*\$_(GET|POST|REQUEST)/i' => 'SSRF / Reverse Shell Trigger',
    ];

    /**
     * Quarantine directory path
     */
    public function getQuarantineDir(): string
    {
        $dir = storage_path('app/quarantine');
        File::ensureDirectoryExists($dir);
        return $dir;
    }

    /**
     * Scan an uploaded file before saving.
     * Returns ['safe' => bool, 'reason' => ?string, 'threat_type' => ?string]
     */
    public function scanUploadedFile(UploadedFile $file, bool $allowPhpForZipExtract = false): array
    {
        if (!SecuritySetting::isTrue('upload_file_scan', true)) {
            return ['safe' => true];
        }

        $origName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        // 1. Check double extensions like file.php.jpg or malicious .php
        if (preg_match('/\.(php[0-9]?|phtml|phar|sh|cgi|py)\./i', $origName)) {
            return [
                'safe' => false,
                'reason' => "فایل دارای پسوند دوتایی خطرناک است ({$origName})",
                'threat_type' => 'double_extension'
            ];
        }

        // 2. Check blocked extensions unless explicitly permitted (e.g. within normal service management)
        if (!$allowPhpForZipExtract && in_array($ext, $this->blockedExtensions, true)) {
            return [
                'safe' => false,
                'reason' => "آپلود فایل‌های اجرایی با پسوند .{$ext} مجاز نمی‌باشد.",
                'threat_type' => 'malicious_ext'
            ];
        }

        // 3. Scan file content for webshell signatures
        $contentCheck = $this->scanContent(file_get_contents($file->getRealPath(), false, null, 0, 512 * 1024)); // first 512KB
        if (!$contentCheck['safe']) {
            return $contentCheck;
        }

        return ['safe' => true];
    }

    /**
     * Scan arbitrary file path on server.
     */
    public function scanPath(string $filePath): array
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return ['safe' => true];
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $content = @file_get_contents($filePath, false, null, 0, 1024 * 1024); // max 1MB
        if ($content === false) {
            return ['safe' => true];
        }

        return $this->scanContent($content);
    }

    /**
     * Scan raw content string for malicious patterns.
     */
    public function scanContent(string $content): array
    {
        foreach ($this->dangerousPatterns as $pattern => $title) {
            if (preg_match($pattern, $content)) {
                return [
                    'safe' => false,
                    'reason' => "الگوی بدافزار یا وب‌شل شناسایی شد: {$title}",
                    'threat_type' => 'webshell_pattern'
                ];
            }
        }

        return ['safe' => true];
    }

    /**
     * Move a suspicious file into quarantine.
     */
    public function quarantine(string $filePath, string $reason, string $threatType = 'suspicious_code'): ?FileQuarantine
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $filename = basename($filePath);
        $quarantineDir = $this->getQuarantineDir();
        $targetName = time() . '_' . preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename) . '.quarantine';
        $targetPath = $quarantineDir . '/' . $targetName;

        $hash = @hash_file('sha256', $filePath);
        $size = @filesize($filePath) ?: 0;

        // Move the file
        if (@rename($filePath, $targetPath) || (copy($filePath, $targetPath) && @unlink($filePath))) {
            $quarantineRecord = FileQuarantine::create([
                'filename' => $filename,
                'original_path' => $filePath,
                'quarantine_path' => $targetPath,
                'reason' => $reason,
                'threat_type' => $threatType,
                'file_hash' => $hash,
                'file_size' => $size,
            ]);

            SecurityEvent::log(
                'file_scan',
                'critical',
                "فایل آلوده به قرنطینه منتقل شد: {$filename}",
                "دلیل: {$reason} | مسیر اصلی: {$filePath}",
                ['quarantine_id' => $quarantineRecord->id, 'hash' => $hash]
            );

            return $quarantineRecord;
        }

        return null;
    }

    /**
     * Scan a directory recursively and quarantine infected files
     */
    public function scanDirectory(string $dir, bool $autoQuarantine = true): array
    {
        $infected = [];
        $scannedCount = 0;

        if (!is_dir($dir)) {
            return ['scanned' => 0, 'infected' => []];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $scannedCount++;
                $filePath = $item->getPathname();
                $scanResult = $this->scanPath($filePath);

                if (!$scanResult['safe']) {
                    $entry = [
                        'file' => $filePath,
                        'reason' => $scanResult['reason'],
                        'threat_type' => $scanResult['threat_type'],
                    ];

                    if ($autoQuarantine && SecuritySetting::isTrue('quarantine_infected', true)) {
                        $this->quarantine($filePath, $scanResult['reason'], $scanResult['threat_type']);
                        $entry['quarantined'] = true;
                    }

                    $infected[] = $entry;
                }
            }
        }

        return [
            'scanned' => $scannedCount,
            'infected' => $infected,
        ];
    }
}
