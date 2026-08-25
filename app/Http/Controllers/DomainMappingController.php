<?php

namespace App\Http\Controllers;

use App\Models\DomainMapping;
use App\Models\Service;
use App\Services\ArvanCloudService;
use App\Services\NginxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DomainMappingController extends Controller
{
    protected ArvanCloudService $arvan;
    protected NginxService $nginx;

    public function __construct(ArvanCloudService $arvan, NginxService $nginx)
    {
        $this->arvan = $arvan;
        $this->nginx = $nginx;
    }

    public function index()
    {
        $services = Service::all();
        $mappings = DomainMapping::with(['service', 'parentMapping', 'parkedMappings'])->get();

        $arvanConnection = $this->arvan->checkConnection();
        $arvanDomains    = ($arvanConnection['status']) ? $this->arvan->getDomains()['data'] ?? [] : [];

        $serverIp = $this->nginx->getServerIp();

        return view('domain-mappings.index', compact(
            'services', 'mappings', 'arvanConnection', 'arvanDomains', 'serverIp'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // سناریو ۱: اتصال از طریق ابرآروان
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'arvan_domain' => 'required|string',
            'service_id'   => 'required|exists:services,id',
            'subdomain'    => 'nullable|string|regex:/^[a-zA-Z0-9-]*$/',
        ]);

        $service     = Service::findOrFail($request->service_id);
        $arvanDomain = $request->arvan_domain;
        $subdomain   = $request->subdomain;

        $subdomainForRecord = empty($subdomain) ? '@' : $subdomain;
        $sourceDomain       = empty($subdomain) ? $arvanDomain : "{$subdomain}.{$arvanDomain}";
        $destinationDomain  = $service->domain;

        $response = $this->arvan->addCnameRecord($arvanDomain, $subdomainForRecord, $destinationDomain);

        if (!isset($response['data']['id'])) {
            return back()->with('error', 'خطا در ساخت رکورد DNS در ابرآروان: ' . json_encode($response));
        }

        $dnsRecordId = $response['data']['id'];

        $mapping = DomainMapping::updateOrCreate(
            ['source_domain' => $sourceDomain],
            [
                'service_id'        => $service->id,
                'destination_domain'=> $destinationDomain,
                'arvan_domain'      => $arvanDomain,
                'mapping_type'      => DomainMapping::TYPE_ARVAN,
                'dns_record_id'     => $dnsRecordId,
                'is_primary'        => true,
            ]
        );

        $this->dispatchConfigurationTask($mapping);
        $this->arvan->purgeCache($arvanDomain);

        return redirect()->route('domain-mappings.index')
            ->with('success', "رکورد DNS در ابرآروان ساخته شد و سرویس برای «{$sourceDomain}» پیکربندی گردید.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // سناریو ۲: Parked Domain (پارک دامنه دوم روی دامنه اول)
    // ─────────────────────────────────────────────────────────────────────────
    public function storeParked(Request $request)
    {
        $request->validate([
            'parent_mapping_id' => 'required|exists:domain_mappings,id',
            'parked_domain'     => 'required|string|regex:/^[a-zA-Z0-9.-]+$/',
            'parked_type'       => 'required|in:arvan,external',
            // اگر آروان: دامنه آروان انتخاب می‌شود
            'arvan_domain'      => 'nullable|string',
            'parked_subdomain'  => 'nullable|string|regex:/^[a-zA-Z0-9-]*$/',
        ]);

        $parentMapping   = DomainMapping::findOrFail($request->parent_mapping_id);
        $service         = $parentMapping->service;
        $parkedDomain    = $request->parked_domain;
        $parkedType      = $request->parked_type;

        $dnsRecordId = null;

        if ($parkedType === 'arvan') {
            // ساخت CNAME در ابرآروان برای دامنه دوم
            $arvanDomain = $request->arvan_domain;
            $subdomain   = $request->parked_subdomain;
            $subdomainForRecord = empty($subdomain) ? '@' : $subdomain;
            $parkedDomain = empty($subdomain) ? $arvanDomain : "{$subdomain}.{$arvanDomain}";

            $response = $this->arvan->addCnameRecord($arvanDomain, $subdomainForRecord, $service->domain);
            if (!isset($response['data']['id'])) {
                return back()->with('error', 'خطا در ساخت رکورد DNS در ابرآروان: ' . json_encode($response));
            }
            $dnsRecordId = $response['data']['id'];
            $this->arvan->purgeCache($arvanDomain);
        }
        // برای external: کاربر A record را خودش تنظیم کرده — فقط Nginx alias می‌سازیم

        $mapping = DomainMapping::updateOrCreate(
            ['source_domain' => $parkedDomain],
            [
                'service_id'         => $service->id,
                'destination_domain' => $service->domain,
                'arvan_domain'       => $parkedType === 'arvan' ? ($request->arvan_domain ?? null) : null,
                'mapping_type'       => DomainMapping::TYPE_PARKED,
                'parent_mapping_id'  => $parentMapping->id,
                'dns_record_id'      => $dnsRecordId,
                'is_primary'         => false,
            ]
        );

        // تسک Nginx alias: دامنه دوم را به همان config دامنه اول اضافه می‌کند
        $aliasTask = $this->nginx->addServerAlias($parentMapping->source_domain, $parkedDomain);
        $this->dispatchTask(array_merge($aliasTask, [
            'service_path' => $service->path,
            'mapping_id'   => $mapping->id,
        ]));

        return redirect()->route('domain-mappings.index')
            ->with('success', "دامنه «{$parkedDomain}» با موفقیت روی «{$parentMapping->source_domain}» پارک شد.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // سناریو ۳: Direct NS (بدون ابرآروان)
    // ─────────────────────────────────────────────────────────────────────────
    public function storeDirect(Request $request)
    {
        $request->validate([
            'service_id'    => 'required|exists:services,id',
            'direct_domain' => 'required|string|regex:/^[a-zA-Z0-9.-]+$/',
        ]);

        $service      = Service::findOrFail($request->service_id);
        $directDomain = strtolower(trim($request->direct_domain));

        // بررسی DNS — آیا دامنه واقعاً به سرور ما resolve می‌شود؟
        $resolved = $this->nginx->domainResolvesToUs($directDomain);

        $mapping = DomainMapping::updateOrCreate(
            ['source_domain' => $directDomain],
            [
                'service_id'         => $service->id,
                'destination_domain' => $service->domain,
                'mapping_type'       => DomainMapping::TYPE_DIRECT,
                'is_primary'         => true,
                'dns_record_id'      => null,
                'arvan_domain'       => null,
            ]
        );

        // ساخت config Nginx مستقیم
        $configTask = $this->nginx->generateDirectConfig($directDomain, $service->path);
        $this->dispatchTask(array_merge($configTask, [
            'new_app_url'    => 'https://' . $directDomain,
            'new_server_name'=> $directDomain . ' www.' . $directDomain,
            'mapping_id'     => $mapping->id,
        ]));

        $message = "دامنه «{$directDomain}» ثبت شد و پیکربندی Nginx ارسال گردید.";
        if (!$resolved) {
            $message .= ' ⚠️ توجه: DNS این دامنه هنوز به IP سرور ما اشاره نمی‌کند.';
        }

        return redirect()->route('domain-mappings.index')->with(
            $resolved ? 'success' : 'info',
            $message
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // اعمال مجدد پیکربندی
    // ─────────────────────────────────────────────────────────────────────────
    public function reprovision(DomainMapping $domainMapping)
    {
        if ($domainMapping->isParked()) {
            $parent = $domainMapping->parentMapping;
            $task   = $this->nginx->addServerAlias(
                $parent ? $parent->source_domain : $domainMapping->destination_domain,
                $domainMapping->source_domain
            );
            $this->dispatchTask(array_merge($task, [
                'service_path' => $domainMapping->service->path,
                'mapping_id'   => $domainMapping->id,
            ]));
        } elseif ($domainMapping->isDirect()) {
            $task = $this->nginx->generateDirectConfig($domainMapping->source_domain, $domainMapping->service->path);
            $this->dispatchTask(array_merge($task, ['mapping_id' => $domainMapping->id]));
        } else {
            // arvan
            $this->dispatchConfigurationTask($domainMapping);
            $arvanDomain = $this->extractDomainParts($domainMapping->source_domain)['arvanDomain'];
            $this->arvan->purgeCache($arvanDomain);
        }

        return redirect()->route('domain-mappings.index')
            ->with('success', "پیکربندی برای «{$domainMapping->source_domain}» مجدداً اعمال شد.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // حذف mapping
    // ─────────────────────────────────────────────────────────────────────────
    public function destroy(DomainMapping $domainMapping)
    {
        // حذف رکورد DNS در ابرآروان (اگر وجود دارد)
        if ($domainMapping->dns_record_id && $domainMapping->arvan_domain) {
            $this->arvan->deleteDnsRecord($domainMapping->arvan_domain, $domainMapping->dns_record_id);
        } elseif ($domainMapping->isArvan() && !$domainMapping->dns_record_id) {
            // fallback برای رکوردهای قدیمی که ID ذخیره نشده
            $parts    = $this->extractDomainParts($domainMapping->source_domain);
            $recordId = $this->arvan->findDnsRecordId($parts['arvanDomain'], $parts['subdomain']);
            if ($recordId) {
                $this->arvan->deleteDnsRecord($parts['arvanDomain'], $recordId);
            }
        }

        $domain = $domainMapping->source_domain;
        $domainMapping->delete();

        return redirect()->route('domain-mappings.index')
            ->with('success', "دامنه «{$domain}» و رکورد DNS مربوطه حذف شدند.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // متدهای کمکی
    // ─────────────────────────────────────────────────────────────────────────
    private function dispatchConfigurationTask(DomainMapping $domainMapping)
    {
        $task = [
            'action'          => 'configure_service',
            'service_path'    => $domainMapping->service->path,
            'new_app_url'     => 'https://' . $domainMapping->source_domain,
            'new_server_name' => $domainMapping->source_domain . ' www.' . $domainMapping->source_domain,
            'mapping_id'      => $domainMapping->id,
            'created_at'      => now()->toIso8601String(),
        ];
        $this->dispatchTask($task);
    }

    private function dispatchTask(array $task)
    {
        $fileName = 'task_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.json';
        try {
            Storage::disk('local')->put('tasks/' . $fileName, json_encode($task, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Log::info('TASK_CREATED', ['file' => $fileName]);
        } catch (\Exception $e) {
            Log::error('TASK_CREATION_FAILED', ['error' => $e->getMessage()]);
        }
    }

    private function extractDomainParts(string $sourceDomain): array
    {
        $arvanDomains = $this->arvan->getDomains()['data'] ?? [];

        foreach ($arvanDomains as $d) {
            if (str_ends_with($sourceDomain, $d['name'])) {
                $arvanDomain = $d['name'];
                $subdomain   = trim(str_replace($arvanDomain, '', $sourceDomain), '.');
                return [
                    'arvanDomain' => $arvanDomain,
                    'subdomain'   => $subdomain === '' ? '@' : $subdomain,
                ];
            }
        }

        $parts = explode('.', $sourceDomain);
        if (count($parts) > 2) {
            return [
                'arvanDomain' => implode('.', array_slice($parts, 1)),
                'subdomain'   => $parts[0],
            ];
        }

        return ['arvanDomain' => $sourceDomain, 'subdomain' => '@'];
    }
}