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

        // ۱. بررسی اینکه آیا این رکورد از قبل در آروان وجود دارد یا خیر (بدون حساسیت به نوع رکورد)
        $existingRecord = $this->findAnyDnsRecord($domain, $recordName);

        if ($existingRecord) {
            Log::info("Record already exists in ArvanCloud. Updating target...", ['domain' => $domain, 'name' => $recordName, 'id' => $existingRecord['id']]);
            
            // سعی در بروزرسانی رکورد موجود
            $updateUrl = "{$this->baseUrl}/domains/{$domain}/dns-records/{$existingRecord['id']}";
            $updateData = [
                'type' => $existingRecord['type'],
                'name' => $recordName,
                'value' => [($existingRecord['type'] === 'aname' ? 'location' : 'host') => $target],
                'cloud' => true,
                'ttl' => 120,
            ];
            $updateRes = Http::withHeaders($this->getHeaders())->put($updateUrl, $updateData);
            
            return ['data' => ['id' => $existingRecord['id']]];
        }

        $data = [
            'type' => $type,
            'name' => $recordName,
            'value' => [$valueKey => $target],
            'cloud' => true,
            'ttl' => 120,
        ];

        Log::info("Creating {$type} record in ArvanCloud.", ['domain' => $domain, 'data' => $data]);
        $response = Http::withHeaders($this->getHeaders())->post($url, $data);
        $resJson = $response->json();

        // اگر آروان خطای تکراری بودن داد، رکورد را مجدداً جستجو کرده و شناسه آن را بازمی‌گردانیم
        if (!$response->successful() && isset($resJson['errors'])) {
            $duplicateCheck = $this->findAnyDnsRecord($domain, $recordName);
            if ($duplicateCheck) {
                return ['data' => ['id' => $duplicateCheck['id']]];
            }
        }

        return $resJson;
    }

    public function findAnyDnsRecord($domain, $subdomain)
    {
        $url = "{$this->baseUrl}/domains/{$domain}/dns-records";
        $response = Http::withHeaders($this->getHeaders())->get($url, ['per_page' => 100]);

        if ($response->successful()) {
            foreach ($response->json('data', []) as $record) {
                if (strtolower($record['name']) === strtolower($subdomain)) {
                    return $record;
                }
            }
        }
        return null;
    }

    public function findDnsRecordId($domain, $subdomain, $type = null)
    {
        $record = $this->findAnyDnsRecord($domain, $subdomain);
        return $record['id'] ?? null;
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