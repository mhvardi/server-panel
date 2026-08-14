<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'type',
        'path',
    ];

    public function getDiskUsage(): int
    {
        if (is_dir($this->path)) {
            $cmd = "du -sb " . escapeshellarg($this->path) . " 2>/dev/null";
            $output = exec($cmd);
            if ($output) {
                $parts = explode("\t", $output);
                return (int) $parts[0];
            }
        }
        return 0;
    }

    public function getDatabaseName(): ?string
    {
        $backupSettings = rtrim($this->path, '/') . '/.backup/settings.json';
        if (file_exists($backupSettings)) {
            $data = json_decode(file_get_contents($backupSettings), true);
            if (!empty($data['db_name'])) {
                return $data['db_name'];
            }
        }
        $envPath = rtrim($this->path, '/') . '/.env';
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            if (preg_match('/^DB_DATABASE=(.+)$/m', $env, $matches)) {
                return trim($matches[1], ' "\'');
            }
        }
        return null;
    }

    public function getDbUsage(): int
    {
        $dbName = $this->getDatabaseName();
        if (!$dbName) return 0;
        
        try {
            $result = \Illuminate\Support\Facades\DB::select("
                SELECT SUM(data_length + index_length) AS size
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$dbName]);
            
            return (int) ($result[0]->size ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getTrafficUsage(): int
    {
        $domain = strtolower($this->domain);
        $safeDomain = preg_replace('/[^a-z0-9._-]+/', '-', $domain);
        
        $logs = [
            "/var/log/nginx/{$safeDomain}-access.log",
            "/var/log/nginx/{$safeDomain}-ssl-access.log"
        ];
        
        $totalBytes = 0;
        foreach ($logs as $log) {
            if (file_exists($log) && is_readable($log)) {
                $cmd = "awk '{sum+=$10} END {print sum}' " . escapeshellarg($log) . " 2>/dev/null";
                $output = exec($cmd);
                if (is_numeric(trim($output))) {
                    $totalBytes += (int) trim($output);
                } else {
                    $totalBytes += filesize($log);
                }
            } else {
                // If not readable, try with sudo
                $cmd = "sudo awk '{sum+=$10} END {print sum}' " . escapeshellarg($log) . " 2>/dev/null";
                $output = exec($cmd);
                if (is_numeric(trim($output))) {
                    $totalBytes += (int) trim($output);
                }
            }
        }
        return $totalBytes;
    }

    public function getSslStatus(): array
    {
        if ($this->type !== 'subdomain') {
            return ['status' => 'not_applicable'];
        }

        $domain = $this->domain;
        $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";

        if (!file_exists($certPath)) {
            return ['status' => 'missing'];
        }

        $cmd = "openssl x509 -enddate -noout -in " . escapeshellarg($certPath) . " 2>/dev/null";
        $output = exec($cmd);

        if ($output && preg_match('/notAfter=(.+)/', $output, $matches)) {
            $date = strtotime($matches[1]);
            $days = round(($date - time()) / 86400);

            if ($days <= 0) {
                return ['status' => 'expired', 'days' => 0];
            }
            
            return ['status' => 'valid', 'days' => $days, 'expires_at' => date('Y-m-d', $date)];
        }

        return ['status' => 'unknown'];
    }
}
