<?php

namespace App\Http\Controllers;

use App\Models\DomainMapping;
use App\Models\Service;
use App\Services\ArvanCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DomainMappingController extends Controller
{
    protected $arvan;

    public function __construct(ArvanCloudService $arvan)
    {
        $this->arvan = $arvan;
    }

    public function index()
    {
        $services = Service::all();
        $mappings = DomainMapping::with('service')->get();

        $arvanConnection = $this->arvan->checkConnection();
        $arvanDomains = ($arvanConnection['status']) ? $this->arvan->getDomains()['data'] ?? [] : [];

        return view('domain-mappings.index', compact('services', 'mappings', 'arvanConnection', 'arvanDomains'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'arvan_domain' => 'required|string',
            'service_id' => 'required|exists:services,id',
            // ساب‌دامین حالا اختیاری است
            'subdomain' => 'nullable|string|regex:/^[a-zA-Z0-9-]*$/',
        ]);

        $service = Service::findOrFail($request->service_id);
        $arvanDomain = $request->arvan_domain;
        $subdomain = $request->subdomain;
        $destinationDomain = $service->domain;

        // اگر ساب‌دامین خالی باشد، برای رکورد DNS مقدار @ (روت) در نظر گرفته می‌شود
        $subdomainForRecord = empty($subdomain) ? '@' : $subdomain;
        $sourceDomain = empty($subdomain) ? $arvanDomain : "{$subdomain}.{$arvanDomain}";

        // Create CNAME record on Arvan Cloud
        $response = $this->arvan->addCnameRecord($arvanDomain, $subdomainForRecord, $destinationDomain);

        if (!isset($response['data']['id'])) {
            return back()->with('error', 'Failed to create DNS record on Arvan Cloud. Response: ' . json_encode($response));
        }

        // Save the mapping in our database (or update if already mapped)
        $mapping = DomainMapping::updateOrCreate(
            ['source_domain' => $sourceDomain],
            [
                'service_id' => $service->id,
                'destination_domain' => $destinationDomain,
            ]
        );

        // Dispatch configuration task for the service
        $this->dispatchConfigurationTask($mapping);
        $this->arvan->purgeCache($arvanDomain);

        return redirect()->route('domain-mappings.index')->with('success', "رکورد DNS متصل شد و سرویس برای دامنه {$sourceDomain} تنظیم گردید.");
    }

    public function reprovision(DomainMapping $domainMapping)
    {
        // استفاده از متد کمکی برای تشخیص درست دامنه آروان (حتی برای دامنه‌های بدون ساب‌دامین)
        $domainParts = $this->extractDomainParts($domainMapping->source_domain);
        $arvanDomain = $domainParts['arvanDomain'];

        // Dispatch configuration task for the service
        $this->dispatchConfigurationTask($domainMapping);
        $this->arvan->purgeCache($arvanDomain);

        return redirect()->route('domain-mappings.index')->with('success', "وظیفه پیکربندی برای {$domainMapping->source_domain} مجدداً ارسال شد.");
    }

    public function destroy(DomainMapping $domainMapping)
    {
        // استفاده از متد کمکی برای تشخیص درست دامنه آروان و رکورد آن
        $domainParts = $this->extractDomainParts($domainMapping->source_domain);
        $arvanDomain = $domainParts['arvanDomain'];
        $subdomain = $domainParts['subdomain'];

        $recordId = $this->arvan->findDnsRecordId($arvanDomain, $subdomain);

        if ($recordId) {
            $this->arvan->deleteDnsRecord($arvanDomain, $recordId);
        }

        $domainMapping->delete();

        return redirect()->route('domain-mappings.index')->with('success', 'اتصال دامنه و رکورد DNS مربوطه حذف شدند.');
    }

    private function dispatchConfigurationTask(DomainMapping $domainMapping)
    {
        $task = [
            'action' => 'configure_service',
            'service_path' => $domainMapping->service->path,
            'new_app_url' => 'https://' . $domainMapping->source_domain,
            'new_server_name' => $domainMapping->source_domain . ' www.' . $domainMapping->source_domain,
            'mapping_id' => $domainMapping->id,
            'created_at' => now()->toIso8601String(),
        ];

        $taskFileName = 'mapping_' . $domainMapping->id . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.json';

        try {
            // Use the 'local' disk which points to storage/app/private
            Storage::disk('local')->put('tasks/' . $taskFileName, json_encode($task, JSON_PRETTY_PRINT));
            Log::info('TASK_CREATED', ['file' => $taskFileName, 'mapping_id' => $domainMapping->id, 'path' => Storage::disk('local')->path('tasks/')]);
        } catch (\Exception $e) {
            Log::error('TASK_CREATION_FAILED', ['file' => $taskFileName, 'mapping_id' => $domainMapping->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * متد کمکی برای تفکیک صحیح دامنه اصلی و ساب‌دامین
     */
    private function extractDomainParts($sourceDomain)
    {
        // تلاش برای یافتن دامنه اصلی از لیست دامنه‌های آروان
        $arvanDomains = $this->arvan->getDomains()['data'] ?? [];

        foreach ($arvanDomains as $d) {
            if (str_ends_with($sourceDomain, $d['name'])) {
                $arvanDomain = $d['name'];
                $subdomain = trim(str_replace($arvanDomain, '', $sourceDomain), '.');
                return [
                    'arvanDomain' => $arvanDomain,
                    'subdomain' => $subdomain === '' ? '@' : $subdomain
                ];
            }
        }

        // در صورتی که نتوانست از API دریافت کند، حدس می‌زند (Fallback)
        $parts = explode('.', $sourceDomain);
        if (count($parts) > 2) {
            return [
                'arvanDomain' => implode('.', array_slice($parts, 1)),
                'subdomain' => $parts[0]
            ];
        }

        return [
            'arvanDomain' => $sourceDomain,
            'subdomain' => '@'
        ];
    }
}