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
            'memory_used_gb'  => $memStats['used_gb'],
            'memory_total_gb' => $memStats['total_gb'],

            'disk_used_gb'    => $diskStats['used_gb'],
            'disk_total_gb'   => $diskStats['total_gb'],

        ];
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
     * Calculates CPU usage over a short interval.
     */
    public function getCpuUsagePercent(): int
    {
        $a = $this->readCpuStat();
        usleep(200_000); // sample delay of 200ms
        $b = $this->readCpuStat();
        if (!$a || !$b) {
            return 0;
        }
        $idleA  = $a['idle'] + $a['iowait'];
        $idleB  = $b['idle'] + $b['iowait'];
        $nonA   = $a['user'] + $a['nice'] + $a['system'] + $a['irq'] + $a['softirq'] + $a['steal'];
        $nonB   = $b['user'] + $b['nice'] + $b['system'] + $b['irq'] + $b['softirq'] + $b['steal'];
        $totalA = $idleA + $nonA;
        $totalB = $idleB + $nonB;
        $totald = $totalB - $totalA;
        if ($totald <= 0) {
            return 0;
        }
        $idled = $idleB - $idleA;
        $usage = (1 - ($idled / $totald)) * 100;
        return (int) max(0, min(100, round($usage)));
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
     * Detects PHP-FPM service name (depending on version) or returns null.
     */
    private function detectPhpFpmServiceName(): ?string
    {
        $candidates = ['php8.2-fpm'];
        foreach ($candidates as $c) {
            if ($this->isUnitExists($c)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Detects MySQL/MariaDB service name.
     */
    private function detectMysqlServiceName(): ?string
    {
        $candidates = ['mysql', 'mariadb'];
        foreach ($candidates as $c) {
            if ($this->isUnitExists($c)) {
                return $c;
            }
        }
        return 'mysql';
    }

    /**
     * Returns status for a list of systemd services.
     */
    private function getSystemdStatuses(array $serviceNames): array
    {
        $out = [];
        foreach ($serviceNames as $key => $unit) {
            $state = 'unknown';
            try {
                $res = Process::run('systemctl is-active ' . escapeshellarg($unit));
                if ($res->successful()) {
                    $state = trim($res->output());
                } else {
                    $state = trim($res->output() ?: $res->errorOutput()) ?: 'unknown';
                }
            } catch (\Throwable $e) {
                $state = 'unknown';
            }
            $out[$key] = [
                'unit'  => $unit,
                'state' => $state,
            ];
        }
        return $out;
    }

    /**
     * Logs metrics to cache for trend charts.
     * Stores at most the 100 latest entries.
     */
    private function logMetrics(array $values): void
    {
        $metrics = Cache::get('server_metrics', []);
        $metrics[] = [
            'timestamp' => Carbon::now()->timestamp,
            'cpu'       => $values['cpu'],
            'mem'       => $values['mem'],
            'disk'      => $values['disk'],
        ];
        if (count($metrics) > 100) {
            $metrics = array_slice($metrics, -100);
        }
        Cache::put('server_metrics', $metrics, now()->addHours(1));
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
     * Returns top N processes sorted by CPU usage.
     */
    public function getTopProcesses(int $limit = 5): array
    {
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
    }

    /**
     * Retrieves queue and failed job counts from database.
     */
    public function getQueueStats(): array
    {
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
    }

    /**
     * Returns the last reboot time by parsing the output of who -b.
     */
    private function getLastReboot(): string
    {
        try {
            $result = Process::run('who -b');
            if ($result->successful()) {
                // sample output: " system boot  2023-09-28 12:45"
                $parts = preg_split('/\s+/', trim($result->output()));
                $date = implode(' ', array_slice($parts, -2));
                return $date;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 'Unknown';
    }

    /**
     * Retrieves a limited number of SSH login attempts.
     */
    public function getLoginHistory(int $limit = 5): array
    {
        $history = [];
        $paths = ['/var/log/auth.log', '/var/log/secure'];
        $logFile = null;
        foreach ($paths as $p) {
            if (is_readable($p)) {
                $logFile = $p;
                break;
            }
        }
        if (!$logFile) {
            return [];
        }
        try {
            $cmd = 'grep -E "sshd\[" ' . escapeshellarg($logFile) . ' | tail -n ' . (int) ($limit * 10);
            $result = Process::run($cmd);
            if ($result->successful()) {
                $lines = array_reverse(array_filter(explode("\n", trim($result->output()))));
                foreach ($lines as $line) {
                    // typical line: "Jan  6 10:15:23 server sshd[12345]: Accepted password for user from 1.2.3.4 port 22"
                    if (preg_match('/^(\w+\s+\d+\s+\d+:\d+:\d+)\s+[^\s]+\s+sshd\[[^\]]+\]:\s+(Accepted|Failed)\s+(?:publickey|password)\s+for\s+(\w+)\s+from\s+([\d\.]+).*/i', $line, $m)) {
                        $history[] = [
                            'timestamp' => $m[1] ?? '',
                            'result'    => strtolower($m[2] ?? ''), // accepted/failed
                            'user'      => $m[3] ?? '',
                            'ip'        => $m[4] ?? '',
                        ];
                    }
                    if (count($history) >= $limit) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $history;
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