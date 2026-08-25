<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NginxService
{
    /**
     * مسیر پیش‌فرض برای فایل‌های config سایت‌های Nginx
     */
    protected string $sitesAvailable;
    protected string $sitesEnabled;

    public function __construct()
    {
        $this->sitesAvailable = config('nginx.sites_available', '/etc/nginx/sites-available');
        $this->sitesEnabled   = config('nginx.sites_enabled', '/etc/nginx/sites-enabled');
    }

    /**
     * یک server block برای دامنه مستقیم (Direct) می‌سازد که به مسیر سرویس پویینت می‌کند.
     * مناسب برای سناریو Direct NS یا Parked Domain با دامنه خارج از آروان.
     */
    public function generateServerBlock(string $domain, string $servicePath, bool $withSsl = false): string
    {
        $wwwDomain = 'www.' . $domain;

        if ($withSsl) {
            return <<<NGINX
server {
    listen 80;
    server_name {$domain} {$wwwDomain};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name {$domain} {$wwwDomain};

    ssl_certificate     /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root {$servicePath}/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX;
        }

        return <<<NGINX
server {
    listen 80;
    server_name {$domain} {$wwwDomain};

    root {$servicePath}/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX;
    }

    /**
     * یک server_name alias به config موجود اضافه می‌کند (Parked Domain که در آروان مدیریت می‌شود).
     * مناسب وقتی دامنه دوم هم از طریق CDN/Proxy آروان روی همان IP است.
     */
    public function addServerAlias(string $primaryDomain, string $aliasedDomain): array
    {
        $task = [
            'action'          => 'add_server_alias',
            'primary_domain'  => $primaryDomain,
            'aliased_domain'  => $aliasedDomain,
            'created_at'      => now()->toIso8601String(),
        ];

        Log::info('NGINX_ADD_ALIAS', $task);
        return $task;
    }

    /**
     * یک config کامل برای دامنه Direct ایجاد می‌کند و تسک را ذخیره می‌کند.
     */
    public function generateDirectConfig(string $domain, string $servicePath): array
    {
        $task = [
            'action'       => 'generate_nginx_config',
            'domain'       => $domain,
            'service_path' => $servicePath,
            'config'       => $this->generateServerBlock($domain, $servicePath),
            'created_at'   => now()->toIso8601String(),
        ];

        Log::info('NGINX_GENERATE_CONFIG', ['domain' => $domain]);
        return $task;
    }

    /**
     * بررسی اینکه آیا یک دامنه روی IP سرور ما resolve می‌شود
     */
    public function domainResolvesToUs(string $domain): bool
    {
        $serverIp = $this->getServerIp();
        if (!$serverIp) return false;

        $resolved = @dns_get_record($domain, DNS_A);
        if (empty($resolved)) return false;

        foreach ($resolved as $record) {
            if (($record['ip'] ?? '') === $serverIp) {
                return true;
            }
        }
        return false;
    }

    /**
     * بررسی CNAME — آیا دامنه به یک host خاص CNAME دارد
     */
    public function domainHasCname(string $domain, string $targetHost): bool
    {
        $resolved = @dns_get_record($domain, DNS_CNAME);
        if (empty($resolved)) return false;

        foreach ($resolved as $record) {
            $cname = rtrim($record['target'] ?? '', '.');
            if ($cname === rtrim($targetHost, '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * IP سرور فعلی
     */
    public function getServerIp(): ?string
    {
        $ip = config('nginx.server_ip') ?: env('SERVER_IP');
        if ($ip) return $ip;

        // تلاش برای دریافت IP عمومی سرور
        try {
            $ip = trim(@file_get_contents('https://api.ipify.org', false, stream_context_create([
                'http' => ['timeout' => 3]
            ])));
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
