<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/**
 * ServerStatsService collects real-time metrics from the host machine.
 *
 * This service exposes methods to fetch CPU, memory, disk usage, load averages,
 * uptime, kernel information, service status, process lists, queue/job counts,
 * SSH login history and a simple backup status placeholder. It also logs metrics
 * into cache for short‑term historical charts.
 */
class ServerStatsService
{
    /**
     * Primary entry point for dashboard overview data.
     *
     * @param string $diskMount The mount point to compute disk usage against.
     * @return array
     */
    public function getOverview(string $diskMount = '/'): array
    {
        $cpu = $this->getCpuUsagePercent();

        $memStats  = $this->getMemoryUsageStats();
        $diskStats = $this->getDiskUsageStats($diskMount);

        $mem  = $memStats['percent'];
        $disk = $diskStats['percent'];

        $uptimeSeconds = $this->getUptimeSeconds();

        // log metrics for trend charts
        $this->logMetrics([
            'cpu'  => $cpu,
            'mem'  => $mem,
            'disk' => $disk,
        ]);

        // compute statuses for common services
        $services = [
            'nginx'   => 'nginx',
            'php-fpm' => $this->detectPhpFpmServiceName(),
            'mysql'   => $this->detectMysqlServiceName(),
            'redis'   => 'redis-server',
            'supervisor' => 'supervisor',
        ];
        $serviceStatus = $this->getSystemdStatuses(array_filter($services));
        $activeServices = collect($serviceStatus)
            ->filter(fn ($s) => ($s['state'] ?? '') === 'active')
            ->count();

        // compute simple health status
        $health = $this->computeHealthStatus($cpu, $mem, $disk, $serviceStatus);

        // build alerts list
        $alerts = $this->buildAlerts([
            'cpu_usage'      => $cpu,
            'memory_usage'   => $mem,
            'disk_usage'     => $disk,
            'service_status' => $serviceStatus,
        ]);

        // queue/job info
        $jobsInfo = $this->getQueueStats();

        return [
            'cpu_usage'        => $cpu,
            'memory_usage'     => $mem,
            'disk_usage'       => $disk,
            'uptime'           => $this->formatUptime($uptimeSeconds),
            'uptime_seconds'   => $uptimeSeconds,
            'health_status'    => $health,
            'service_status'   => $serviceStatus,
            'active_services'  => $activeServices,
            'load_avg'         => $this->getLoadAverage(),
            'hostname'         => gethostname() ?: 'Unknown',
            'server_ip'        => $this->getServerIp(),
            'os_name'          => php_uname('s'),
            'kernel_version'   => php_uname('r'),
            'last_reboot'      => $this->getLastReboot(),
            'alerts'           => $alerts,
            'warnings'         => count($alerts),
            'top_processes'    => $this->getTopProcesses(5),
            'queue'            => $jobsInfo['pending'] ?? 0,
            'failed_jobs'      => $jobsInfo['failed'] ?? 0,
            'metrics_history'  => $this->getMetricsHistory(30),
            'login_history'    => $this->getLoginHistory(5),
            'backup_status'    => $this->getBackupStatus(),
            'last_updated_at'  => now()->format('Y-m-d H:i:s'),
            'memory_used_gb'   => $memStats['used_gb'],
            'memory_total_gb'  => $memStats['total_gb'],
            'disk_used_gb'     => $diskStats['used_gb'],
            'disk_total_gb'    => $diskStats['total_gb'],
        ];
    }

    /**
     * Resolves the server local or public IP address with 24-hour cache.
     */
    public function getServerIp(): string
    {
        return Cache::remember('server_detected_ip', 86400, function () {
            $ip = request()->server('SERVER_ADDR');
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
            $host = gethostname();
            if ($host) {
                $resolved = @gethostbyname($host);
                if ($resolved && filter_var($resolved, FILTER_VALIDATE_IP)) {
                    return $resolved;
                }
            }
            return '127.0.0.1';
        });
    }

