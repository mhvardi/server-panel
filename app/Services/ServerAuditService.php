<?php

namespace App\Services;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Process;

class ServerAuditService
{
    /**
     * Get a comprehensive server security overview.
     */
    public function getAuditSummary(): array
    {
        return [
            'listening_ports' => $this->getListeningPorts(),
            'suspicious_processes' => $this->getSuspiciousProcesses(),
            'permission_warnings' => $this->checkCriticalPermissions(),
            'sensitive_files' => $this->checkSensitiveFiles(),
            'resource_usage' => $this->getResourceUsage(),
        ];
    }

    /**
     * Inspect open listening ports on the server.
     */
    public function getListeningPorts(): array
    {
        $ports = [];

        // 1. Try ss or netstat
        if (function_exists('shell_exec')) {
            $output = @shell_exec('ss -tuln 2>/dev/null || netstat -tuln 2>/dev/null');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (preg_match('/(tcp|udp)\s+\S+\s+\S+\s+(\S+):(\d+)/i', $line, $matches)) {
                        $proto = strtoupper($matches[1]);
                        $bind = $matches[2];
                        $port = (int) $matches[3];

                        $ports[] = [
                            'protocol' => $proto,
                            'bind' => $bind,
                            'port' => $port,
                            'service' => $this->guessPortService($port),
                            'is_exposed' => ($bind === '0.0.0.0' || $bind === '*' || $bind === '::'),
                        ];
                    }
                }
            }
        }

        // 2. Fallback parse /proc/net/tcp if on Linux
        if (empty($ports) && file_exists('/proc/net/tcp')) {
            $lines = @file('/proc/net/tcp');
            if ($lines) {
                array_shift($lines); // header
                foreach ($lines as $l) {
                    $parts = preg_split('/\s+/', trim($l));
                    if (isset($parts[1], $parts[3]) && $parts[3] === '0A') { // 0A is LISTEN state
                        [$hexIp, $hexPort] = explode(':', $parts[1]);
                        $port = hexdec($hexPort);
                        $ports[] = [
                            'protocol' => 'TCP',
                            'bind' => ($hexIp === '00000000') ? '0.0.0.0' : '127.0.0.1',
                            'port' => $port,
                            'service' => $this->guessPortService($port),
                            'is_exposed' => ($hexIp === '00000000'),
                        ];
                    }
                }
            }
        }

        return $ports;
    }

    /**
     * Guess common service running on port
     */
    protected function guessPortService(int $port): string
    {
        return match ($port) {
            22 => 'SSH',
            80 => 'HTTP (Nginx/Apache)',
            443 => 'HTTPS (Nginx/Apache)',
            3306 => 'MySQL / MariaDB',
            5432 => 'PostgreSQL',
            6379 => 'Redis',
            27017 => 'MongoDB',
            8080 => 'HTTP Alternate',
            9000 => 'PHP-FPM',
            default => 'سرویس سفارشی'
        };
    }

    /**
     * Check for suspicious high-CPU or unexpected processes (e.g., crypto miners)
     */
    public function getSuspiciousProcesses(): array
    {
        $suspicious = [];
        $knownBadNames = ['xmrig', 'minerd', 'cpuminer', 'stratum', 'kdevtmpfsi', 'kinsing', 'pnscan', 'masscan'];

        if (function_exists('shell_exec')) {
            $output = @shell_exec('ps aux --sort=-%cpu 2>/dev/null | head -n 15');
            if ($output) {
                $lines = explode("\n", trim($output));
                array_shift($lines); // Skip header

                foreach ($lines as $line) {
                    $cols = preg_split('/\s+/', trim($line), 11);
                    if (count($cols) >= 11) {
                        $user = $cols[0];
                        $pid = $cols[1];
                        $cpu = (float) $cols[2];
                        $mem = (float) $cols[3];
                        $cmd = $cols[10];

                        $isBad = false;
                        foreach ($knownBadNames as $bad) {
                            if (stripos($cmd, $bad) !== false) {
                                $isBad = true;
                                break;
                            }
                        }

                        if ($isBad || ($cpu > 85.0 && !str_contains($cmd, 'composer') && !str_contains($cmd, 'php artisan'))) {
                            $suspicious[] = [
                                'pid' => $pid,
                                'user' => $user,
                                'cpu' => $cpu,
                                'mem' => $mem,
                                'command' => substr($cmd, 0, 120),
                                'warning' => $isBad ? 'نام پروسس مشابه بدافزار یا ماینر است' : 'مصرف بیش از حد پردازنده (بالای ۸۵٪)',
                            ];
                        }
                    }
                }
            }
        }

        return $suspicious;
    }

    /**
     * Check critical file and folder permissions (no 777 in core directories)
     */
    public function checkCriticalPermissions(): array
    {
        $warnings = [];
        $base = base_path();

        $checkDirs = [
            $base . '/.env' => 'فایل پیکربندی اصلی',
            $base . '/storage' => 'پوشه استوریج',
            $base . '/bootstrap/cache' => 'پوشه کش بوت‌استرپ',
            $base . '/public' => 'پوشه پابلیک وب',
        ];

        foreach ($checkDirs as $path => $desc) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -3);
                if ($perms === '777' || (is_file($path) && in_array($perms, ['777', '666', '766'], true))) {
                    $warnings[] = [
                        'path' => $path,
                        'name' => $desc,
                        'current_perms' => $perms,
                        'recommended' => is_dir($path) ? '755' : '644',
                        'level' => 'danger'
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Check if sensitive configuration files are exposed
     */
    public function checkSensitiveFiles(): array
    {
        $issues = [];
        $base = base_path();

        // 1. Check APP_DEBUG status
        if (config('app.debug') === true) {
            $issues[] = [
                'type' => 'app_debug',
                'title' => 'فعال بودن APP_DEBUG',
                'desc' => 'حالت دیباگ در محیط عمومی فعال است و اطلاعات داخلی سیستم را افشا می‌کند.',
                'level' => 'critical',
            ];
        }

        // 2. Check if .env is inside public folder by mistake
        if (file_exists($base . '/public/.env')) {
            $issues[] = [
                'type' => 'env_in_public',
                'title' => 'قرار داشتن فایل .env در پوشه public',
                'desc' => 'فایل حاوی پسوردها و کلیدهای سرور مستقیماً از طریق وب قابل دانلود است!',
                'level' => 'critical',
            ];
        }

        // 3. Check if .git exists in public
        if (is_dir($base . '/public/.git')) {
            $issues[] = [
                'type' => 'git_in_public',
                'title' => 'پوشه .git در مسیر public',
                'desc' => 'تاریخچه مخزن و کدهای سیستم در دسترس عموم قرار دارد.',
                'level' => 'critical',
            ];
        }

        return $issues;
    }

    /**
     * Fetch CPU, RAM & Disk metrics
     */
    public function getResourceUsage(): array
    {
        $cpu = 0;
        $mem = ['total' => 0, 'used' => 0, 'percent' => 0];
        $disk = ['total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0];

        // Disk
        $diskTotal = @disk_total_space(base_path()) ?: 1;
        $diskFree  = @disk_free_space(base_path()) ?: 0;
        $diskUsed  = $diskTotal - $diskFree;
        $disk = [
            'total_gb' => round($diskTotal / (1024 ** 3), 1),
            'used_gb'  => round($diskUsed / (1024 ** 3), 1),
            'free_gb'  => round($diskFree / (1024 ** 3), 1),
            'percent'  => round(($diskUsed / $diskTotal) * 100, 1),
        ];

        // Memory (/proc/meminfo on Linux)
        if (file_exists('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $t) &&
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $a)) {
                $totalMb = (int) $t[1] / 1024;
                $availMb = (int) $a[1] / 1024;
                $usedMb  = $totalMb - $availMb;
                $mem = [
                    'total_mb' => round($totalMb),
                    'used_mb'  => round($usedMb),
                    'percent'  => round(($usedMb / $totalMb) * 100, 1),
                ];
            }
        }

        // CPU load averages
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            $cpu = $loads[0] ?? 0;
        }

        return [
            'cpu_load' => $cpu,
            'memory'   => $mem,
            'disk'     => $disk,
        ];
    }
}
