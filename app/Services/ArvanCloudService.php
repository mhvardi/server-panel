<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArvanCloudService
{
    protected $apiKey;
    protected $baseUrl = 'https://napi.arvancloud.ir/cdn/4.0';

    public function __construct()
    {
        $this->apiKey = env('ARVAN_API_KEY');
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'ApiKey ' . $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    public function checkConnection()
    {
        if (empty($this->apiKey)) {
            return ['status' => false, 'message' => 'ARVAN_API_KEY is not set in your .env file.'];
        }
        return $this->getDomains(1, 1);
    }

    public function getDomains($perPage = 100, $page = 1)
    {
        $url = "{$this->baseUrl}/domains";
        $response = Http::withHeaders($this->getHeaders())->get($url, ['per_page' => $perPage, 'page' => $page]);

        if ($response->successful()) {
            return ['status' => true, 'data' => $response->json('data', [])];
        }

        $message = 'Failed to fetch domains from Arvan Cloud.';
        if ($response->status() === 401) {
            $message = 'Connection failed: Invalid API Key.';
        }

        return ['status' => false, 'message' => $message, 'response' => $response->body()];
    }

    public function addCnameRecord($domain, $subdomain, $target)
    {
        $url = "{$this->baseUrl}/domains/{$domain}/dns-records";

        // تشخیص خودکار نوع رکورد: اگر @ باشد ANAME وگرنه CNAME
        $type = ($subdomain === '@' || empty($subdomain)) ? 'aname' : 'cname';
        $recordName = empty($subdomain) ? '@' : $subdomain;

        // در API آروان، CNAME از کلید host و ANAME از کلید location استفاده می‌کند
        $valueKey = ($type === 'aname') ? 'location' : 'host';

        // ۱. بررسی اینکه آیا این رکورد از قبل در آروان وجود دارد یا خیر
        $existingId = $this->findDnsRecordId($domain, $recordName, $type);

        if ($existingId) {
            Log::info("Record already exists. Skipping creation and returning existing ID.", ['domain' => $domain, 'name' => $recordName]);
            // اگر وجود داشت، به جای ساخت مجدد و دریافت ارور داپلیکیت، شبیه‌سازی یک عملیات موفق را انجام می‌دهیم
            return ['data' => ['id' => $existingId]];
        }

        $data = [
            'type' => $type,
            'name' => $recordName,
            'value' => [$valueKey => $target],
            'cloud' => true,
            'ttl' => 120,
        ];

        Log::info("Creating {$type} record.", ['domain' => $domain, 'data' => $data]);
        return Http::withHeaders($this->getHeaders())->post($url, $data)->json();
    }

    public function findDnsRecordId($domain, $subdomain, $type = null)
    {
        $url = "{$this->baseUrl}/domains/{$domain}/dns-records";
        $response = Http::withHeaders($this->getHeaders())->get($url, ['search' => $subdomain]);

        // اگر نوع رکورد از سمت کنترلر فرستاده نشده باشد، به‌صورت هوشمند تشخیص می‌دهد
        if (is_null($type)) {
            $type = ($subdomain === '@' || empty($subdomain)) ? 'aname' : 'cname';
        }

        if ($response->successful()) {
            foreach ($response->json('data', []) as $record) {
                if ($record['name'] === $subdomain && $record['type'] === $type) {
                    return $record['id'];
                }
            }
        }
        return null;
    }

    public function deleteDnsRecord($domain, $recordId)
    {
        $url = "{$this->baseUrl}/domains/{$domain}/dns-records/{$recordId}";
        return Http::withHeaders($this->getHeaders())->delete($url)->successful();
    }

    public function purgeCache($domain)
    {
        $url = "{$this->baseUrl}/domains/{$domain}/caching/purge";
        return Http::withHeaders($this->getHeaders())->post($url, ['purge' => 'all'])->successful();
    }
}