    /**
     * Returns load average for 1, 5 and 15 minute intervals.
     */
    public function getLoadAverage(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
        if (!$load || count($load) < 3) {
            return [0, 0, 0];
        }
        return [round($load[0], 2), round($load[1], 2), round($load[2], 2)];
    }

    /**
     * Calculates CPU usage percent without blocking (zero sleep).
     * Uses delta calculation between current /proc/stat and previously cached sample.
     */
    public function getCpuUsagePercent(): int
    {
        $curr = $this->readCpuStat();
        if (!$curr) {
            $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
            if ($load && isset($load[0])) {
                return (int) max(0, min(100, round($load[0] * 100)));
            }
            return 0;
        }

        $now = microtime(true);
        $prev = Cache::get('server_stats_cpu_last_sample');
        $cachedUsage = Cache::get('server_stats_cpu_usage_cached');

        if (is_array($prev) && isset($prev['stat'], $prev['time'])) {
            $elapsed = $now - (float) $prev['time'];

            // If sampled very recently (< 0.8s), return the cached percentage to prevent division by near-zero delta
            if ($elapsed < 0.8 && $cachedUsage !== null) {
                return (int) $cachedUsage;
            }

            $pStat = $prev['stat'];
            $idlePrev = $pStat['idle'] + $pStat['iowait'];
            $idleCurr = $curr['idle'] + $curr['iowait'];

            $nonPrev = $pStat['user'] + $pStat['nice'] + $pStat['system'] + $pStat['irq'] + $pStat['softirq'] + $pStat['steal'];
            $nonCurr = $curr['user'] + $curr['nice'] + $curr['system'] + $curr['irq'] + $curr['softirq'] + $curr['steal'];

            $totalPrev = $idlePrev + $nonPrev;
            $totalCurr = $idleCurr + $nonCurr;

            $totalDelta = $totalCurr - $totalPrev;
            $idleDelta = $idleCurr - $idlePrev;

            if ($totalDelta > 0) {
                $usage = (1.0 - ($idleDelta / $totalDelta)) * 100.0;
                $percent = (int) max(0, min(100, round($usage)));

                // Update sample & cached usage
                Cache::put('server_stats_cpu_last_sample', ['stat' => $curr, 'time' => $now], 120);
                Cache::put('server_stats_cpu_usage_cached', $percent, 120);

                return $percent;
            }
        }

        // Cold start (no valid previous sample in cache):
        // Take a single ultra-short sample (25ms) once to prime the cache
        usleep(25_000);
        $next = $this->readCpuStat();
        if ($next) {
            $idleA = $curr['idle'] + $curr['iowait'];
            $idleB = $next['idle'] + $next['iowait'];
            $nonA  = $curr['user'] + $curr['nice'] + $curr['system'] + $curr['irq'] + $curr['softirq'] + $curr['steal'];
            $nonB  = $next['user'] + $next['nice'] + $next['system'] + $next['irq'] + $next['softirq'] + $next['steal'];

            $totalA = $idleA + $nonA;
            $totalB = $idleB + $nonB;
            $totald = $totalB - $totalA;
            $usage = 0;
            if ($totald > 0) {
                $idled = $idleB - $idleA;
                $usage = (1.0 - ($idled / $totald)) * 100.0;
            }
            $percent = (int) max(0, min(100, round($usage)));
            Cache::put('server_stats_cpu_last_sample', ['stat' => $next, 'time' => microtime(true)], 120);
            Cache::put('server_stats_cpu_usage_cached', $percent, 120);
            return $percent;
        }

        Cache::put('server_stats_cpu_last_sample', ['stat' => $curr, 'time' => $now], 120);
        return (int) ($cachedUsage ?? 0);
    }

