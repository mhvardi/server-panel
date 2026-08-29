<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncDomainConfigs extends Command
{
    protected $signature = 'domains:sync-nginx {--service= : ID or name of specific service}';
    protected $description = 'Sync and rebuild Nginx configs and BIND DNS zones for all services and domains';

    public function handle()
    {
        $this->info('Starting domain and Nginx config sync...');

        $services = Service::all();
        $isLinux = !str_starts_with(strtoupper(PHP_OS), 'WIN');
        $serverIp = config('nginx.server_ip') ?: env('SERVER_IP', '185.128.139.89');

        foreach ($services as $service) {
            if ($service->type !== 'subdomain') {
                continue;
            }

            $primaryDomain = $service->domain;
            $clientDomains = $service->getClientDomains();
            $allDomains = array_unique(array_filter(array_merge([$primaryDomain], $clientDomains)));

            $serverNames = [];
            foreach ($allDomains as $d) {
                $d = trim($d);
                if (!empty($d)) {
                    $serverNames[] = $d;
                    if (!str_starts_with($d, 'www.')) {
                        $serverNames[] = "www.{$d}";
                    }
                }
            }
            $serverNames = array_unique($serverNames);
            $serverNameStr = implode(' ', $serverNames);

            $this->info("Service [{$service->name}]: Domains => " . implode(', ', $serverNames));

            if ($isLinux) {
                $this->rebuildNginxConfig($service, $serverNameStr);
            }
        }

        // Rebuild BIND9 zone files for all registered domains
        if ($isLinux) {
            $this->syncBindZones($serverIp);
            
            // Reload Nginx & BIND
            exec('sudo nginx -t 2>&1', $nginxTestOut, $nginxTestCode);
            if ($nginxTestCode === 0) {
                exec('sudo systemctl reload nginx 2>&1');
                $this->info('✓ Nginx reloaded successfully.');
            } else {
                $this->error('✗ Nginx configuration error: ' . implode("\n", $nginxTestOut));
            }

            exec('sudo systemctl reload named 2>&1', $namedOut, $namedCode);
            if ($namedCode === 0) {
                $this->info('✓ BIND9 DNS server reloaded successfully.');
            }
        }

        $this->info('Domain and Nginx sync completed!');
        return 0;
    }

    protected function rebuildNginxConfig(Service $service, string $serverNameStr): void
    {
        $fullDomain = $service->domain;
        $real = realpath($service->path) ?: $service->path;
        $rootPath = rtrim($real, '/') . '/public';
        $phpVersion = '8.2';
        if (preg_match('/php(\d+\.\d+)/', shell_exec('php -v 2>&1') ?? '', $m)) {
            $phpVersion = $m[1];
        }
        $phpSock = "/run/php/php{$phpVersion}-fpm.sock";
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullDomain);

        $sslCert = "/etc/letsencrypt/live/{$fullDomain}/fullchain.pem";
        $sslKey = "/etc/letsencrypt/live/{$fullDomain}/privkey.pem";
        $hasSsl = file_exists($sslCert) && file_exists($sslKey);

        $http = "server {\n"
              . "    listen 80;\n"
              . "    listen [::]:80;\n"
              . "    server_name {$serverNameStr};\n"
              . "    root {$rootPath};\n"
              . "    index index.php index.html index.htm;\n"
              . "    charset utf-8;\n"
              . "    access_log /var/log/nginx/{$safe}-access.log;\n"
              . "    error_log  /var/log/nginx/{$safe}-error.log;\n"
              . "    location / {\n"
              . "        try_files \$uri \$uri/ /index.php?\$query_string;\n"
              . "    }\n"
              . "    location = /favicon.ico { access_log off; log_not_found off; }\n"
              . "    location = /robots.txt  { access_log off; log_not_found off; }\n"
              . "    location ~ \\.php$ {\n"
              . "        include snippets/fastcgi-php.conf;\n"
              . "        fastcgi_pass unix:{$phpSock};\n"
              . "    }\n"
              . "    location ~ /\\. {\n"
              . "        deny all;\n"
              . "    }\n"
              . "}\n";

        $content = $http;

        if ($hasSsl) {
            $https = "server {\n"
                   . "    listen 443 ssl;\n"
                   . "    listen [::]:443 ssl;\n"
                   . "    server_name {$serverNameStr};\n"
                   . "    root {$rootPath};\n"
                   . "    index index.php index.html index.htm;\n"
                   . "    charset utf-8;\n"
                   . "    ssl_certificate {$sslCert};\n"
                   . "    ssl_certificate_key {$sslKey};\n"
                   . "    include /etc/letsencrypt/options-ssl-nginx.conf;\n"
                   . "    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;\n"
                   . "    location / {\n"
                   . "        try_files \$uri \$uri/ /index.php?\$query_string;\n"
                   . "    }\n"
                   . "    location = /favicon.ico { access_log off; log_not_found off; }\n"
                   . "    location = /robots.txt  { access_log off; log_not_found off; }\n"
                   . "    location ~ \\.php$ {\n"
                   . "        include snippets/fastcgi-php.conf;\n"
                   . "        fastcgi_pass unix:{$phpSock};\n"
                   . "    }\n"
                   . "    location ~ /\\. {\n"
                   . "        deny all;\n"
                   . "    }\n"
                   . "}\n";
            $content .= "\n" . $https;
        }

        $confName = "service-subdomain-{$safe}.conf";
        $tmpFile = "/tmp/nginx_sync_" . uniqid() . ".conf";
        file_put_contents($tmpFile, $content);

        exec("sudo mv {$tmpFile} /etc/nginx/sites-available/{$confName}");
        exec("sudo chmod 644 /etc/nginx/sites-available/{$confName}");
        exec("sudo ln -sf /etc/nginx/sites-available/{$confName} /etc/nginx/sites-enabled/{$confName}");
    }

    protected function syncBindZones(string $serverIp): void
    {
        $zonesDir = '/etc/bind/zones';
        if (!file_exists($zonesDir)) {
            exec("sudo mkdir -p {$zonesDir}");
        }

        $domains = Domain::pluck('domain')->unique();
        $namedLocalEntries = [];

        foreach ($domains as $domainName) {
            $domainName = strtolower(trim($domainName));
            if (empty($domainName) || str_ends_with($domainName, '.vardicrm.ir')) {
                continue;
            }

            // Extract apex domain (e.g., drfitteam.com from www.drfitteam.com)
            $parts = explode('.', $domainName);
            $apexDomain = count($parts) > 2 ? implode('.', array_slice($parts, -2)) : $domainName;

            $zoneFile = "{$zonesDir}/db.{$apexDomain}";
            $serial = date('Ymd') . '01';

            $zoneContent = "\$TTL    86400\n"
                . "@       IN      SOA     ns1.vardicrm.ir. admin.vardicrm.ir. (\n"
                . "                        {$serial}      ; Serial\n"
                . "                        3600            ; Refresh\n"
                . "                        1800            ; Retry\n"
                . "                        604800          ; Expire\n"
                . "                        86400 )         ; Minimum\n\n"
                . "@       IN      NS      ns1.vardicrm.ir.\n"
                . "@       IN      NS      ns2.vardicrm.ir.\n\n"
                . "@       IN      A       {$serverIp}\n"
                . "www     IN      A       {$serverIp}\n"
                . "*       IN      A       {$serverIp}\n";

            $tmpZone = "/tmp/zone_" . uniqid() . ".db";
            file_put_contents($tmpZone, $zoneContent);
            exec("sudo mv {$tmpZone} {$zoneFile}");
            exec("sudo chmod 644 {$zoneFile}");

            $namedLocalEntries[] = "zone \"{$apexDomain}\" {\n    type master;\n    file \"{$zoneFile}\";\n};";
        }

        if (!empty($namedLocalEntries)) {
            $namedConfLocal = "/etc/bind/named.conf.local";
            $existingConf = file_exists($namedConfLocal) ? file_get_contents($namedConfLocal) : '';

            $newEntries = [];
            foreach ($namedLocalEntries as $entry) {
                preg_match('/zone "([^"]+)"/', $entry, $m);
                if (!empty($m[1]) && !str_contains($existingConf, "zone \"{$m[1]}\"")) {
                    $newEntries[] = $entry;
                }
            }

            if (!empty($newEntries)) {
                $tmpNamed = "/tmp/named_" . uniqid() . ".conf";
                file_put_contents($tmpNamed, $existingConf . "\n" . implode("\n\n", $newEntries) . "\n");
                exec("sudo mv {$tmpNamed} {$namedConfLocal}");
                exec("sudo chmod 644 {$namedConfLocal}");
            }
        }
    }
}
