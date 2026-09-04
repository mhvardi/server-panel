<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesZipUpdates;
use App\Models\Service;
use App\Services\ArvanDnsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class ServiceController extends Controller
{
    use HandlesZipUpdates;

    private string $nginxSitesAvailable = '/etc/nginx/sites-available';
    private string $nginxSitesEnabled   = '/etc/nginx/sites-enabled';
    private string $nginxConfigPath     = '/etc/nginx/server-panel-services';
    private string $linuxBasePath       = '/var/www/service';
    private string $mainSslDomainEnvKey = 'APP_MAIN_DOMAIN';

    public function manualUpdate(Request $request, Service $service)
    {
        // Increase time and memory limits for large file processing
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate([
            'update_zip' => 'required|file',
            'overwrite_composer' => 'nullable|boolean',
        ]);

        $zipFile = $request->file('update_zip');

        if ($zipFile->getClientOriginalExtension() !== 'zip') {
            return back()->with('error', 'لطفاً یک فایل با پسوند .zip آپلود کنید.');
        }

        $zip = new ZipArchive;
        $tempDir = storage_path('app/temp_update_' . time() . '_' . Str::random(5));

        if ($zip->open($zipFile->getRealPath()) === TRUE) {
            File::makeDirectory($tempDir, 0755, true, true);
            $zip->extractTo($tempDir);
            $zip->close();

            $extractedFolders = File::directories($tempDir);
            $sourceDir = count($extractedFolders) === 1 && count(File::files($tempDir)) === 0
                ? $extractedFolders[0]
                : $tempDir;

            // Define files to be excluded from deletion
            $excludedFiles = ['.env', '.env.backup', 'storage', 'public/uploads'];
            if (!$request->has('overwrite_composer')) {
                $excludedFiles[] = 'composer.json';
                $excludedFiles[] = 'composer.lock';
            }

            $this->syncFiles($sourceDir, $service->path, $excludedFiles);

            File::deleteDirectory($tempDir);

            $message = "سرویس با موفقیت از طریق فایل ZIP به‌روزرسانی شد.";
            if (!$request->has('overwrite_composer')) {
                $message .= " (فایل composer.json حفظ شد)";
            }

            if ($request->has('run_migrations')) {
                $message .= $this->runMigrations($service);
            }

            return back()->with('success', $message);
        } else {
            return back()->with('error', 'خطا در باز کردن فایل ZIP. لطفاً مطمئن شوید فایل سالم است.');
        }
    }

    private function isWindows(): bool
    {
        return function_exists('windows_os') ? windows_os() : (PHP_OS_FAMILY === 'Windows');
    }

    private function shEscape(string $value): string
    {
        return escapeshellarg($value);
    }

    private function getCurrentUser(): string
    {
        if ($this->isWindows()) {
            return get_current_user();
        }

        $whoami = Process::run('whoami');
        if ($whoami->successful()) {
            return trim($whoami->output());
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $userInfo = posix_getpwuid(posix_geteuid());
            if ($userInfo && isset($userInfo['name'])) {
                return $userInfo['name'];
            }
        }

        return get_current_user();
    }

    private function findCommandPath(string $command): string
    {
        if ($this->isWindows()) return $command;

        $result = Process::run("which " . $this->shEscape($command));
        if ($result->successful()) {
            return trim($result->output());
        }

        return $command;
    }

    private function runSudoCommand(string $command)
    {
        if ($this->isWindows()) {
            return Process::run($command);
        }

        $currentUser = $this->getCurrentUser();

        if ($currentUser === 'root') {
            $command = preg_replace('/^sudo\s+/', '', $command);
            return Process::run($command);
        }

        $sudoTest = Process::run('sudo -n true 2>&1');
        if ($sudoTest->successful()) {
            return Process::run($command);
        }

        $sudoPassword = env('SUDO_PASSWORD');
        if ($sudoPassword) {
            $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $command);
            $escapedPassword = escapeshellarg($sudoPassword);
            $commandWithPassword = "printf %s {$escapedPassword} | sudo -S " . $commandWithoutSudo;
            return Process::run($commandWithPassword);
        }

        $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $command);
        $result = Process::run($commandWithoutSudo);

        if (!$result->successful()) {
            $commands = ['mkdir', 'mv', 'chmod', 'chown', 'ln', 'rm', 'cp', 'tee', 'cat', 'nginx', 'systemctl'];
            $paths = array_map(fn($c) => $this->findCommandPath($c), $commands);
            $all = implode(', ', $paths);

            throw new \Exception(
                "Command failed: {$command}\n\n" .
                "Current user: {$currentUser}\n" .
                "Please configure passwordless sudo for '{$currentUser}'.\n\n" .
                "sudo visudo\n\n" .
                "Add:\n" .
                "{$currentUser} ALL=(ALL) NOPASSWD: {$all}\n\n" .
                "Or set SUDO_PASSWORD in .env (less secure)\n\n" .
                "Error: " . $result->errorOutput()
            );
        }

        return $result;
    }

    private function normalizeSubdomainLabel(string $label): string
    {
        $label = strtolower(trim($label));
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
            throw new \InvalidArgumentException("Invalid subdomain label: {$label}");
        }
        return $label;
    }

    private function normalizeSubfolderSegment(string $segment): string
    {
        $segment = strtolower(trim($segment));
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $segment)) {
            throw new \InvalidArgumentException("Invalid subfolder name: {$segment}");
        }
        return $segment;
    }

    private function normalizeFqdn(string $fqdn): string
    {
        $fqdn = strtolower(trim($fqdn));
        if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $fqdn)) {
            throw new \InvalidArgumentException("Invalid domain: {$fqdn}");
        }
        return $fqdn;
    }

    private function getMainDomain(): string
    {
        $main = env('APP_MAIN_DOMAIN');
        if ($main) return $this->normalizeFqdn($main);

        $host = request()->getHost();
        try {
            return $this->normalizeFqdn($host);
        } catch (\Throwable $e) {
            return $host;
        }
    }

    private function getBasePath(): string
    {
        return $this->isWindows() ? base_path('../service') : $this->linuxBasePath;
    }

    private function servicePathForDomain(string $fullDomain): string
    {
        return rtrim($this->getBasePath(), '/') . '/' . $fullDomain;
    }

    private function nginxSafeName(string $value): string
    {
        $value = strtolower($value);
        return preg_replace('/[^a-z0-9._-]+/', '-', $value);
    }

    private function ensureDirectoryLinux(string $path, int $mode = 0755, string $owner = 'www-data:www-data'): void
    {
        if ($this->isWindows()) {
            if (!File::exists($path)) File::makeDirectory($path, $mode, true);
            return;
        }

        $p = $this->shEscape($path);
        $this->runSudoCommand("sudo mkdir -p {$p}");
        $this->runSudoCommand("sudo chmod " . decoct($mode) . " {$p}");
        $this->runSudoCommand("sudo chown -R " . $this->shEscape($owner) . " {$p}");
    }

    private function putFileEnsuringDir(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($path, $contents);
    }

    private function getFileOwnerUser(): string
    {
        if ($this->isWindows()) {
            return get_current_user();
        }

        $ubuntuCheck = Process::run('id ubuntu 2>&1');
        if ($ubuntuCheck->successful()) {
            return 'ubuntu';
        }

        return $this->getCurrentUser();
    }

    private function fixPermissions(string $path): void
    {
        if ($this->isWindows()) return;

        $p = $this->shEscape($path);
        $fileOwner = $this->getFileOwnerUser();

        $this->runSudoCommand("sudo chown -R www-data:www-data {$p}");
        $this->runSudoCommand("sudo chmod -R 755 {$p}");

        $storagePath = $path . '/storage';
        $cachePath = $path . '/bootstrap/cache';

        if (File::exists($storagePath)) {
            $storageEscaped = $this->shEscape($storagePath);
            $this->runSudoCommand("sudo chown -R {$fileOwner}:www-data {$storageEscaped}");
            $this->runSudoCommand("sudo chmod -R ug+rwX {$storageEscaped}");
            $this->runSudoCommand("sudo find {$storageEscaped} -type d -exec chmod g+s {} \\;");
        }

        if (File::exists($cachePath)) {
            $cacheEscaped = $this->shEscape($cachePath);
            $this->runSudoCommand("sudo chown -R {$fileOwner}:www-data {$cacheEscaped}");
            $this->runSudoCommand("sudo chmod -R ug+rwX {$cacheEscaped}");
            $this->runSudoCommand("sudo find {$cacheEscaped} -type d -exec chmod g+s {} \\;");
        }
    }

    private function resolveSslCertificatePaths(string $domain): array
    {
        $domain = $this->nginxSafeName($domain);

        $preferred = "/etc/letsencrypt/live/{$domain}";
        if (File::exists($preferred . '/fullchain.pem') && File::exists($preferred . '/privkey.pem')) {
            return [
                'cert' => $preferred . '/fullchain.pem',
                'key'  => $preferred . '/privkey.pem',
            ];
        }

        $main = env('MAIN_SSL_DOMAIN') ?: $this->getMainDomain();
        $main = $this->nginxSafeName($main);
        $fallback = "/etc/letsencrypt/live/{$main}";

        if (!File::exists($fallback . '/fullchain.pem')) {
            $alt = "/etc/letsencrypt/live/{$main}-0001";
            if (File::exists($alt . '/fullchain.pem') && File::exists($alt . '/privkey.pem')) {
                $fallback = $alt;
            }
        }

        return [
            'cert' => $fallback . '/fullchain.pem',
            'key'  => $fallback . '/privkey.pem',
        ];
    }

    private function detectPHPVersion(): string
    {
        $commonVersions = ['8.3', '8.2', '8.1', '8.0', '7.4'];
        foreach ($commonVersions as $version) {
            if (file_exists("/run/php/php{$version}-fpm.sock")) {
                return $version;
            }
        }
        return '8.2';
    }

    private function nginxServiceConfNameForSubdomain(string $fqdn): string
    {
        return "service-" . $this->nginxSafeName($fqdn) . ".conf";
    }

    private function writeNginxSiteConfig(string $availablePath, string $enabledPath, string $content): void
    {
        if ($this->isWindows()) return;

        $tmp = '/tmp/nginx_site_' . uniqid() . '.conf';
        File::put($tmp, $content);

        $tmpEsc = $this->shEscape($tmp);
        $availEsc = $this->shEscape($availablePath);
        $enEsc = $this->shEscape($enabledPath);

        $this->runSudoCommand("sudo mv {$tmpEsc} {$availEsc}");
        $this->runSudoCommand("sudo chmod 644 {$availEsc}");
        $this->runSudoCommand("sudo chown root:root {$availEsc}");
        $this->runSudoCommand("sudo ln -sf {$availEsc} {$enEsc}");

        $test = $this->runSudoCommand('sudo nginx -t');
        if (!$test->successful()) {
            throw new \Exception("Nginx configuration test failed: " . $test->errorOutput());
        }

        $reload = $this->runSudoCommand('sudo systemctl reload nginx');
        if (!$reload->successful()) {
            Log::warning("Nginx reload failed", ['error' => $reload->errorOutput()]);
        }
    }

    private function createNginxConfigSubdomain(string $fullDomain, string $servicePath): void
    {
        if ($this->isWindows()) return;

        $fullDomain = $this->normalizeFqdn($fullDomain);
        $real = realpath($servicePath) ?: $servicePath;
        $rootPath = rtrim($real, '/') . '/public';
        $phpVersion = $this->detectPHPVersion();
        $phpSock = "/run/php/php{$phpVersion}-fpm.sock";
        $ssl = $this->resolveSslCertificatePaths($fullDomain);
        $debugHeader = $this->nginxSafeName($fullDomain);
        $forceHttps = (bool) env('SERVICE_FORCE_HTTPS', false);

        $service = Service::where('domain', $fullDomain)->orWhere('path', $servicePath)->first();
        $allDomains = [$fullDomain];
        if (!str_starts_with($fullDomain, 'www.')) {
            $allDomains[] = "www.{$fullDomain}";
        }
        if ($service) {
            foreach ($service->getClientDomains() as $cd) {
                $cd = trim($cd);
                if (!empty($cd)) {
                    $allDomains[] = $cd;
                    if (!str_starts_with($cd, 'www.')) {
                        $allDomains[] = "www.{$cd}";
                    }
                }
            }
        }
        $serverNameStr = implode(' ', array_unique($allDomains));

        $httpServer = $forceHttps
            ? "server {\n    listen 80;\n    listen [::]:80;\n    server_name {$serverNameStr};\n    return 301 https://\$host\$request_uri;\n}\n"
            : "server {\n    listen 80;\n    listen [::]:80;\n    server_name {$serverNameStr};\n    add_header X-Debug-Server \"{$debugHeader}-http\" always;\n    root {$rootPath};\n    index index.php index.html index.htm;\n    charset utf-8;\n    access_log /var/log/nginx/{$debugHeader}-access.log;\n    error_log  /var/log/nginx/{$debugHeader}-error.log;\n    location / {\n        try_files \$uri \$uri/ /index.php?\$query_string;\n    }\n    location = /favicon.ico { access_log off; log_not_found off; }\n    location = /robots.txt  { access_log off; log_not_found off; }\n    location ~ \\.php$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:{$phpSock};\n    }\n    location ~ /\\. {\n        deny all;\n    }\n}\n";

        $httpsServer = "server {\n    listen 443 ssl;\n    listen [::]:443 ssl;\n    server_name {$serverNameStr};\n    add_header X-Debug-Server \"{$debugHeader}-https\" always;\n    root {$rootPath};\n    index index.php index.html index.htm;\n    charset utf-8;\n    access_log /var/log/nginx/{$debugHeader}-ssl-access.log;\n    error_log  /var/log/nginx/{$debugHeader}-ssl-error.log;\n    ssl_certificate     {$ssl['cert']};\n    ssl_certificate_key {$ssl['key']};\n    include /etc/letsencrypt/options-ssl-nginx.conf;\n    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;\n    location / {\n        try_files \$uri \$uri/ /index.php?\$query_string;\n    }\n    location = /favicon.ico { access_log off; log_not_found off; }\n    location = /robots.txt  { access_log off; log_not_found off; }\n    location ~ \\.php$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:{$phpSock};\n    }\n    location ~ /\\. {\n        deny all;\n    }\n}\n";

        $content = $httpServer . "\n" . $httpsServer;
        $file = $this->nginxServiceConfNameForSubdomain($fullDomain);
        $available = "{$this->nginxSitesAvailable}/{$file}";
        $enabled   = "{$this->nginxSitesEnabled}/{$file}";

        $this->removeNginxConfig($fullDomain);
        $this->writeNginxSiteConfig($available, $enabled, $content);
        Log::info("Created nginx config for subdomain", ['domain' => $fullDomain, 'available' => $available, 'enabled' => $enabled, 'root' => $rootPath]);
    }

    private function createNginxConfigSubfolder(string $folderSegment, string $servicePublicPath): void
    {
        if ($this->isWindows()) return;

        $folder = $this->normalizeSubfolderSegment($folderSegment);
        $realPublic = rtrim(realpath($servicePublicPath) ?: $servicePublicPath, '/') . '/';
        $phpVersion = $this->detectPHPVersion();
        $phpSock = "/run/php/php{$phpVersion}-fpm.sock";
        $safe = $this->nginxSafeName($folder);

        $snippet = "## service subfolder: {$folder}\nlocation ^~ /{$folder}/ {\n    alias {$realPublic};\n    index index.php index.html index.htm;\n    try_files \$uri \$uri/ /index.php?\$query_string;\n}\n\nlocation ~ ^/{$folder}/(.+\\.php)\$ {\n    alias {$realPublic}\$1;\n    include snippets/fastcgi-php.conf;\n    fastcgi_param SCRIPT_FILENAME {$realPublic}\$1;\n    fastcgi_pass unix:{$phpSock};\n}\n";

        if (!File::exists($this->nginxConfigPath)) {
            $this->runSudoCommand("sudo mkdir -p " . $this->shEscape($this->nginxConfigPath));
            $this->runSudoCommand("sudo chmod 755 " . $this->shEscape($this->nginxConfigPath));
            $this->runSudoCommand("sudo chown root:root " . $this->shEscape($this->nginxConfigPath));
        }

        $confFile = "{$this->nginxConfigPath}/subfolder-{$safe}.conf";
        $tmp = '/tmp/nginx_subfolder_' . uniqid() . '.conf';
        File::put($tmp, $snippet);

        $this->runSudoCommand("sudo mv " . $this->shEscape($tmp) . " " . $this->shEscape($confFile));
        $this->runSudoCommand("sudo chmod 644 " . $this->shEscape($confFile));
        $this->runSudoCommand("sudo chown root:root " . $this->shEscape($confFile));

        $test = $this->runSudoCommand('sudo nginx -t');
        if (!$test->successful()) {
            throw new \Exception("Nginx configuration test failed: " . $test->errorOutput());
        }
        $this->runSudoCommand('sudo systemctl reload nginx');
        Log::info("Created nginx subfolder snippet", ['folder' => $folder, 'file' => $confFile]);
    }

    private function removeNginxConfig(string $domainOrFolder): void
    {
        if ($this->isWindows()) return;

        $safe = $this->nginxSafeName($domainOrFolder);
        $targets = [
            "{$this->nginxSitesAvailable}/service-{$safe}.conf",
            "{$this->nginxSitesEnabled}/service-{$safe}.conf",
            "{$this->nginxSitesAvailable}/subdomain-{$safe}.conf",
            "{$this->nginxSitesEnabled}/subdomain-{$safe}.conf",
            "{$this->nginxSitesAvailable}/00-subdomain-{$safe}.conf",
            "{$this->nginxSitesEnabled}/00-subdomain-{$safe}.conf",
            "{$this->nginxSitesAvailable}/{$safe}-ssl.conf",
            "{$this->nginxSitesEnabled}/{$safe}-ssl.conf",
            "{$this->nginxSitesAvailable}/{$safe}.ssl.conf",
            "{$this->nginxSitesEnabled}/{$safe}.ssl.conf",
            "{$this->nginxConfigPath}/subfolder-{$safe}.conf",
        ];

        $removedAny = false;
        foreach ($targets as $t) {
            if (File::exists($t) || is_link($t)) {
                $this->runSudoCommand("sudo rm -f " . $this->shEscape($t));
                $removedAny = true;
            }
        }

        if ($removedAny) {
            $test = $this->runSudoCommand('sudo nginx -t');
            if ($test->successful()) {
                $this->runSudoCommand('sudo systemctl reload nginx');
            } else {
                Log::error("Nginx config test failed after removal", ['error' => $test->errorOutput()]);
            }
        }
    }

    private function removeQuotesFromEnvValue(string $envPath, string $key): void
    {
        if (!File::exists($envPath)) return;
        try {
            $envContent = File::get($envPath);
            $lines = explode("\n", $envContent);
            $updated = false;
            foreach ($lines as $i => $line) {
                if (preg_match("/^{$key}\s*=\s*([\"']?)(.*?)\\1\s*$/m", $line, $matches)) {
                    $value = $matches[2] ?? '';
                    $cleanValue = trim($value);
                    if ((str_starts_with($cleanValue, '"') && str_ends_with($cleanValue, '"')) || (str_starts_with($cleanValue, "'") && str_ends_with($cleanValue, "'"))) {
                        $cleanValue = substr($cleanValue, 1, -1);
                    }
                    $lines[$i] = "{$key}={$cleanValue}";
                    $updated = true;
                } elseif (preg_match("/^{$key}\s*=\s*(.+?)\s*$/m", $line, $matches)) {
                    $value = trim($matches[1] ?? '');
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                        $lines[$i] = "{$key}={$value}";
                        $updated = true;
                    }
                }
            }
            if ($updated) {
                File::put($envPath, implode("\n", $lines));
                Log::info("Removed quotes from {$key} in .env file", ['path' => $envPath]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to remove quotes from {$key} in .env", ['path' => $envPath, 'error' => $e->getMessage()]);
        }
    }

    private function updateServiceEnv(string $envPath, Service $service): void
    {
        if (!File::exists($envPath)) return;
        $envContent = File::get($envPath);
        $protocol = env('SERVICE_FORCE_HTTPS', false) ? 'https' : 'http';
        if ($service->type === 'subdomain') {
            $appUrl = "{$protocol}://{$service->domain}";
            $hostForCookies = $service->domain;
        } else {
            $mainDomain = $this->getMainDomain();
            $appUrl = "{$protocol}://{$mainDomain}/{$service->domain}";
            $hostForCookies = $mainDomain;
        }
        $updates = ['APP_URL' => $appUrl];
        if (!$this->envHasActiveKey($envContent, 'SESSION_DOMAIN')) {
            $cookieDomain = $this->guessCookieDomain($hostForCookies);
            if ($cookieDomain) {
                $updates['SESSION_DOMAIN'] = $cookieDomain;
            }
        }
        if (!$this->envHasActiveKey($envContent, 'SESSION_SECURE_COOKIE')) {
            $updates['SESSION_SECURE_COOKIE'] = $protocol === 'https' ? 'true' : 'false';
        }
        foreach ($updates as $key => $value) {
            $envContent = $this->upsertEnvPreservingComments($envContent, $key, (string) $value);
        }
        File::put($envPath, $envContent);
    }

    private function envHasActiveKey(string $envContent, string $key): bool
    {
        return (bool) preg_match("/^{$key}\s*=/m", $envContent);
    }

    private function upsertEnvPreservingComments(string $envContent, string $key, string $value): string
    {
        $value = trim($value);
        if (preg_match("/^{$key}\s*=.*$/m", $envContent)) {
            return preg_replace("/^{$key}\s*=.*$/m", "{$key}={$value}", $envContent, 1);
        }
        if (preg_match("/^#\s*{$key}\s*=.*$/m", $envContent)) {
            return preg_replace("/^#\s*{$key}\s*=.*$/m", "$0\n{$key}={$value}", $envContent, 1);
        }
        if (preg_match('/^APP_KEY\s*=.*$/m', $envContent)) {
            return preg_replace('/^APP_KEY\s*=.*$/m', "$0\n{$key}={$value}", $envContent, 1);
        }
        if (preg_match('/^APP_NAME\s*=.*$/m', $envContent)) {
            return preg_replace('/^APP_NAME\s*=.*$/m', "$0\n{$key}={$value}", $envContent, 1);
        }
        return rtrim($envContent) . "\n{$key}={$value}\n";
    }

    private function guessCookieDomain(string $host): ?string
    {
        $host = trim($host);
        if ($host === '') return null;
        $host = preg_replace('#^https?://#', '', $host);
        $host = preg_replace('#/.*$#', '', $host);
        $host = preg_replace('/:\d+$/', '', $host);
        $parts = array_values(array_filter(explode('.', $host)));
        if (count($parts) < 2) return null;
        $root = implode('.', array_slice($parts, -2));
        return '.' . $root;
    }

    private function autoSetupLaravelProject(Service $service): array
    {
        $servicePath = $service->path;
        $steps = [];
        $errors = [];
        if (!File::exists($servicePath . '/artisan')) {
            return ['steps' => [], 'errors' => [], 'is_laravel' => false];
        }
        $steps[] = '✓ Laravel project detected';
        $envPath = $servicePath . '/.env';
        $envExamplePath = $servicePath . '/.env.example';
        if (!File::exists($envPath) && File::exists($envExamplePath)) {
            try {
                File::copy($envExamplePath, $envPath);
                $steps[] = '✓ Created .env file from .env.example';
            } catch (\Exception $e) {
                $errors[] = 'Failed to create .env: ' . $e->getMessage();
            }
        } elseif (File::exists($envPath)) {
            $steps[] = '✓ .env file already exists';
        }
        if (File::exists($envPath)) {
            try {
                $this->updateServiceEnv($envPath, $service);
                $steps[] = '✓ Updated .env with service-specific settings';
            } catch (\Exception $e) {
                $errors[] = 'Failed to update .env: ' . $e->getMessage();
            }
        }
        if (!File::exists($servicePath . '/vendor')) {
            try {
                $env = ['COMPOSER_HOME' => '/tmp/composer_home_' . uniqid(), 'HOME' => '/tmp', 'PATH' => getenv('PATH') ?: '/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin'];
                File::makeDirectory($env['COMPOSER_HOME'], 0755, true);
                $result = Process::path($servicePath)->env($env)->run('composer install --no-dev --optimize-autoloader');
                File::deleteDirectory($env['COMPOSER_HOME']);
                if ($result->successful()) {
                    $steps[] = '✓ Composer dependencies installed';
                } else {
                    $errors[] = 'نصب Composer با خطا مواجه شد: ' . $result->errorOutput();
                }
            } catch (\Exception $e) {
                $errors[] = 'Composer install exception: ' . $e->getMessage();
            }
        } else {
            $steps[] = '✓ Composer dependencies already installed';
        }
        if (File::exists($servicePath . '/vendor')) {
            try {
                Process::path($servicePath)->run('php artisan config:clear');
            } catch (\Exception $e) {
                Log::warning('Failed to clear config cache before key generation', ['error' => $e->getMessage()]);
            }
        }
        if (File::exists($servicePath . '/vendor')) {
            $this->ensureAppKey($servicePath);
            $steps[] = '✓ APP_KEY checked/generated';
        }
        try {
            $this->fixPermissions($servicePath);
            $steps[] = '✓ Permissions fixed';
        } catch (\Exception $e) {
            $errors[] = 'Failed to fix permissions: ' . $e->getMessage();
        }
        if (File::exists($servicePath . '/vendor')) {
            try {
                Process::path($servicePath)->run('php artisan config:clear');
                Process::path($servicePath)->run('php artisan view:clear');
                Process::path($servicePath)->run('php artisan cache:clear');
                if (File::exists($servicePath . '/bootstrap/cache/config.php')) {
                    File::delete($service->path . '/bootstrap/cache/config.php');
                }
                $steps[] = '✓ All caches cleared';
            } catch (\Exception $e) {
                Log::warning('Failed to clear caches', ['error' => $e->getMessage()]);
            }
        }
        if ($service->type === 'subfolder' && File::exists($servicePath . '/public')) {
            $this->createSymlink($service->domain, $servicePath . '/public');
            $steps[] = '✓ Symlink created/updated';
        }
        return ['steps' => $steps, 'errors' => $errors, 'is_laravel' => true];
    }

    private function ensureAppKey(string $servicePath): void
    {
        if (!File::exists($servicePath . '/artisan') || !File::exists($servicePath . '/vendor')) return;
        $envPath = $servicePath . '/.env';
        if (!File::exists($envPath)) return;
        $envContent = File::get($envPath);
        if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches)) {
            $appKey = trim($matches[1]);
            if (!empty($appKey) && str_starts_with($appKey, 'base64:') && strlen($appKey) > 7) {
                return;
            }
        }
        try {
            Process::path($servicePath)->run('php artisan config:clear');
            if (File::exists($servicePath . '/bootstrap/cache/config.php')) {
                File::delete($servicePath . '/bootstrap/cache/config.php');
            }
            $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=', File::get($envPath));
            File::put($envPath, $envContent);
            $result = Process::path($servicePath)->run('php artisan key:generate --force');
            if ($result->successful()) {
                usleep(200000);
                $newEnvContent = File::get($envPath);
                if (preg_match('/^APP_KEY=(.+)$/m', $newEnvContent, $newMatches)) {
                    $newAppKey = trim($newMatches[1]);
                    if (!empty($newAppKey) && str_starts_with($newAppKey, 'base64:') && strlen($newAppKey) > 7) {
                        Log::info('Generated APP_KEY for Laravel project', ['path' => $servicePath]);
                    } else {
                        Log::warning('APP_KEY generated but format seems invalid, retrying...', ['path' => $servicePath, 'key' => substr($newAppKey, 0, 20) . '...']);
                        $newEnvContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=', $newEnvContent);
                        File::put($envPath, $newEnvContent);
                        $retryResult = Process::path($servicePath)->run('php artisan key:generate --force');
                        if ($retryResult->successful()) {
                            usleep(200000);
                            $finalEnvContent = File::get($envPath);
                            if (preg_match('/^APP_KEY=(.+)$/m', $finalEnvContent, $finalMatches)) {
                                $finalKey = trim($finalMatches[1]);
                                if (!empty($finalKey) && str_starts_with($finalKey, 'base64:') && strlen($finalKey) > 7) {
                                    Log::info('APP_KEY generated successfully on retry', ['path' => $servicePath]);
                                }
                            }
                        }
                    }
                } else {
                    Log::warning('APP_KEY command succeeded but key not found in .env, generating manually', ['path' => $servicePath]);
                    $key = 'base64:' . base64_encode(random_bytes(32));
                    $newEnvContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, File::get($envPath));
                    File::put($envPath, $newEnvContent);
                    Log::info('Manually generated APP_KEY', ['path' => $servicePath]);
                }
                Process::path($servicePath)->run('php artisan config:clear');
                if (File::exists($servicePath . '/bootstrap/cache/config.php')) {
                    File::delete($servicePath . '/bootstrap/cache/config.php');
                }
            } else {
                Log::warning('Failed to generate APP_KEY via artisan, trying manual generation', ['path' => $servicePath, 'error' => $result->errorOutput(), 'output' => $result->output()]);
                $key = 'base64:' . base64_encode(random_bytes(32));
                $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, File::get($envPath));
                File::put($envPath, $envContent);
                Log::info('Manually generated APP_KEY as fallback', ['path' => $servicePath]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while generating APP_KEY', ['path' => $servicePath, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function runMigrations(Service $service): string
    {
        if (!File::exists($service->path . '/artisan')) return '';
        try {
            $this->fixPermissions($service->path);
            $migrateResult = Process::path($service->path)->run('php artisan migrate --force');
            if ($migrateResult->successful()) {
                return "\n\nMigrations executed:\n" . $migrateResult->output();
            } else {
                return "\n\nMigration failed:\n" . $migrateResult->errorOutput();
            }
        } catch (\Exception $e) {
            return "\n\nMigration exception: " . $e->getMessage();
        }
    }

    public function index(Request $request)
    {
        $query = Service::with('domainMappings');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('domain', 'like', "%{$searchTerm}%")
                  ->orWhereHas('domainMappings', function($subQ) use ($searchTerm) {
                      $subQ->where('source_domain', 'like', "%{$searchTerm}%");
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $services = $query->latest()->paginate(10)->withQueryString();
        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'domain' => 'required|string|max:255', 'type' => 'required|in:subdomain,subfolder']);
        $type = $request->input('type');
        $inputDomain = $request->input('domain');
        $mainDomain = $this->getMainDomain();
        if ($type === 'subdomain') {
            $label = $this->normalizeSubdomainLabel($inputDomain);
            $fullDomain = $this->normalizeFqdn("{$label}.{$mainDomain}");
        } else {
            $fullDomain = $this->normalizeSubfolderSegment($inputDomain);
        }
        if (Service::where('domain', $fullDomain)->exists()) {
            return back()->withInput()->withErrors(['domain' => 'This domain/subdomain is already taken.']);
        }
        if ($type === 'subdomain') {
            $dnsService = new ArvanDnsService();
            $validation = $dnsService->validateSubdomainName($label);
            if (!$validation['valid']) {
                return back()->withInput()->withErrors(['domain' => $validation['error']]);
            }
            $dnsResult = $dnsService->createARecord($mainDomain, $label);
            if (!$dnsResult['success']) {
                return back()->withInput()->withErrors(['domain' => $dnsResult['error']]);
            }
            Log::info('DNS record created for subdomain', ['subdomain' => $label, 'domain' => $mainDomain, 'full_domain' => $fullDomain]);
        }
        $servicePath = $this->servicePathForDomain($fullDomain);
        $publicPath = $servicePath . '/public';
        try {
            if (!$this->isWindows()) {
                $this->ensureDirectoryLinux($servicePath, 0755, 'www-data:www-data');
                $this->ensureDirectoryLinux($publicPath, 0755, 'www-data:www-data');
            } else {
                if (!File::exists($publicPath)) File::makeDirectory($publicPath, 0755, true);
            }
            $this->writePlaceholderFiles($fullDomain, $type, $publicPath);
            $this->fixPermissions($servicePath);
            $this->removeNginxConfig($fullDomain);
            if ($type === 'subfolder') {
                $this->createSymlink($fullDomain, $publicPath);
                $this->createNginxConfigSubfolder($fullDomain, $publicPath);
            } else {
                $this->createNginxConfigSubdomain($fullDomain, $servicePath);
            }
            $service = Service::create(['name' => $request->name, 'domain' => $fullDomain, 'type' => $type, 'path' => $servicePath]);
            if ($type === 'subdomain') {
                try {
                    $dnsService = new ArvanDnsService();
                    $healthCheck = $dnsService->healthCheck($fullDomain, 5, 10);
                    if ($healthCheck['success']) {
                        Log::info('Subdomain health check passed', ['domain' => $fullDomain, 'status' => $healthCheck['status'] ?? null]);
                    } else {
                        Log::info('Subdomain health check pending (DNS propagation)', ['domain' => $fullDomain, 'message' => $healthCheck['message'] ?? 'DNS propagation in progress']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Health check failed (non-critical)', ['domain' => $fullDomain, 'error' => $e->getMessage()]);
                }
            }
            $successMessage = 'Service created successfully.';
            if ($type === 'subdomain') {
                $successMessage .= ' DNS record has been created. SSL will work automatically with wildcard certificate.';
            }
            return redirect()->route('services.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Service creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($type === 'subdomain' && isset($dnsResult) && $dnsResult['success']) {
                Log::warning('Service creation failed after DNS was created', ['domain' => $fullDomain, 'dns_created' => true]);
            }
            return back()->withInput()->withErrors(['error' => 'Failed to create service: ' . $e->getMessage()]);
        }
    }

    private function writePlaceholderFiles(string $fullDomain, string $type, string $publicPath): void
    {
        File::put($publicPath . '/index.html', '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Welcome to ' . htmlspecialchars($fullDomain) . '</title><style>body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; } .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; } h1 { color: #333; } p { color: #666; }</style></head><body><div class="container"><h1>خوش آمدید به ' . htmlspecialchars($fullDomain) . '</h1><p>نوع سرویس: ' . htmlspecialchars($type === 'subdomain' ? 'زیردامنه' : 'زیرپوشه') . '</p><p>سرویس شما ایجاد شد. لطفاً پروژه Laravel خود را آپلود/کلون کنید.</p></div></body></html>');
        File::put($publicPath . '/index.php', '<?php echo "<!DOCTYPE html><html lang=\\"fa\\" dir=\\"rtl\\"><head><meta charset=\\"UTF-8\\"><meta name=\\"viewport\\" content=\\"width=device-width, initial-scale=1.0\\"><title>PHP is Working</title><style>body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; } .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; } h1 { color: #28a745; } p { color: #666; } code { display: inline-block; padding: 2px 6px; background: #eee; border-radius: 6px; }</style></head><body><div class=\\"container\\"><h1>✓ PHP در حال کار است!</h1><p>Nginx در حال سرو کردن PHP از مسیر <code>public</code> است.</p><p>PHP Version: " . phpversion() . "</p><p>اگر پروژه Laravel آپلود شود باید فایل <code>artisan</code> در ریشه سرویس موجود باشد.</p></div></body></html>";');
        File::put($publicPath . '/.htaccess', '<IfModule mod_rewrite.c><IfModule mod_negotiation.c>Options -MultiViews -Indexes</IfModule>RewriteEngine On RewriteCond %{HTTP:Authorization} . RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}] RewriteCond %{REQUEST_FILENAME} !-d RewriteCond %{REQUEST_URI} (.+)/$ RewriteRule ^ %1 [L,R=301] RewriteCond %{REQUEST_FILENAME} !-d RewriteCond %{REQUEST_FILENAME} !-f RewriteRule ^ index.php [L]</IfModule>');
    }

    private function createSymlink(string $domainSegment, string $targetPath): void
    {
        $linkPath = public_path($domainSegment);
        if (file_exists($linkPath) || is_link($linkPath)) {
            @unlink($linkPath);
        }
        @symlink($targetPath, $linkPath);
    }

    public function show(Service $service)
    {
        $service->load('domainMappings');
        $hasGit = File::exists($service->path . '/.git');
        $structure = [];
        if (File::exists($service->path)) {
            $structure['root_files'] = array_map(fn($f) => $f->getFilename(), File::files($service->path));
            $structure['directories'] = array_map(fn($d) => basename($d), File::directories($service->path));
            $structure['resources'] = [];
            if (File::exists($service->path . '/resources')) {
                $structure['resources'] = $this->getDirContents($service->path . '/resources', 'resources');
            }
        }
        
        $sslStatus = $service->getSslStatus();

        return view('services.show', compact('service', 'hasGit', 'structure', 'sslStatus'));
    }

    private function getDirContents($dir, $prefix = '')
    {
        $results = [];
        try {
            foreach (File::allFiles($dir) as $file) {
                $results[] = $prefix . '/' . $file->getRelativePathname();
            }
        } catch (\Exception $e) {
            $results[] = 'Error: ' . $e->getMessage();
        }
        return $results;
    }

    public function getFile(Request $request, Service $service)
    {
        $filename = $request->query('file');
        if (!$filename) return response()->json(['error' => 'Filename required'], 400);
        $realBase = realpath($service->path);
        $targetPath = realpath($service->path . '/' . $filename);
        if ($targetPath === false || $realBase === false || strpos($targetPath, $realBase) !== 0) {
            return response()->json(['error' => 'Invalid file path'], 403);
        }
        if (!File::exists($targetPath)) return response()->json(['error' => 'File not found'], 404);
        return response()->json(['content' => File::get($targetPath)]);
    }

    public function saveFile(Request $request, Service $service)
    {
        $filename = $request->input('file');
        $content = $request->input('content');
        if (!$filename) return response()->json(['error' => 'Filename required'], 400);
        $realBase = realpath($service->path);
        $targetPath = $service->path . '/' . ltrim($filename, '/');
        $targetDir = dirname($targetPath);
        $realTargetDir = realpath($targetDir);
        if ($realBase === false || $realTargetDir === false || strpos($realTargetDir, $realBase) !== 0) {
            return response()->json(['error' => 'Invalid file path'], 403);
        }
        try {
            File::put($targetPath, $content ?? '');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save file: ' . $e->getMessage()], 500);
        }
    }

    public function createFile(Request $request, Service $service)
    {
        $filename = $request->input('filename');
        if (!$filename) return back()->withErrors(['error' => 'Filename is required']);
        if (strpos($filename, '..') !== false || strpos($filename, "\0") !== false || str_starts_with($filename, '/')) {
            return back()->withErrors(['error' => 'Invalid filename']);
        }
        $targetPath = $service->path . '/' . $filename;
        $directory = dirname($targetPath);
        if (!File::exists($directory)) File::makeDirectory($directory, 0755, true);
        if (File::exists($targetPath)) return back()->withErrors(['error' => 'File already exists']);
        try {
            File::put($targetPath, '');
            return back()->with('success', "فایل '$filename' با موفقیت ایجاد شد.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create file: ' . $e->getMessage()]);
        }
    }

    public function uploadFile(Request $request, Service $service)
    {
        // Increase time and memory limits for large file processing
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        if ($file->getClientOriginalExtension() !== 'zip') {
            return back()->with('error', 'لطفاً یک فایل با پسوند .zip آپلود کنید.');
        }

        $runMigrations = $request->has('run_migrations');
        try {
            $destinationPath = $service->path;
            $zipPath = $destinationPath . '/upload_temp.zip';
            $file->move($destinationPath, 'upload_temp.zip');
            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                // Zip Slip Path Traversal Protection
                $realDest = realpath($destinationPath) ?: $destinationPath;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                        $zip->close();
                        File::delete($zipPath);
                        \App\Models\SecurityEvent::log(
                            'file_scan',
                            'critical',
                            'حمله Zip Slip Path Traversal مسدود شد: ' . $file->getClientOriginalName(),
                            "فایل درون آرشیو قصد نفوذ به خارج از پوشه سرویس را داشت: {$entryName}",
                            ['service_id' => $service->id],
                            $request->ip()
                        );
                        return back()->withErrors(['error' => 'خطای امنیتی: فایل زیپ حاوی مسیرهای نامعتبر است (Zip Slip).']);
                    }
                }

                $zip->extractTo($destinationPath);
                $zip->close();
                File::delete($zipPath);
                $setupResult = $this->autoSetupLaravelProject($service);
                $message = "File uploaded and extracted successfully.\n\n";
                if ($setupResult['is_laravel']) {
                    $message .= "Laravel Auto-Setup:\n";
                    $message .= implode("\n", $setupResult['steps']);
                    if ($runMigrations) {
                        $message .= $this->runMigrations($service);
                    }
                    if (!empty($setupResult['errors'])) {
                        $message .= "\n\nErrors:\n" . implode("\n", $setupResult['errors']);
                    }
                } else {
                    $message .= "Files extracted. If this is a Laravel project, run 'Composer Install' from Commands tab.";
                }
                return back()->with('success', $message);
            }
            return back()->withErrors(['error' => 'Failed to extract zip file.']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function uploadSingleFile(Request $request, Service $service)
    {
        // Increase time and memory limits
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate(['upload_file' => 'required|file', 'upload_path' => 'nullable|string']);
        try {
            $file = $request->file('upload_file');

            // Security check
            $scanner = app(\App\Services\FileScanner::class);
            $scanResult = $scanner->scanUploadedFile($file, true);
            if (!$scanResult['safe']) {
                \App\Models\SecurityEvent::log(
                    'file_scan',
                    'critical',
                    'آپلود فایل مخرب در سرویس مسدود شد: ' . $file->getClientOriginalName(),
                    $scanResult['reason'],
                    ['service_id' => $service->id, 'service_name' => $service->name],
                    $request->ip()
                );
                return back()->withErrors(['error' => 'خطای امنیتی: ' . $scanResult['reason']]);
            }

            $relativePath = $request->input('upload_path', '');
            if (strpos($relativePath, '..') !== false || str_starts_with($relativePath, '/')) {
                return back()->withErrors(['error' => 'Invalid upload path.']);
            }
            $destinationPath = $service->path . '/' . $relativePath;
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            $filename = $file->getClientOriginalName();
            $file->move($destinationPath, $filename);
            $this->fixPermissions($destinationPath . '/' . $filename);
            return back()->with('success', "فایل '{$filename}' با موفقیت در مسیر '{$relativePath}' آپلود شد.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'File upload failed: ' . $e->getMessage()]);
        }
    }

    public function gitAction(Request $request, Service $service)
    {
        // افزایش محدودیت زمان برای ریپازیتوری‌های بزرگ
        set_time_limit(600);

        $action = $request->input('action');
        $repoUrl = $request->input('repo_url');
        $runMigrations = $request->has('run_migrations');
        $backupFiles = [];
        $stashed = false;
        $command = '';

        // ۱. حل قطعی مشکل Permission Denied در پوشه گیت (مخصوص خطای FETCH_HEAD)
        if (!$this->isWindows() && File::exists($service->path . '/.git')) {
            try {
                $servicePathEscaped = $this->shEscape($service->path);
                $this->runSudoCommand("sudo chown -R www-data:www-data {$servicePathEscaped}/.git");
                $this->runSudoCommand("sudo chmod -R 775 {$servicePathEscaped}/.git");
                Log::info('Fixed .git permissions before action', ['path' => $service->path]);
            } catch (\Exception $e) {
                Log::warning('Failed to fix .git permissions', ['error' => $e->getMessage()]);
            }
        }

        // دستور پایه گیت با دور زدن خطای Dubious Ownership
        $gitBase = 'git -c safe.directory="*"';

        if ($action === 'clone') {
            if (!$repoUrl) return back()->withErrors(['error' => 'Repository URL is required for clone.']);

            $files = File::files($service->path);
            $dirs = File::directories($service->path);
            if (count($files) > 0 || count($dirs) > 0) {
                try {
                    File::cleanDirectory($service->path);
                    Log::info('Cleaned directory before git clone', ['path' => $service->path]);
                } catch (\Exception $e) {
                    return back()->withErrors(['error' => 'Failed to clean directory: ' . $e->getMessage()]);
                }
            }
            // قفل کردن کلون روی برنچ main
            $command = "{$gitBase} clone -b main " . escapeshellarg($repoUrl) . " .";

        } elseif ($action === 'pull') {

            // بکاپ‌گیری از فایل‌های حساس
            $importantFiles = ['.env', '.env.backup', 'composer.json'];
            foreach ($importantFiles as $file) {
                $filePath = $service->path . '/' . $file;
                if (File::exists($filePath)) {
                    $backupPath = $service->path . '/' . $file . '.backup_' . time();
                    try {
                        File::copy($filePath, $backupPath);
                        $backupFiles[$file] = $backupPath;
                    } catch (\Exception $e) {
                        Log::warning('Failed to backup file before git pull', ['file' => $file]);
                    }
                }
            }

            // فچ کردن تغییرات از سرور
            Process::path($service->path)->timeout(120)->run("{$gitBase} fetch origin");

            // بررسی تغییرات لوکال
            $statusResult = Process::path($service->path)->run("{$gitBase} status --porcelain");
            $hasLocalChanges = $statusResult->successful() && !empty(trim($statusResult->output()));

            if ($hasLocalChanges) {
                // ذخیره تغییرات لوکال به صورت موقت
                $stashCommand = "{$gitBase} stash";
                $stashResult = Process::path($service->path)->run($stashCommand);
                if ($stashResult->successful()) {
                    $stashed = true;
                    Log::info('Stashed local changes before git pull');
                } else {
                    Log::warning('Failed to stash, attempting hard reset to allow pull');
                    Process::path($service->path)->run("{$gitBase} reset --hard HEAD");
                }
            }

            // سوییچ اجباری و قطعی به برنچ main قبل از اجرای عملیات Pull
            Process::path($service->path)->run("{$gitBase} checkout main");
            $branch = 'main';

            // قفل کردن دستور پول روی برنچ main
            $command = "{$gitBase} pull origin main --no-rebase";

        } else {
            return back()->withErrors(['error' => 'Invalid git action.']);
        }

        // اجرای دستور کلون یا پول
        $result = Process::path($service->path)->timeout(600)->run($command);

        // در صورت شکست خوردن عملیات گیت
        if (!$result->successful()) {
            if ($stashed) {
                Process::path($service->path)->run("{$gitBase} stash pop");
            }
            foreach ($backupFiles as $file => $backupPath) {
                if (File::exists($backupPath)) {
                    @File::copy($backupPath, $service->path . '/' . $file);
                    @File::delete($backupPath);
                }
            }
            return back()->withErrors(['error' => 'Git ' . $action . ' failed: ' . $result->errorOutput()]);
        }

        // بازگردانی Stash لوکال در صورت وجود
        if ($stashed) {
            $unstashResult = Process::path($service->path)->run("{$gitBase} stash pop");
            if (!$unstashResult->successful()) {
                Log::warning('Failed to restore stashed changes after pull. You may have merge conflicts.', ['error' => $unstashResult->errorOutput()]);
            }
        }

        if ($action === 'clone') {
            $setupResult = $this->autoSetupLaravelProject($service);
            $message = "Git clone completed successfully.\n\n";
            if ($setupResult['is_laravel']) {
                $message .= "Laravel Auto-Setup:\n" . implode("\n", $setupResult['steps']);
                if (!empty($setupResult['errors'])) {
                    $message .= "\n\nErrors:\n" . implode("\n", $setupResult['errors']);
                }
            } else {
                $message .= "Project cloned successfully.";
            }
            return back()->with('success', $message);
        }

        // بازگردانی فایل‌های حساس بعد از فرآیند Pull
        if ($action === 'pull' && !empty($backupFiles)) {
            foreach ($backupFiles as $file => $backupPath) {
                if (!File::exists($backupPath)) continue;
                $filePath = $service->path . '/' . $file;

                if ($file === '.env') {
                    if (!File::exists($filePath)) {
                        @File::copy($backupPath, $filePath);
                    } else {
                        // اگر کلید اصلی از بین رفته بود بکاپ جایگزین شود
                        $currentContent = File::get($filePath);
                        $backupContent = File::get($backupPath);
                        $currentHasValidKey = preg_match('/^APP_KEY=base64:.+$/m', $currentContent);
                        $backupHasValidKey = preg_match('/^APP_KEY=base64:.+$/m', $backupContent);

                        if (($backupHasValidKey && !$currentHasValidKey)) {
                            @File::copy($backupPath, $filePath);
                        }
                    }
                }
                @File::delete($backupPath);
            }
        }

        // اجرای خودکار مایگریشن‌ها در صورت انتخاب کاربر
        $migrationOutput = '';
        if ($action === 'pull' && $runMigrations) {
            $migrationOutput = $this->runMigrations($service);
        }

        $message = "Git pull from origin main completed successfully.";
        if ($stashed) {
            $message .= ' Local changes were safely stashed and restored.';
        }
        if ($runMigrations) {
            $message .= $migrationOutput;
        }

        // تنظیم و تصحیح مجدد مجوزهای دسترسی لینوکس پس از دریافت فایل‌های جدید
        $this->fixPermissions($service->path);

        return back()->with('success', $message);
    }

    public function executeCommand(Request $request, Service $service)
    {
        // Increase limits for potentially long-running commands
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $commandType = $request->input('command');
        $allowedCommands = ['view:clear' => 'php artisan view:clear', 'config:clear' => 'php artisan config:clear', 'cache:clear' => 'php artisan cache:clear', 'optimize' => 'php artisan optimize --no-interaction', 'migrate' => 'php artisan migrate --force', 'composer_install' => 'composer install --no-dev --optimize-autoloader', 'composer_update' => 'composer update', 'npm_install' => 'npm install', 'npm_build' => 'npm run build', 'npm_clean' => 'rm -rf node_modules', 'key_generate' => 'php artisan key:generate', 'fix_permissions' => 'fix_permissions_internal', 'auto_setup' => 'auto_setup_internal'];
        if (!array_key_exists($commandType, $allowedCommands)) {
            return back()->withErrors(['error' => 'Invalid command.']);
        }
        if ($commandType === 'fix_permissions') {
            $this->fixPermissions($service->path);
            return back()->with('success', 'مجوزها (Permissions) با موفقیت اصلاح شدند.');
        }
        if ($commandType === 'optimize' && File::exists($service->path . '/artisan')) {
            try {
                $envPath = $service->path . '/.env';
                if (File::exists($envPath)) {
                    $envContent = File::get($envPath);
                    $lines = explode("\n", $envContent);
                    $fixedLines = [];
                    $hasError = false;
                    foreach ($lines as $line) {
                        $line = rtrim($line);
                        if (empty($line) || strpos($line, '#') === 0) {
                            $fixedLines[] = $line;
                            continue;
                        }
                        if (strpos($line, '=') === false) {
                            $hasError = true;
                            Log::warning('Skipping invalid .env line', ['line' => substr($line, 0, 50)]);
                            continue;
                        }
                        $line = preg_replace('/\s*=\s*/', '=', $line);
                        if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                            $key = trim($matches[1]);
                            $value = trim($matches[2]);
                            if (preg_match('/\$\{[^}]+\}/', $value)) {
                                if (preg_match('/\s/', $value) && !preg_match('/^["\'].*["\']$/', $value)) {
                                    $value = '"' . $value . '"';
                                }
                            } elseif (preg_match('/[^a-zA-Z0-9_\-.\/:@]/', $value) && !preg_match('/^["\'].*["\']$/', $value)) {
                                $value = '"' . addslashes($value) . '"';
                            }
                            $fixedLines[] = $key . '=' . $value;
                        } else {
                            $fixedLines[] = $line;
                        }
                    }
                    if ($hasError) {
                        $fixedContent = implode("\n", $fixedLines);
                        File::put($envPath, $fixedContent);
                        Log::info('Fixed .env file format issues', ['path' => $envPath]);
                    }
                }
                $env = ['COMPOSER_HOME' => '/tmp/composer_home_' . uniqid(), 'HOME' => '/tmp', 'PATH' => getenv('PATH') ?: '/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin'];
                File::makeDirectory($env['COMPOSER_HOME'], 0755, true);
                try {
                    Process::path($service->path)->env($env)->run('php artisan config:clear --no-interaction');
                } catch (\Exception $e) {
                    Log::warning('config:clear failed', ['error' => $e->getMessage()]);
                }
                try {
                    Process::path($service->path)->env($env)->run('php artisan cache:clear --no-interaction');
                } catch (\Exception $e) {
                    Log::warning('cache:clear failed', ['error' => $e->getMessage()]);
                }
                try {
                    Process::path($service->path)->env($env)->run('php artisan view:clear --no-interaction');
                } catch (\Exception $e) {
                    Log::warning('view:clear failed', ['error' => $e->getMessage()]);
                }
                if (File::exists($service->path . '/bootstrap/cache/config.php')) {
                    File::delete($service->path . '/bootstrap/cache/config.php');
                }
                $cacheDir = $service->path . '/bootstrap/cache';
                if (File::exists($cacheDir)) {
                    $cacheFiles = File::files($cacheDir);
                    foreach ($cacheFiles as $file) {
                        if (str_ends_with($file->getFilename(), '.php')) {
                            try {
                                File::delete($file->getPathname());
                            } catch (\Exception $e) {}
                        }
                    }
                }
                File::deleteDirectory($env['COMPOSER_HOME']);
                Log::info('Cleared config cache before optimize', ['path' => $service->path]);
            } catch (\Exception $e) {
                Log::warning('Failed to clear config cache before optimize', ['error' => $e->getMessage(), 'path' => $service->path]);
            }
        }
        if ($commandType === 'auto_setup') {
            $setupResult = $this->autoSetupLaravelProject($service);
            $message = "Laravel Auto-Setup completed.\n\n";
            if ($setupResult['is_laravel']) {
                $message .= implode("\n", $setupResult['steps']);
                if (!empty($setupResult['errors'])) {
                    $message .= "\n\nErrors:\n" . implode("\n", $setupResult['errors']);
                }
            } else {
                $message .= "This does not appear to be a Laravel project (no artisan file found).";
            }
            return back()->with('success', $message);
        }
        if ($commandType === 'npm_clean') {
            $nodeModulesPath = $service->path . '/node_modules';
            if (File::exists($nodeModulesPath)) {
                File::deleteDirectory($nodeModulesPath);
            }
            return back()->with('success', 'پوشه node_modules با موفقیت پاکسازی شد.');
        }
        if ($commandType === 'composer_install') {
            $envPath = $service->path . '/.env';
            $envExamplePath = $service->path . '/.env.example';
            if (!File::exists($envPath) && File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
            }
        }
        $cmd = $allowedCommands[$commandType];
        $artisanCommands = ['view:clear', 'config:clear', 'cache:clear', 'optimize', 'migrate', 'key_generate'];
        $runArtisanAsUser = false;
        if (in_array($commandType, $artisanCommands) && File::exists($service->path . '/artisan')) {
            try {
                $this->fixPermissions($service->path);
                $runArtisanAsUser = $this->getFileOwnerUser();
                Log::info('Fixed permissions before artisan command', ['command' => $commandType, 'path' => $service->path, 'user' => $runArtisanAsUser]);
            } catch (\Exception $e) {
                Log::warning('Failed to fix permissions before artisan command', ['command' => $commandType, 'path' => $service->path, 'error' => $e->getMessage()]);
            }
        }
        $npmCacheDir = '/tmp/npm_cache_' . uniqid();
        $env = ['COMPOSER_HOME' => '/tmp/composer_home_' . uniqid(), 'npm_config_cache' => $npmCacheDir, 'HOME' => '/tmp', 'PATH' => getenv('PATH') ?: '/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin' . ':' . $service->path . '/node_modules/.bin'];
        if ($commandType === 'npm_install' || $commandType === 'npm_build') {
            $env['npm_config_fetch_timeout'] = '900000';
            $env['npm_config_fetch_retries'] = '15';
            $env['npm_config_fetch_retry_mintimeout'] = '30000';
            $env['npm_config_fetch_retry_maxtimeout'] = '300000';
            $env['npm_config_maxsockets'] = '5';
            $env['npm_config_prefer_offline'] = 'false';
            $env['npm_config_progress'] = 'true';
            $env['npm_config_loglevel'] = 'warn';
            $env['npm_config_strict_ssl'] = 'false';
            $npmRegistry = env('NPM_REGISTRY');
            if ($npmRegistry) {
                $env['npm_config_registry'] = $npmRegistry;
            } else {
                $env['npm_config_registry'] = 'https://registry.npmjs.org/';
            }
        }
        File::makeDirectory($env['COMPOSER_HOME'], 0755, true);
        File::makeDirectory($env['npm_config_cache'], 0755, true);
        $timeout = 60;
        if ($commandType === 'npm_install') {
            $timeout = 3600;
        } elseif ($commandType === 'composer_install' || $commandType === 'composer_update') {
            $timeout = 600;
        }
        if ($commandType === 'npm_install') {
            $npmRegistry = env('NPM_REGISTRY');
            $registryFlag = $npmRegistry ? " --registry={$npmRegistry}" : '';
            $cmd = "npm install --prefer-offline --no-audit --legacy-peer-deps{$registryFlag} --fetch-timeout=900000 --fetch-retries=15";
        }
        if ($commandType === 'npm_install' && !$this->isWindows()) {
            $nodeModulesPath = $service->path . '/node_modules';
            if (File::exists($nodeModulesPath)) {
                try {
                    Log::info('Removing existing node_modules before npm install', ['path' => $nodeModulesPath]);
                    $this->runSudoCommand("sudo rm -rf " . $this->shEscape($nodeModulesPath));
                } catch (\Exception $e) {
                    Log::warning('Failed to remove node_modules, will continue anyway', ['error' => $e->getMessage()]);
                }
            }
        }
        $permissionsFixed = false;
        $runAsUser = null;
        if ($commandType === 'npm_install' && !$this->isWindows()) {
            $ubuntuUserCheck = Process::run('id ubuntu 2>&1');
            if ($ubuntuUserCheck->successful()) {
                $runAsUser = 'ubuntu';
            } else {
                $currentUser = $this->getCurrentUser();
                if ($currentUser !== 'www-data' && $currentUser !== 'root') {
                    $runAsUser = $currentUser;
                } else {
                    $usersCheck = Process::run('getent passwd | grep -E ":[0-9]{4}:" | head -1 | cut -d: -f1');
                    if ($usersCheck->successful()) {
                        $runAsUser = trim($usersCheck->output());
                    }
                }
            }
            if ($runAsUser) {
                try {
                    $pathEscaped = $this->shEscape($service->path);
                    $this->runSudoCommand("sudo chown -R {$runAsUser}:{$runAsUser} {$pathEscaped}");
                    $this->runSudoCommand("sudo chown -R {$runAsUser}:{$runAsUser} " . $this->shEscape($npmCacheDir));
                    $this->runSudoCommand("sudo chmod -R 755 " . $this->shEscape($npmCacheDir));
                    $this->runSudoCommand("sudo rm -rf " . $this->shEscape($npmCacheDir) . "/_cacache 2>/dev/null || true");
                    $permissionsFixed = true;
                    Log::info('Fixed permissions for npm install', ['user' => $runAsUser, 'path' => $service->path, 'cache' => $npmCacheDir]);
                } catch (\Exception $e) {
                    Log::warning('Failed to fix permissions before npm install', ['error' => $e->getMessage(), 'path' => $service->path]);
                }
            }
        }
        if ($commandType === 'npm_install' && !$this->isWindows()) {
            $pathEscaped = $this->shEscape($service->path);
            $logFile = $service->path . '/.npm_install.log';
            $errorLogFile = $service->path . '/.npm_install_error.log';
            $scriptFile = '/tmp/npm_install_' . uniqid() . '.sh';
            $envExports = '';
            foreach ($env as $key => $value) {
                $envExports .= 'export ' . $this->shEscape($key) . '=' . $this->shEscape($value) . "\n";
            }
            $scriptContent = "#!/bin/bash\nset +e\ncd {$pathEscaped} || exit 1\necho 'Cleaning up npm cache and preparing for install...' >> " . $this->shEscape($logFile) . "\n";
            if ($runAsUser) {
                $scriptContent .= "sudo chown -R {$runAsUser}:{$runAsUser} " . $this->shEscape($npmCacheDir) . " 2>/dev/null || true\nsudo chmod -R 755 " . $this->shEscape($npmCacheDir) . " 2>/dev/null || true\nsudo rm -rf " . $this->shEscape($npmCacheDir) . "/_cacache 2>/dev/null || true\n";
            }
            $scriptContent .= "touch " . $this->shEscape($logFile) . " 2>/dev/null || sudo touch " . $this->shEscape($logFile) . "\ntouch " . $this->shEscape($errorLogFile) . " 2>/dev/null || sudo touch " . $this->shEscape($errorLogFile) . "\nchmod 666 " . $this->shEscape($logFile) . " 2>/dev/null || sudo chmod 666 " . $this->shEscape($logFile) . "\nchmod 666 " . $this->shEscape($errorLogFile) . " 2>/dev/null || sudo chmod 666 " . $this->shEscape($errorLogFile) . "\necho 'Starting npm install at '$(date) >> " . $this->shEscape($logFile) . "\n" . $envExports;
            $npmRegistry = env('NPM_REGISTRY');
            if ($npmRegistry) {
                $scriptContent .= "export npm_config_registry=" . $this->shEscape($npmRegistry) . "\nnpm config set registry " . $this->shEscape($npmRegistry) . " 2>&1 | tee -a " . $this->shEscape($logFile) . "\n";
            }
            if ($runAsUser) {
                $currentUser = $this->getCurrentUser();
                $sudoPassword = env('SUDO_PASSWORD');
                if ($currentUser === 'root') {
                    $scriptContent .= "{$cmd} >> " . $this->shEscape($logFile) . " 2>> " . $this->shEscape($errorLogFile) . "\n";
                } elseif ($sudoPassword) {
                    $scriptContent .= "echo " . escapeshellarg($sudoPassword) . " | sudo -S -u {$runAsUser} bash -c 'cd {$pathEscaped} && {$envExports}{$cmd}' >> " . $this->shEscape($logFile) . " 2>> " . $this->shEscape($errorLogFile) . "\n";
                } else {
                    $scriptContent .= "sudo -u {$runAsUser} bash -c 'cd {$pathEscaped} && {$envExports}{$cmd}' >> " . $this->shEscape($logFile) . " 2>> " . $this->shEscape($errorLogFile) . "\n";
                }
            } else {
                $scriptContent .= "{$cmd} >> " . $this->shEscape($logFile) . " 2>> " . $this->shEscape($errorLogFile) . "\n";
            }
            $scriptContent .= "EXIT_CODE=\$?\nif [ \$EXIT_CODE -eq 0 ]; then\n    echo 'npm install completed successfully at '$(date) >> " . $this->shEscape($logFile) . "\nelse\n    echo 'npm install failed with exit code '\$EXIT_CODE' at '$(date) >> " . $this->shEscape($logFile) . "\nfi\n";
            if ($runAsUser) {
                $scriptContent .= "EXIT_CODE=\$?\nsudo chown -R www-data:www-data {$pathEscaped} 2>/dev/null || true\nsudo chmod -R 755 {$pathEscaped} 2>/dev/null || true\n";
                if (File::exists($service->path . '/storage')) {
                    $scriptContent .= "sudo chmod -R 775 " . $this->shEscape($service->path . '/storage') . " 2>/dev/null || true\n";
                }
                if (File::exists($service->path . '/bootstrap/cache')) {
                    $scriptContent .= "sudo chmod -R 775 " . $this->shEscape($service->path . '/bootstrap/cache') . " 2>/dev/null || true\n";
                }
                $scriptContent .= "exit \$EXIT_CODE\n";
            }
            try {
                File::put($scriptFile, $scriptContent);
                $this->runSudoCommand("sudo chmod +x " . $this->shEscape($scriptFile));
                if ($runAsUser) {
                    $this->runSudoCommand("sudo chmod 777 " . $this->shEscape($service->path));
                }
                $this->runSudoCommand("sudo chmod 777 " . $this->shEscape($service->path));
                if ($runAsUser) {
                    $this->runSudoCommand("sudo -u {$runAsUser} bash -c 'echo \"npm install started at $(date)\" > " . $this->shEscape($logFile) . "'");
                    $this->runSudoCommand("sudo -u {$runAsUser} bash -c 'echo \"npm install error log started at $(date)\" > " . $this->shEscape($errorLogFile) . "'");
                    $this->runSudoCommand("sudo chmod 666 " . $this->shEscape($logFile));
                    $this->runSudoCommand("sudo chmod 666 " . $this->shEscape($errorLogFile));
                    $this->runSudoCommand("sudo chown {$runAsUser}:{$runAsUser} " . $this->shEscape($logFile));
                    $this->runSudoCommand("sudo chown {$runAsUser}:{$runAsUser} " . $this->shEscape($errorLogFile));
                } else {
                    $this->runSudoCommand("sudo bash -c 'echo \"npm install started at $(date)\" > " . $this->shEscape($logFile) . "'");
                    $this->runSudoCommand("sudo bash -c 'echo \"npm install error log started at $(date)\" > " . $this->shEscape($errorLogFile) . "'");
                    $this->runSudoCommand("sudo chmod 666 " . $this->shEscape($logFile));
                    $this->runSudoCommand("sudo chmod 666 " . $this->shEscape($errorLogFile));
                }
                $backgroundCommand = "nohup bash " . $this->shEscape($scriptFile) . " >> " . $this->shEscape($logFile) . " 2>> " . $this->shEscape($errorLogFile) . " & echo \$!";
                $startResult = Process::run($backgroundCommand);
                if ($startResult->successful()) {
                    $pid = trim($startResult->output());
                    Log::info('npm install started in background', ['pid' => $pid, 'script' => $scriptFile, 'log' => $logFile, 'path' => $service->path]);
                    usleep(1000000);
                    if (File::exists($logFile)) {
                        return back()->with('success', "نصب npm در پس‌زمینه شروع شد (PID: {$pid}). برای پیگیری پیشرفت لاگ را چک کنید: {$logFile}");
                    } else {
                        return back()->with('warning', "npm install started in background but log file not found. Check: {$logFile} or error log: {$errorLogFile}");
                    }
                } else {
                    $error = $startResult->errorOutput();
                    Log::error('Failed to start npm install in background', ['error' => $error, 'command' => $backgroundCommand, 'script' => $scriptFile]);
                    if (File::exists($scriptFile)) {
                        Log::debug('npm install script content', ['script' => File::get($scriptFile)]);
                    }
                    @unlink($scriptFile);
                    return back()->withErrors(['error' => "Failed to start npm install in background. Error: {$error}. Check server logs for details."]);
                }
            } catch (\Exception $e) {
                Log::error('Exception while starting npm install in background', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                @unlink($scriptFile);
                return back()->withErrors(['error' => "Failed to start npm install: " . $e->getMessage()]);
            }
        }
        try {
            if ($runAsUser && $commandType === 'npm_install') {
                $pathEscaped = $this->shEscape($service->path);
                $envExports = '';
                foreach ($env as $key => $value) {
                    $envExports .= 'export ' . $this->shEscape($key) . '=' . $this->shEscape($value) . '; ';
                }
                $fullCommand = "sudo -u {$runAsUser} bash -c 'cd {$pathEscaped} && {$envExports}{$cmd}'";
                $currentUser = $this->getCurrentUser();
                $sudoPassword = env('SUDO_PASSWORD');
                if ($currentUser === 'root') {
                    $fullCommand = preg_replace('/^sudo\s+/', '', $fullCommand);
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($fullCommand);
                } elseif ($sudoPassword) {
                    $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $fullCommand);
                    $escapedPassword = escapeshellarg($sudoPassword);
                    $commandWithPassword = "printf %s {$escapedPassword} | sudo -S " . $commandWithoutSudo;
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($commandWithPassword);
                } else {
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($fullCommand);
                }
            } elseif ($runArtisanAsUser && in_array($commandType, $artisanCommands)) {
                $pathEscaped = $this->shEscape($service->path);
                $envExports = '';
                foreach ($env as $key => $value) {
                    $envExports .= 'export ' . $this->shEscape($key) . '=' . $this->shEscape($value) . '; ';
                }
                $fullCommand = "sudo -u {$runArtisanAsUser} bash -c 'cd {$pathEscaped} && {$envExports}{$cmd}'";
                $currentUser = $this->getCurrentUser();
                $sudoPassword = env('SUDO_PASSWORD');
                if ($currentUser === 'root') {
                    $fullCommand = preg_replace('/^sudo\s+/', '', $fullCommand);
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($fullCommand);
                } elseif ($sudoPassword) {
                    $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $fullCommand);
                    $escapedPassword = escapeshellarg($sudoPassword);
                    $commandWithPassword = "printf %s {$escapedPassword} | sudo -S " . $commandWithoutSudo;
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($commandWithPassword);
                } else {
                    $result = Process::path($service->path)->env($env)->timeout($timeout)->run($fullCommand);
                }
            } else {
                $result = Process::path($service->path)->env($env)->timeout($timeout)->run($cmd);
            }
        } catch (\Exception $e) {
            if ($runAsUser && $commandType === 'npm_install') {
                Log::warning('Failed to run npm install with user switching, trying without', ['user' => $runAsUser, 'error' => $e->getMessage()]);
                $result = Process::path($service->path)->env($env)->timeout($timeout)->run($cmd);
            } elseif ($runArtisanAsUser && in_array($commandType, $artisanCommands)) {
                Log::warning('Failed to run artisan command with user switching, trying without', ['user' => $runArtisanAsUser, 'command' => $commandType, 'error' => $e->getMessage()]);
                $result = Process::path($service->path)->env($env)->timeout($timeout)->run($cmd);
            } else {
                throw $e;
            }
        } finally {
            if ($permissionsFixed && !$this->isWindows()) {
                try {
                    $pathEscaped = $this->shEscape($service->path);
                    $this->runSudoCommand("sudo chown -R www-data:www-data {$pathEscaped}");
                    $this->runSudoCommand("sudo chmod -R 755 {$pathEscaped}");
                    if (File::exists($service->path . '/storage')) {
                        $this->runSudoCommand("sudo chmod -R 775 " . $this->shEscape($service->path . '/storage'));
                    }
                    if (File::exists($service->path . '/bootstrap/cache')) {
                        $this->runSudoCommand("sudo chmod -R 775 " . $this->shEscape($service->path . '/bootstrap/cache'));
                    }
                    Log::info('Restored permissions after npm install', ['path' => $service->path]);
                } catch (\Exception $e) {
                    Log::warning('Failed to restore permissions after npm install', ['error' => $e->getMessage(), 'path' => $service->path]);
                }
            }
        }
        File::deleteDirectory($env['COMPOSER_HOME']);
        File::deleteDirectory($env['npm_config_cache']);
        if ($result->successful() && $commandType === 'composer_install') {
            $this->ensureAppKey($service->path);
        }
        if ($result->successful()) {
            return back()->with('success', "Command executed successfully.\nOutput:\n" . $result->output());
        }
        return back()->withErrors(['error' => "Command failed.\nError:\n" . $result->errorOutput() . "\nOutput:\n" . $result->output()]);
    }

    public function npmInstallStatus(Service $service)
    {
        $logFile = $service->path . '/.npm_install.log';
        $errorLogFile = $service->path . '/.npm_install_error.log';
        $status = ['running' => false, 'log' => '', 'error_log' => '', 'log_exists' => false, 'error_log_exists' => false];
        if (!$this->isWindows()) {
            $checkProcess = Process::run("pgrep -f 'npm install' | grep -v grep");
            if ($checkProcess->successful() && !empty(trim($checkProcess->output()))) {
                $status['running'] = true;
            }
        }
        if (File::exists($logFile)) {
            $status['log_exists'] = true;
            try {
                $status['log'] = File::get($logFile);
            } catch (\Exception $e) {
                $status['log'] = 'Error reading log file: ' . $e->getMessage();
            }
        }
        if (File::exists($errorLogFile)) {
            $status['error_log_exists'] = true;
            try {
                $status['error_log'] = File::get($errorLogFile);
            } catch (\Exception $e) {
                $status['error_log'] = 'Error reading error log file: ' . $e->getMessage();
            }
        }
        return response()->json($status);
    }

    public function reset(Service $service)
    {
        try {
            if (File::exists($service->path)) {
                File::cleanDirectory($service->path);
            }
            $publicPath = $service->path . '/public';
            if (!$this->isWindows()) {
                $this->ensureDirectoryLinux($publicPath, 0755, 'www-data:www-data');
            } else {
                if (!File::exists($publicPath)) File::makeDirectory($publicPath, 0755, true);
            }
            $this->writePlaceholderFiles($service->domain, $service->type, $publicPath);
            $this->fixPermissions($service->path);
            $this->removeNginxConfig($service->domain);
            if ($service->type === 'subfolder') {
                $this->createSymlink($service->domain, $publicPath);
                $this->createNginxConfigSubfolder($service->domain, $publicPath);
            } else {
                $this->createNginxConfigSubdomain($service->domain, $service->path);
            }
            return back()->with('success', 'سرویس با موفقیت ریست شد (فایل‌ها و تنظیمات Nginx به‌روز شدند).');
        } catch (\Exception $e) {
            Log::error('Reset failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Failed to reset service: ' . $e->getMessage()]);
        }
    }

    public function destroy(Service $service)
    {
        try {
            if ($service->type === 'subfolder') {
                $linkPath = public_path($service->domain);
                if (is_link($linkPath) || file_exists($linkPath)) {
                    @unlink($linkPath);
                }
            }
            $this->removeNginxConfig($service->domain);
            if (File::exists($service->path)) {
                File::deleteDirectory($service->path);
            }
            $service->delete();
            return redirect()->route('services.index')->with('success', 'سرویس با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete service: ' . $e->getMessage()]);
        }
    }

    public function analyze(Service $service)
    {
        $diskUsageBytes = $service->getDiskUsage();
        $dbUsageBytes = $service->getDbUsage();
        $trafficUsageBytes = $service->getTrafficUsage();

        $formatBytes = function ($bytes) {
            if ($bytes == 0) return '0 B';
            $k = 1024;
            $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        };

        return view('services.analyze', [
            'service' => $service,
            'diskUsage' => $formatBytes($diskUsageBytes),
            'dbUsage' => $formatBytes($dbUsageBytes),
            'trafficUsage' => $formatBytes($trafficUsageBytes),
            'diskBytes' => $diskUsageBytes,
            'dbBytes' => $dbUsageBytes,
            'trafficBytes' => $trafficUsageBytes,
            'dbName' => $service->getDatabaseName() ?? 'یافت نشد',
        ]);
    }

    public function generateSsl(Request $request, Service $service)
    {
        $selectedDomain = trim($request->input('target_domain', ''));

        if (!empty($selectedDomain)) {
            $targetDomains = [$selectedDomain];
        } else {
            $targetDomains = array_unique(array_filter(array_merge(
                $service->getClientDomains(),
                [$service->domain]
            )));
        }

        if (empty($targetDomains)) {
            return back()->withErrors(['error' => 'هیچ دامنه‌ای برای این سرویس یافت نشد.']);
        }

        try {
            if ($this->isWindows()) {
                return back()->with('success', 'شبیه‌سازی: صدور گواهینامه SSL در ویندوز پشتیبانی نمی‌شود.');
            }

            $webroot = rtrim($service->path, '/') . '/public';
            $domainFlags = implode(' ', array_map(fn($d) => "-d " . escapeshellarg($d), $targetDomains));

            // 1. First try certbot with nginx plugin for all target domains
            $cmd = "sudo certbot --nginx {$domainFlags} --non-interactive --agree-tos --register-unsafely-without-email --redirect";
            $result = $this->runSudoCommand($cmd);

            // 2. If --nginx plugin fails, fallback to webroot method
            if (!$result->successful() && File::exists($webroot)) {
                $cmdWebroot = "sudo certbot certonly --webroot -w " . escapeshellarg($webroot) . " {$domainFlags} --non-interactive --agree-tos --register-unsafely-without-email";
                $resultWebroot = $this->runSudoCommand($cmdWebroot);
                if ($resultWebroot->successful()) {
                    $result = $resultWebroot;
                    if ($service->type === 'subdomain') {
                        $this->createNginxConfigSubdomain($service->domain, $service->path);
                    }
                }
            }

            // 3. Reload Nginx to apply changes
            $this->runSudoCommand("sudo systemctl reload nginx");

            $domainsListStr = implode(' ، ', $targetDomains);
            if ($result->successful()) {
                return back()->with('success', "گواهینامه SSL برای دامنه‌(های) [{$domainsListStr}] با موفقیت صادر و فعال گردید.");
            } else {
                $errorMsg = $result->errorOutput() ?: $result->output();
                Log::error('Certbot SSL issue failed', ['domains' => $targetDomains, 'error' => $errorMsg]);
                return back()->withErrors(['error' => 'خطا در صدور SSL: ' . $errorMsg]);
            }
        } catch (\Exception $e) {
            Log::error('Exception in generateSsl', ['service' => $service->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'استثنا در صدور SSL: ' . $e->getMessage()]);
        }
    }

    public function revokeSsl(Request $request, Service $service)
    {
        $domain = trim($request->input('target_domain', ''));
        if (empty($domain)) {
            $domain = $service->getPrimaryDomain();
        }

        try {
            if ($this->isWindows()) {
                return back()->with('success', 'شبیه‌سازی: لغو گواهینامه SSL در ویندوز پشتیبانی نمی‌شود.');
            }

            // Delete certificate using certbot
            $cmd = "sudo certbot delete --cert-name " . escapeshellarg($domain) . " --non-interactive";
            $this->runSudoCommand($cmd);

            // Re-apply nginx config
            if ($service->type === 'subdomain') {
                $this->createNginxConfigSubdomain($service->domain, $service->path);
            }

            $this->runSudoCommand("sudo systemctl reload nginx");

            return back()->with('success', "گواهینامه SSL برای دامنه [{$domain}] با موفقیت لغو و حذف گردید.");
        } catch (\Exception $e) {
            Log::error('Exception in revokeSsl', ['domain' => $domain, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'خطا در لغو گواهینامه SSL: ' . $e->getMessage()]);
        }
    }

    public function triggerAutoRenew(Service $service)
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('ssl:auto-renew');
            $output = \Illuminate\Support\Facades\Artisan::output();
            return back()->with('success', "فرآیند تمدید خودکار گواهینامه‌ها اجرا شد.\n" . $output);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در اجرای تمدید خودکار: ' . $e->getMessage()]);
        }
    }

    public function storeCustomDomain(Request $request, Service $service)
    {
        $request->validate([
            'custom_domain' => 'required|string|max:255',
        ]);

        $customDomain = strtolower(trim($request->input('custom_domain')));
        $customDomain = preg_replace('#^https?://#', '', $customDomain);
        $customDomain = trim(explode('/', $customDomain)[0]);

        if (empty($customDomain) || !preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i', $customDomain)) {
            return back()->withErrors(['error' => 'نام دامنه وارد شده معتبر نیست. لطفاً فرمت دامنه را بدون http/https وارد کنید (مثال: panel.shafa.doctor)']);
        }

        try {
            $mapping = \App\Models\DomainMapping::updateOrCreate(
                ['source_domain' => $customDomain],
                [
                    'service_id' => $service->id,
                    'destination_domain' => $service->domain,
                ]
            );

            // Sync with Domain model (Domain Control Center)
            \App\Models\Domain::updateOrCreate(
                ['domain' => $customDomain],
                [
                    'service_id'   => $service->id,
                    'status'       => \App\Models\Domain::STATUS_CONNECTED,
                    'dns_provider' => \App\Models\Domain::DNS_EXTERNAL,
                ]
            );

            // Recreate nginx config to handle new server_name if needed
            if ($service->type === 'subdomain') {
                $this->createNginxConfigSubdomain($service->domain, $service->path);
            }

            if ($request->has('issue_ssl')) {
                // Automatically issue SSL for this new domain
                return $this->generateSsl(new Request(['target_domain' => $customDomain]), $service);
            }

            return back()->with('success', "دامنه اختصاصی [{$customDomain}] با موفقیت برای این سرویس ثبت گردید.");
        } catch (\Exception $e) {
            Log::error('Error storing custom domain', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'خطا در ثبت دامنه اختصاصی: ' . $e->getMessage()]);
        }
    }

    public function destroyCustomDomain(Service $service, \App\Models\DomainMapping $domainMapping)
    {
        try {
            $domain = $domainMapping->source_domain;
            $domainMapping->delete();

            // Sync with Domain model
            \App\Models\Domain::where('domain', $domain)->delete();

            if ($service->type === 'subdomain') {
                $this->createNginxConfigSubdomain($service->domain, $service->path);
            }

            return back()->with('success', "دامنه اختصاصی [{$domain}] از این سرویس حذف شد.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در حذف دامنه اختصاصی: ' . $e->getMessage()]);
        }
    }

    public function getLogs(Service $service)
    {
        $domain = $this->nginxSafeName($service->domain);

        $paths = [
            'access' => "/var/log/nginx/{$domain}-access.log",
            'error' => "/var/log/nginx/{$domain}-error.log",
            'ssl_access' => "/var/log/nginx/{$domain}-ssl-access.log",
            'ssl_error' => "/var/log/nginx/{$domain}-ssl-error.log",
        ];

        $logs = [];

        foreach ($paths as $key => $path) {
            if (File::exists($path) && is_readable($path)) {
                $cmd = "tail -n 100 " . escapeshellarg($path) . " 2>/dev/null";
                $output = [];
                exec($cmd, $output);
                $logs[$key] = !empty($output) ? implode("\n", $output) : 'هیچ لاگی در این فایل ثبت نشده است.';
            } else {
                try {
                    $res = $this->runSudoCommand("sudo tail -n 100 " . escapeshellarg($path));
                    $logs[$key] = ($res->successful() && !empty(trim($res->output())))
                        ? trim($res->output())
                        : 'فایل لاگ یافت نشد یا هنوز لاگی برای این سرویس وجود ندارد.';
                } catch (\Exception $e) {
                    $logs[$key] = 'فایل لاگ ایجاد نشده یا خالی است.';
                }
            }
        }

        return response()->json($logs);
    }
}
