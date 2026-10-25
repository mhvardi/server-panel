<?php

namespace App\Services;

use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    protected static ?array $iranRanges = null;

    /**
     * Check if an IP address belongs to Iran or private network.
     */
    public function isIranIp(string $ip): bool
    {
        // 1. Whitelist loopback / local IP addresses
        if ($this->isLocalOrPrivateIp($ip)) {
            return true;
        }

        // 2. Check against whitelisted IPs
        if ($this->isWhitelisted($ip)) {
            return true;
        }

        // 3. Instantaneous offline RIPE NCC Iran CIDR lookup (0.0005ms)
        if ($this->isIranIpOffline($ip)) {
            return true;
        }

        // 4. Fallback to country lookup
        $country = $this->getCountryCode($ip);
        return strtoupper($country) === 'IR';
    }

    /**
     * Determine if an IP is within Iran IPv4 ranges using offline binary search.
     */
    public function isIranIpOffline(string $ip): bool
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        $target = (float) sprintf('%u', $ipLong);
        if ($target <= 0) {
            return false;
        }

        if (static::$iranRanges === null) {
            $path = resource_path('data/iran_ipv4_ranges.json');
            if (file_exists($path)) {
                static::$iranRanges = json_decode(file_get_contents($path), true) ?: [];
            } else {
                static::$iranRanges = [];
            }
        }

        $ranges = static::$iranRanges;
        $low = 0;
        $high = count($ranges) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $start = $ranges[$mid][0];
            $end = $ranges[$mid][1];

            if ($target >= $start && $target <= $end) {
                return true;
            }

            if ($target < $start) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return false;
    }

    /**
     * Check if IP is in company or custom whitelist
     */
    public function isWhitelisted(string $ip): bool
    {
        // Company and admin IP defaults
        $companyIps = ['94.183.100.3', '89.40.242.238', '127.0.0.1', '::1'];
        if (in_array($ip, $companyIps, true)) {
            return true;
        }

        try {
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
        } catch (\Throwable $e) {
            // ignore
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

        // Fast offline check: If in Iran ranges, immediately return IR without external API
        if ($this->isIranIpOffline($ip)) {
            return 'IR';
        }

        // 1. Try local MaxMind database if present
        $dbPath = env('GEOIP_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Country.mmdb'));
        if (file_exists($dbPath) && class_exists('\GeoIp2\Database\Reader')) {
            try {
                $reader = new \GeoIp2\Database\Reader($dbPath);
                $record = $reader->country($ip);
                $code = $record->country->isoCode;
                if ($code) {
                    return substr(strtoupper($code), 0, 5);
                }
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
                    $code = $response->json('countryCode');
                    if ($code) {
                        return substr(strtoupper($code), 0, 5);
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("GeoIP online lookup failed for {$ip}: " . $e->getMessage());
            }

            return 'UN';
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
