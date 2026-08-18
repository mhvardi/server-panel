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

    public function domainMappings()
    {
        return $this->hasMany(\App\Models\DomainMapping::class);
    }

    public function getClientDomains(): array
    {
        return $this->domainMappings()->pluck('source_domain')->filter()->values()->toArray();
    }

    public function getPrimaryDomain(): string
    {
        $clientDomain = $this->domainMappings()->latest()->value('source_domain');
        return !empty($clientDomain) ? trim($clientDomain) : trim($this->domain);
    }

    public function getSslStatus(): array
    {
        if ($this->type !== 'subdomain') {
            return ['status' => 'not_applicable'];
        }

        // The target domain to check is primarily the client's custom domain (if mapped), otherwise service domain
        $targetDomain = $this->getPrimaryDomain();
        if (empty($targetDomain)) {
            return ['status' => 'missing', 'checked_domain' => $this->domain];
        }

        // 1. Try checking live SSL certificate over network (works for any client domain / reverse proxy / Cloudflare / Let's Encrypt)
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $client = @stream_socket_client(
                "ssl://{$targetDomain}:443",
                $errno,
                $errstr,
                3,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($client) {
                $params = stream_context_get_params($client);
                fclose($client);

                if (!empty($params['options']['ssl']['peer_certificate'])) {
                    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                    if (!empty($cert['validTo_time_t'])) {
                        $expiresAt = $cert['validTo_time_t'];
                        $days = round(($expiresAt - time()) / 86400);
                        $issuer = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Let\'s Encrypt');

                        return [
                            'status' => $days > 0 ? 'valid' : 'expired',
                            'days' => max(0, (int) $days),
                            'expires_at' => date('Y-m-d', $expiresAt),
                            'issuer' => $issuer,
                            'checked_domain' => $targetDomain,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Socket check failed, proceed to local certificate file check
        }

        // 2. Check local Let's Encrypt certificate file paths for target domain and service domain
        $domainsToCheck = array_unique(array_filter([$targetDomain, $this->domain]));
        foreach ($domainsToCheck as $dom) {
            $safeDomain = preg_replace('/[^a-z0-9._-]+/', '-', strtolower($dom));
            $possiblePaths = [
                "/etc/letsencrypt/live/{$dom}/fullchain.pem",
                "/etc/letsencrypt/live/{$safeDomain}/fullchain.pem",
                "/etc/letsencrypt/live/{$dom}-0001/fullchain.pem",
            ];

            foreach ($possiblePaths as $certPath) {
                $cmd = "sudo openssl x509 -enddate -noout -in " . escapeshellarg($certPath) . " 2>/dev/null";
                $output = @exec($cmd);

                if ($output && preg_match('/notAfter=(.+)/', $output, $matches)) {
                    $date = strtotime($matches[1]);
                    $days = round(($date - time()) / 86400);

                    return [
                        'status' => $days > 0 ? 'valid' : 'expired',
                        'days' => max(0, (int) $days),
                        'expires_at' => date('Y-m-d', $date),
                        'issuer' => 'Let\'s Encrypt (Local)',
                        'checked_domain' => $dom,
                    ];
                }
            }
        }

        return ['status' => 'missing', 'checked_domain' => $targetDomain];
    }
}
