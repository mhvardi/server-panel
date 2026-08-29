<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Service;
use App\Services\ArvanCloudService;
use App\Services\DomainProvisioningService;
use App\Services\NginxService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DomainCenterController extends Controller
{
    public function __construct(
        protected ArvanCloudService        $arvan,
        protected NginxService             $nginx,
        protected DomainProvisioningService $provisioning,
    ) {}

    // ─── Dashboard ─────────────────────────────────────────────────

    /**
     * Overview dashboard with statistics.
     */
    public function index()
    {
        $total          = Domain::count();
        $connected      = Domain::where('status', Domain::STATUS_CONNECTED)->count();
        $parkedDefault  = Domain::where('status', Domain::STATUS_PARKED_DEFAULT)->count();
        $sslExpiring    = Domain::where('ssl_status', Domain::SSL_ACTIVE)
                                ->where('ssl_expires_at', '<=', Carbon::now()->addDays(30))
                                ->count();

        return view('domain-center.index', compact(
            'total', 'connected', 'parkedDefault', 'sslExpiring'
        ));
    }

    // ─── Domain list ───────────────────────────────────────────────

    /**
     * Full list of all domains.
     */
    public function domains()
    {
        $domains = Domain::with(['service', 'parkedOnDomain'])->orderByDesc('created_at')->get();

        return view('domain-center.domains', compact('domains'));
    }

    // ─── Connect (Arvan / Direct) ──────────────────────────────────

    /**
     * Show the domain connection form.
     */
    public function showConnect()
    {
        $arvanConnection = $this->arvan->checkConnection();
        $arvanDomains    = $arvanConnection['status'] ? ($this->arvan->getDomains()['data'] ?? []) : [];
        $services        = Service::orderBy('name')->get();
        $serverIp        = $this->nginx->getServerIp();

        return view('domain-center.connect', compact(
            'arvanDomains', 'arvanConnection', 'services', 'serverIp'
        ));
    }

    /**
     * Connect a domain via ArvanCloud (adds CNAME/ANAME record automatically).
     */
    public function connectArvan(Request $r)
    {
        $r->validate([
            'arvan_domain' => 'required|string',
            'service_id'   => 'required|exists:services,id',
            'subdomain'    => 'nullable|string|max:63',
        ]);

        $arvanZone = $r->arvan_domain;
        $subdomain = trim($r->subdomain ?? '');
        $fullDomain = $subdomain ? ($subdomain . '.' . $arvanZone) : $arvanZone;

        // Get server IP for CNAME target
        $serverIp = $this->nginx->getServerIp();

        // Add DNS record via ArvanCloud
        $result = $this->arvan->addCnameRecord($arvanZone, $subdomain ?: '@', $serverIp ?? '');

        $recordId = $result['data']['id'] ?? null;

        // Create the domain record
        $domain = Domain::create([
            'domain'          => $fullDomain,
            'status'          => Domain::STATUS_CONNECTED,
            'service_id'      => $r->service_id,
            'dns_provider'    => Domain::DNS_ARVAN,
            'arvan_zone'      => $arvanZone,
            'arvan_record_id' => $recordId,
        ]);

        // Provision Nginx
        $this->provisioning->provisionNginx($domain);

        return redirect()->route('domain-center.domains')
            ->with('success', "دامنه {$fullDomain} با موفقیت از طریق ابرآروان متصل شد.");
    }

    /**
     * Connect a domain directly (external or self-NS).
     */
    public function connectDirect(Request $r)
    {
        $r->validate([
            'direct_domain' => 'required|string|max:253',
            'service_id'    => 'required|exists:services,id',
        ]);

        $domainName = strtolower(trim($r->direct_domain));

        // Check DNS and NS records
        $pointsToUs = $this->provisioning->checkDomainPointsToUs($domainName);
        $nsInfo     = $this->provisioning->checkDomainNS($domainName);

        // Determine provider
        $provider = $nsInfo['points_to_us'] ? Domain::DNS_SELF_NS : Domain::DNS_EXTERNAL;

        $domain = Domain::create([
            'domain'       => $domainName,
            'status'       => Domain::STATUS_CONNECTED,
            'service_id'   => $r->service_id,
            'dns_provider' => $provider,
        ]);

        $this->provisioning->provisionNginx($domain);

        $dnsMsg = $pointsToUs
            ? 'DNS دامنه به سرور اشاره می‌کند.'
            : 'هشدار: DNS دامنه هنوز به IP سرور اشاره نمی‌کند.';

        return redirect()->route('domain-center.domains')
            ->with('success', "دامنه {$domainName} اضافه شد. {$dnsMsg}");
    }

    // ─── Parked domains ────────────────────────────────────────────

    /**
     * Show the park-a-domain form.
     */
    public function showParked()
    {
        $connectedDomains = Domain::where('status', Domain::STATUS_CONNECTED)
                                  ->with('service')
                                  ->orderBy('domain')
                                  ->get();

        $arvanConnection = $this->arvan->checkConnection();
        $arvanDomains    = $arvanConnection['status'] ? ($this->arvan->getDomains()['data'] ?? []) : [];
        $services        = Service::orderBy('name')->get();
        $serverIp        = $this->nginx->getServerIp();

        return view('domain-center.parked', compact(
            'connectedDomains', 'arvanDomains', 'arvanConnection', 'services', 'serverIp'
        ));
    }

    /**
     * Park a secondary domain on top of an existing connected domain.
     */
    public function connectParked(Request $r)
    {
        $r->validate([
            'parent_domain_id' => 'required|exists:domains,id',
            'parked_type'      => 'required|in:arvan,external',
            'parked_domain'    => 'required_if:parked_type,external|nullable|string|max:253',
            'arvan_domain'     => 'required_if:parked_type,arvan|nullable|string',
            'subdomain'        => 'nullable|string|max:63',
        ]);

        $primaryDomain = Domain::findOrFail($r->parent_domain_id);

        if ($r->parked_type === 'arvan') {
            $arvanZone  = $r->arvan_domain;
            $sub        = trim($r->subdomain ?? '');
            $fullDomain = $sub ? ($sub . '.' . $arvanZone) : $arvanZone;
            $serverIp   = $this->nginx->getServerIp();

            $result   = $this->arvan->addCnameRecord($arvanZone, $sub ?: '@', $serverIp ?? '');
            $recordId = $result['data']['id'] ?? null;

            $domain = Domain::create([
                'domain'          => $fullDomain,
                'status'          => Domain::STATUS_PARKED_ON,
                'parked_on_id'    => $primaryDomain->id,
                'service_id'      => $primaryDomain->service_id,
                'dns_provider'    => Domain::DNS_ARVAN,
                'arvan_zone'      => $arvanZone,
                'arvan_record_id' => $recordId,
            ]);
        } else {
            $fullDomain = strtolower(trim($r->parked_domain));

            $domain = Domain::create([
                'domain'       => $fullDomain,
                'status'       => Domain::STATUS_PARKED_ON,
                'parked_on_id' => $primaryDomain->id,
                'service_id'   => $primaryDomain->service_id,
                'dns_provider' => Domain::DNS_EXTERNAL,
            ]);
        }

        $this->provisioning->provisionNginxParked($domain, $primaryDomain);

        return redirect()->route('domain-center.domains')
            ->with('success', "دامنه {$fullDomain} با موفقیت روی {$primaryDomain->domain} پارک شد.");
    }

    // ─── Assign service ────────────────────────────────────────────

    /**
     * Assign a service to a parked/unassigned domain and provision Nginx.
     */
    public function assignService(Request $r, Domain $domain)
    {
        $r->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $domain->update([
            'service_id' => $r->service_id,
            'status'     => Domain::STATUS_CONNECTED,
        ]);

        $domain->refresh();
        $this->provisioning->provisionNginx($domain);

        return redirect()->route('domain-center.domains')
            ->with('success', "سرویس با موفقیت به دامنه {$domain->domain} اختصاص داده شد.");
    }

    // ─── NS Settings ───────────────────────────────────────────────

    /**
     * Static Name Server settings & instructions page.
     */
    public function nsSettings()
    {
        $serverIp  = $this->nginx->getServerIp();
        $nsRecords = ['ns1.vardicrm.ir', 'ns2.vardicrm.ir'];

        return view('domain-center.ns-settings', compact('serverIp', 'nsRecords'));
    }

    // ─── Delete ────────────────────────────────────────────────────

    /**
     * Delete a domain: remove ArvanCloud record (if arvan), remove Nginx config, delete DB record.
     */
    public function destroy(Domain $domain)
    {
        // Remove DNS record from ArvanCloud if applicable
        if ($domain->dns_provider === Domain::DNS_ARVAN && $domain->arvan_zone && $domain->arvan_record_id) {
            $this->arvan->deleteDnsRecord($domain->arvan_zone, $domain->arvan_record_id);
        }

        // Dispatch Nginx removal task
        $this->provisioning->removeConfig($domain);

        $domainName = $domain->domain;
        $domain->delete();

        return redirect()->route('domain-center.domains')
            ->with('success', "دامنه {$domainName} حذف شد.");
    }

    // ─── AJAX ──────────────────────────────────────────────────────

    /**
     * AJAX: Check DNS for a given domain name.
     */
    public function checkDns(Request $r)
    {
        $r->validate(['domain' => 'required|string|max:253']);

        $domain     = strtolower(trim($r->domain));
        $pointsToUs = $this->provisioning->checkDomainPointsToUs($domain);
        $nsInfo     = $this->provisioning->checkDomainNS($domain);

        $aRecords = @dns_get_record($domain, DNS_A);
        $aIps = array_column($aRecords ?: [], 'ip');

        return response()->json([
            'domain'          => $domain,
            'points_to_us'    => $pointsToUs,
            'a_records'       => $aIps,
            'ns_records'      => $nsInfo['ns'],
            'ns_points_to_us' => $nsInfo['points_to_us'],
            'server_ip'       => $this->nginx->getServerIp(),
        ]);
    }
}