    /**
     * Reads first line of /proc/stat and returns CPU counters.
     */
    private function readCpuStat(): ?array
    {
        $line = @file('/proc/stat')[0] ?? null;
        if (!$line) {
            return null;
        }
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) < 8 || $parts[0] !== 'cpu') {
            return null;
        }
        return [
            'user'    => (int) ($parts[1] ?? 0),
            'nice'    => (int) ($parts[2] ?? 0),
            'system'  => (int) ($parts[3] ?? 0),
            'idle'    => (int) ($parts[4] ?? 0),
            'iowait'  => (int) ($parts[5] ?? 0),
            'irq'     => (int) ($parts[6] ?? 0),
            'softirq' => (int) ($parts[7] ?? 0),
            'steal'   => (int) ($parts[8] ?? 0),
        ];
    }

    /**
     * Calculates memory usage percentage.
     */
    public function getMemoryUsagePercent(): int
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return 0;
        }
        preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $availMatch);
        $total = (int) ($totalMatch[1] ?? 0);
        $avail = (int) ($availMatch[1] ?? 0);
        if ($total <= 0) {
            return 0;
        }
        $used = $total - $avail;
        $usage = ($used / $total) * 100;
        return (int) max(0, min(100, round($usage)));
    }

    /**
     * Calculates disk usage percentage for a mount point.
     */
    public function getDiskUsagePercent(string $mountPoint = '/'): int
    {
        $total = @disk_total_space($mountPoint);
        $free  = @disk_free_space($mountPoint);
        if (!$total || !$free) {
            return 0;
        }
        $used = $total - $free;
        $usage = ($used / $total) * 100;
        return (int) max(0, min(100, round($usage)));
    }

    public function getMemoryUsageStats(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return ['percent' => 0, 'used_gb' => 0, 'total_gb' => 0, 'available_gb' => 0];
        }

        preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $availMatch);

        $totalKb = (int) ($totalMatch[1] ?? 0);
        $availKb = (int) ($availMatch[1] ?? 0);

        if ($totalKb <= 0) {
            return ['percent' => 0, 'used_gb' => 0, 'total_gb' => 0, 'available_gb' => 0];
        }

        $usedKb = max(0, $totalKb - $availKb);

        $percent = (int) max(0, min(100, round(($usedKb / $totalKb) * 100)));

        // GiB (1024 base)
        $totalGb = round($totalKb / 1024 / 1024, 2);
        $usedGb  = round($usedKb / 1024 / 1024, 2);
        $availGb = round($availKb / 1024 / 1024, 2);

        return [
            'percent'       => $percent,
            'used_gb'       => $usedGb,
            'total_gb'      => $totalGb,
            'available_gb'  => $availGb,
        ];
    }

    public function getDiskUsageStats(string $mountPoint = '/'): array
    {
        $cacheKey = 'server_disk_stats_' . md5($mountPoint);

        return Cache::remember($cacheKey, 30, function () use ($mountPoint) {
            $total = @disk_total_space($mountPoint);
            $free  = @disk_free_space($mountPoint);

            if (!$total || !$free) {
                return ['percent' => 0, 'used_gb' => 0, 'total_gb' => 0, 'free_gb' => 0];
            }

            $used = max(0, $total - $free);

            $percent = (int) max(0, min(100, round(($used / $total) * 100)));

            $totalGb = round($total / 1024 / 1024 / 1024, 2);
            $usedGb  = round($used  / 1024 / 1024 / 1024, 2);
            $freeGb  = round($free  / 1024 / 1024 / 1024, 2);

            return [
                'percent'  => $percent,
                'used_gb'  => $usedGb,
                'total_gb' => $totalGb,
                'free_gb'  => $freeGb,
            ];
        });
    }

    /**
     * Returns system uptime in seconds.
     */
    public function getUptimeSeconds(): int
    {
        $content = @file_get_contents('/proc/uptime');
        if (!$content) {
            return 0;
        }
        $parts = explode(' ', trim($content));
        return (int) floor((float) ($parts[0] ?? 0));
    }

    /**
     * Formats seconds into human‑readable days, hours and minutes.
     */
    public function formatUptime(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'Unknown';
        }
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        return "{$days} days, {$hours} hours, {$minutes} mins";
    }

    /**
     * Determines if a systemd unit exists.
     */
    private function isUnitExists(string $unit): bool
    {
        try {
            // LoadState returns: loaded | not-found | masked | ...
            $res = Process::run('systemctl show ' . escapeshellarg($unit) . ' -p LoadState --value');
            $loadState = trim($res->output() ?: '');

            return $loadState !== '' && $loadState !== 'not-found';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Detects PHP-FPM service name with 24-hour cache.
     */
    private function detectPhpFpmServiceName(): ?string
    {
        return Cache::remember('server_detected_unit_php_fpm', 86400, function () {
            $candidates = ['php8.2-fpm', 'php8.3-fpm', 'php8.1-fpm', 'php-fpm'];
            foreach ($candidates as $c) {
                if ($this->isUnitExists($c)) {
                    return $c;
                }
            }
            return null;
        });
    }

    /**
     * Detects MySQL/MariaDB service name with 24-hour cache.
     */
    private function detectMysqlServiceName(): ?string
    {
        return Cache::remember('server_detected_unit_mysql', 86400, function () {
            $candidates = ['mysql', 'mariadb'];
            foreach ($candidates as $c) {
                if ($this->isUnitExists($c)) {
                    return $c;
                }
            }
            return 'mysql';
        });
    }

    /**
     * Returns status for a list of systemd services.
     * Batches all units into a single command and caches results for 15 seconds.
     */
    public function getSystemdStatuses(array $serviceNames): array
    {
        $cacheKey = 'server_systemd_statuses_' . md5(json_encode($serviceNames));

        return Cache::remember($cacheKey, 15, function () use ($serviceNames) {
            $out = [];
            if (empty($serviceNames)) {
                return $out;
            }

            // Batch all service units in a single systemctl command
            $units = array_values($serviceNames);
            $escapedUnits = array_map('escapeshellarg', $units);

            try {
                $cmd = 'systemctl is-active ' . implode(' ', $escapedUnits);
                $res = Process::run($cmd);
                $lines = explode("\n", trim($res->output() ?: ''));

                $i = 0;
                foreach ($serviceNames as $key => $unit) {
                    $state = isset($lines[$i]) && trim($lines[$i]) !== '' ? trim($lines[$i]) : 'unknown';
                    $out[$key] = [
                        'unit'  => $unit,
                        'state' => $state,
                    ];
                    $i++;
                }
            } catch (\Throwable $e) {
                foreach ($serviceNames as $key => $unit) {
                    $out[$key] = [
                        'unit'  => $unit,
                        'state' => 'unknown',
                    ];
                }
            }

            return $out;
        });
    }

    /**
     * Logs metrics to cache for trend charts.
     * Rate-limited to record at most once every 30 seconds.
     */
    private function logMetrics(array $values): void
    {
        $lastLogged = (int) Cache::get('server_metrics_last_logged_at', 0);
        $now = Carbon::now()->timestamp;

        // Rate limit: record at most once every 30 seconds
        if (($now - $lastLogged) < 30 && Cache::has('server_metrics')) {
            return;
        }

        Cache::put('server_metrics_last_logged_at', $now, now()->addHours(2));

        $metrics = Cache::get('server_metrics', []);
        $metrics[] = [
            'timestamp' => $now,
            'cpu'       => $values['cpu'],
            'mem'       => $values['mem'],
            'disk'      => $values['disk'],
        ];
        if (count($metrics) > 60) {
            $metrics = array_slice($metrics, -60);
        }
        Cache::put('server_metrics', $metrics, now()->addHours(2));
    }

    /**
     * Returns recent metrics history up to a maximum of $limit records.
     */
    public function getMetricsHistory(int $limit = 30): array
    {
        $metrics = Cache::get('server_metrics', []);
        return array_slice($metrics, -$limit);
    }

    /**
     * Builds alerts array based on thresholds and service status.
     */
    private function buildAlerts(array $stats): array
    {
        $alerts = [];
        $cpu  = (int) ($stats['cpu_usage'] ?? 0);
        $mem  = (int) ($stats['memory_usage'] ?? 0);
        $disk = (int) ($stats['disk_usage'] ?? 0);
        // CPU alerts
        if ($cpu >= 95) {
            $alerts[] = ['type' => 'danger', 'icon' => 'microchip', 'message' => "CPU usage extremely high ({$cpu}%)", 'time' => Carbon::now()->format('H:i:s')];
        } elseif ($cpu >= 80) {
            $alerts[] = ['type' => 'warning', 'icon' => 'microchip', 'message' => "CPU usage high ({$cpu}%)", 'time' => Carbon::now()->format('H:i:s')];
        }
        // Memory alerts
        if ($mem >= 90) {
            $alerts[] = ['type' => 'danger', 'icon' => 'memory', 'message' => "Memory usage extremely high ({$mem}%)", 'time' => Carbon::now()->format('H:i:s')];
        } elseif ($mem >= 80) {
            $alerts[] = ['type' => 'warning', 'icon' => 'memory', 'message' => "Memory usage high ({$mem}%)", 'time' => Carbon::now()->format('H:i:s')];
        }
        // Disk alerts
        if ($disk >= 95) {
            $alerts[] = ['type' => 'danger', 'icon' => 'hdd', 'message' => "Disk usage extremely high ({$disk}%)", 'time' => Carbon::now()->format('H:i:s')];
        } elseif ($disk >= 85) {
            $alerts[] = ['type' => 'warning', 'icon' => 'hdd', 'message' => "Disk usage high ({$disk}%)", 'time' => Carbon::now()->format('H:i:s')];
        }
        // Service alerts
        $serviceStatus = $stats['service_status'] ?? [];
        foreach ($serviceStatus as $key => $info) {
            $state = $info['state'] ?? 'unknown';
            if (in_array($state, ['failed', 'inactive', 'unknown'], true)) {
                $unit = $info['unit'] ?? $key;
                $alerts[] = ['type' => 'danger', 'icon' => 'exclamation-circle', 'message' => "Service {$unit} status: {$state}", 'time' => Carbon::now()->format('H:i:s')];
            }
        }
        return $alerts;
    }

    /**
     * Computes a simple health status string based on thresholds.
     */
    private function computeHealthStatus(int $cpu, int $mem, int $disk, array $services): string
    {
        // if any service is down or metrics exceed 95
        if ($cpu >= 95 || $mem >= 90 || $disk >= 95) {
            return 'critical';
        }
        foreach ($services as $info) {
            $state = $info['state'] ?? 'unknown';
            if (!in_array($state, ['active'], true)) {
                return 'degraded';
            }
        }
        // moderate range
        if ($cpu >= 80 || $mem >= 80 || $disk >= 85) {
            return 'degraded';
        }
        return 'healthy';
    }

    /**
     * Returns top N processes sorted by CPU usage with 15s cache.
     */
    public function getTopProcesses(int $limit = 5): array
    {
        return Cache::remember('server_top_processes_' . $limit, 15, function () use ($limit) {
            $out = [];
            try {
                $cmd = 'ps -eo pid,user,%cpu,%mem,command --sort=-%cpu | head -n ' . (int) ($limit + 1);
                $result = Process::run($cmd);
                if ($result->successful()) {
                    $lines = array_filter(explode("\n", trim($result->output())));
                    // remove header
                    array_shift($lines);
                    foreach ($lines as $line) {
                        $parts = preg_split('/\s+/', trim($line), 5);
                        if (count($parts) < 5) {
                            continue;
                        }
                        [$pid, $user, $cpuUsage, $memUsage, $command] = $parts;
                        $out[] = [
                            'pid'     => (int) $pid,
                            'user'    => $user,
                            'cpu'     => (float) $cpuUsage,
                            'mem'     => (float) $memUsage,
                            'command' => mb_strimwidth($command, 0, 50, '…'),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
            return $out;
        });
    }

    /**
     * Retrieves queue and failed job counts from database with 15s cache.
     */
    public function getQueueStats(): array
    {
        return Cache::remember('server_queue_stats', 15, function () {
            $pending = 0;
            $failed  = 0;
            try {
                if (DB::getSchemaBuilder()->hasTable('jobs')) {
                    $pending = DB::table('jobs')->count();
                }
                if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                    $failed = DB::table('failed_jobs')->count();
                }
            } catch (\Throwable $e) {
                // ignore errors
            }
            return ['pending' => $pending, 'failed' => $failed];
        });
    }

    /**
     * Returns the last reboot time without executing external commands when possible.
     */
    public function getLastReboot(): string
    {
        return Cache::remember('server_last_reboot', 86400, function () {
            $uptimeSeconds = $this->getUptimeSeconds();
            if ($uptimeSeconds > 0) {
                return date('Y-m-d H:i', time() - $uptimeSeconds);
            }
            try {
                $result = Process::run('who -b');
                if ($result->successful()) {
                    $parts = preg_split('/\s+/', trim($result->output()));
                    return implode(' ', array_slice($parts, -2));
                }
            } catch (\Throwable $e) {
                // ignore
            }
            return 'Unknown';
        });
    }

    /**
     * Retrieves a limited number of SSH login attempts.
     * Uses O(1) tail-seeking and 60s cache instead of full-file grep.
     */
    public function getLoginHistory(int $limit = 5): array
    {
        return Cache::remember('server_login_history_' . $limit, 60, function () use ($limit) {
            $paths = ['/var/log/auth.log', '/var/log/secure'];
            $logFile = null;
            foreach ($paths as $p) {
                if (@is_readable($p)) {
                    $logFile = $p;
                    break;
                }
            }
            if (!$logFile) {
                return [];
            }

            // Efficiently read only the tail of the log file without scanning the full file
            $lines = $this->readTailLines($logFile, 150);
            if (empty($lines)) {
                return [];
            }

            $history = [];
            foreach (array_reverse($lines) as $line) {
                if (str_contains($line, 'sshd[') && preg_match('/^(\w+\s+\d+\s+\d+:\d+:\d+)\s+[^\s]+\s+sshd\[[^\]]+\]:\s+(Accepted|Failed)\s+(?:publickey|password)\s+for\s+(\w+)\s+from\s+([\d\.]+).*/i', $line, $m)) {
                    $history[] = [
                        'timestamp' => $m[1] ?? '',
                        'result'    => strtolower($m[2] ?? ''),
                        'user'      => $m[3] ?? '',
                        'ip'        => $m[4] ?? '',
                    ];
                    if (count($history) >= $limit) {
                        break;
                    }
                }
            }

            return $history;
        });
    }

    /**
     * Efficiently reads the last N lines from a file using fseek (O(1) memory and disk I/O).
     */
    private function readTailLines(string $filePath, int $maxLines = 150, int $chunkSize = 65536): array
    {
        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            try {
                $res = Process::run('tail -n ' . (int) $maxLines . ' ' . escapeshellarg($filePath));
                if ($res->successful()) {
                    return array_filter(explode("\n", trim($res->output())));
                }
            } catch (\Throwable $e) {}
            return [];
        }

        $size = @filesize($filePath) ?: 0;
        if ($size <= 0) {
            fclose($fp);
            return [];
        }

        $offset = max(0, $size - $chunkSize);
        fseek($fp, $offset);
        $data = fread($fp, $chunkSize);
        fclose($fp);

        if ($data === false || $data === '') {
            return [];
        }

        $lines = explode("\n", $data);
        if ($offset > 0 && count($lines) > 0) {
            array_shift($lines); // remove partial first line
        }

        return array_slice(array_filter($lines), -$maxLines);
    }

    /**
     * Returns a placeholder backup status. Extend this when backup integration exists.
     */
    private function getBackupStatus(): array
    {
        // If you have backup logs or database, implement here. Otherwise return not configured.
        return [
            'status'  => 'not_configured',
            'message' => 'Backup status not available. Configure backups to enable this widget.',
        ];
    }
}