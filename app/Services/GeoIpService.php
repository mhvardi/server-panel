<?php

namespace App\Services;

use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    /**
     * Check if an IP address belongs to Iran or private network.
     */
    public function isIranIp(string $ip): bool
    {
        // Whitelist loopback / local IP addresses
        if ($this->isLocalOrPrivateIp($ip)) {
            return true;
        }

        // Check against whitelisted IPs
        if ($this->isWhitelisted($ip)) {
            return true;
        }

        $country = $this->getCountryCode($ip);
        return strtoupper($country) === 'IR';
    }

    /**
     * Check if IP is in company or custom whitelist
     */
    public function isWhitelisted(string $ip): bool
    {
        // Company IP hardcoded default
        $companyIps = ['94.183.100.3', '127.0.0.1', '::1'];
        if (in_array($ip, $companyIps, true)) {
            return true;
        }

        $whitelisted = SecuritySetting::get('whitelisted_ips', '');
        if (!empty($whitelisted)) {
            $list = preg_split('/[\r\n,]+/', $whitelisted);
            foreach ($list as $allowed) {
                $allowed = trim($allowed);
                if ($allowed === '') continue;
                if ($ip === $allowed || $this->ipInRange($ip, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get country code of IP
     */
    public function getCountryCode(string $ip): string
    {
        if ($this->isLocalOrPrivateIp($ip)) {
            return 'LOCAL';
        }

        // 1. Try local MaxMind database if present
        $dbPath = env('GEOIP_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Country.mmdb'));
        if (file_exists($dbPath) && class_exists('\GeoIp2\Database\Reader')) {
            try {
                $reader = new \GeoIp2\Database\Reader($dbPath);
                $record = $reader->country($ip);
                return $record->country->isoCode ?? 'UNKNOWN';
            } catch (\Throwable $e) {
                Log::debug("GeoIP local lookup failed for {$ip}: " . $e->getMessage());
            }
        }

        // 2. Cache-backed fast online lookup (fallback)
        return cache()->remember("geoip_{$ip}", 86400, function () use ($ip) {
            try {
                // Free, fast ip-api.com check with 1.5s timeout
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,countryCode");
                if ($response->successful() && $response->json('status') === 'success') {
                    return $response->json('countryCode') ?? 'UNKNOWN';
                }
            } catch (\Throwable $e) {
                Log::debug("GeoIP online lookup failed for {$ip}: " . $e->getMessage());
            }

            return 'UNKNOWN';
        });
    }

    /**
     * Determine if an IP is local, loopback or private network
     */
    public function isLocalOrPrivateIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Check if IP is in CIDR subnet
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);
        $subnetLong &= $mask;
        return ($ipLong & $mask) === $subnetLong;
    }
}
