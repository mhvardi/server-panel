<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DomainProvisioningService
{
    public function __construct(
        protected NginxService $nginx,
    ) {}

    // ─── Task dispatch ─────────────────────────────────────────────

    /**
     * Write a JSON task file to storage/app/private/tasks/ for the server agent.
     */
    private function dispatchTask(array $task): void
    {
        $fileName = 'task_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.json';
        try {
            Storage::disk('local')->put(
                'tasks/' . $fileName,
                json_encode($task, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            Log::info('DOMAIN_TASK_CREATED', ['file' => $fileName, 'action' => $task['action'] ?? null]);
        } catch (\Exception $e) {
            Log::error('DOMAIN_TASK_CREATION_FAILED', ['error' => $e->getMessage()]);
        }
    }

    // ─── Provisioning methods ──────────────────────────────────────

    /**
     * Dispatch a task to configure a direct Nginx vhost for a connected domain.
     * The server agent will apply the Nginx config and reload.
     */
    public function provisionNginx(Domain $domain): void
    {
        $servicePath = $domain->service?->path ?? '';

        $task = [
            'action'          => 'configure_direct_domain',
            'domain'          => $domain->domain,
            'service_path'    => $servicePath,
            'new_app_url'     => 'https://' . $domain->domain,
            'new_server_name' => $domain->domain . ' www.' . $domain->domain,
            'mapping_id'      => $domain->id,
            'created_at'      => now()->toIso8601String(),
        ];

        $this->dispatchTask($task);
    }

    /**
     * Dispatch a task to add a server_name alias for a parked domain.
     * The primary domain's Nginx config will have the alias added to it.
     */
    public function provisionNginxParked(Domain $domain, Domain $primaryDomain): void
    {
        $servicePath = $primaryDomain->service?->path ?? $domain->service?->path ?? '';

        $task = [
            'action'         => 'add_server_alias',
            'primary_domain' => $primaryDomain->domain,
            'aliased_domain' => $domain->domain,
            'service_path'   => $servicePath,
            'mapping_id'     => $domain->id,
            'created_at'     => now()->toIso8601String(),
        ];

        $this->dispatchTask($task);
    }

    /**
     * Dispatch a task to generate a static parked-page HTML for an unassigned domain.
     */
    public function generateParkedPageConfig(string $domain): void
    {
        $task = [
            'action'    => 'generate_parked_page',
            'domain'    => $domain,
            'html_path' => '/var/www/parked-domain/index.html',
            'created_at' => now()->toIso8601String(),
        ];

        $this->dispatchTask($task);
    }

    /**
     * Run certbot to issue an SSL certificate for a domain.
     * Returns ['success' => bool, 'output' => string].
     */
    public function issueSSL(Domain $domain): array
    {
        $cmd = sprintf(
            'sudo certbot certonly --nginx -d %s --non-interactive --agree-tos --email admin@vardicrm.ir 2>&1',
            escapeshellarg($domain->domain)
        );

        Log::info('SSL_CERTBOT_START', ['domain' => $domain->domain, 'cmd' => $cmd]);
        $output  = shell_exec($cmd) ?? '';
        $success = str_contains($output, 'Successfully received certificate')
                || str_contains($output, 'Certificate not yet due for renewal');

        Log::info('SSL_CERTBOT_RESULT', ['domain' => $domain->domain, 'success' => $success]);

        return ['success' => $success, 'output' => $output];
    }

    /**
     * Dispatch a task to remove the Nginx config for this domain.
     */
    public function removeConfig(Domain $domain): void
    {
        $task = [
            'action'             => 'remove_domain_config',
            'domain'             => $domain->domain,
            'nginx_config_path'  => $domain->nginx_config_path,
            'created_at'         => now()->toIso8601String(),
        ];

        $this->dispatchTask($task);
    }

    // ─── DNS helpers ───────────────────────────────────────────────

    /**
     * Check if the given domain's A record points to this server's IP.
     */
    public function checkDomainPointsToUs(string $domain): bool
    {
        return $this->nginx->domainResolvesToUs($domain);
    }

    /**
     * Retrieve NS records for the given domain and check if it uses our nameservers.
     *
     * @return array{ns: string[], points_to_us: bool}
     */
    public function checkDomainNS(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_NS) ?: [];

        $ns = array_map(fn($r) => rtrim($r['target'] ?? '', '.'), $records);

        // Our nameservers
        $ourNs = ['ns1.vardicrm.ir', 'ns2.vardicrm.ir'];

        $pointsToUs = !empty(array_intersect(
            array_map('strtolower', $ns),
            $ourNs
        ));

        return [
            'ns'           => $ns,
            'points_to_us' => $pointsToUs,
        ];
    }
}
