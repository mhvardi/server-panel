<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('domain_mappings') || !Schema::hasTable('domains')) {
            return;
        }

        // 1. First pass: Migrate all primary / independent domain mappings
        $primaryMappings = DB::table('domain_mappings')
            ->whereNull('parent_mapping_id')
            ->get();

        foreach ($primaryMappings as $mapping) {
            $domainName = trim($mapping->source_domain);
            if (empty($domainName)) {
                continue;
            }

            // Determine DNS provider
            $dnsProvider = 'external';
            $mappingType = $mapping->mapping_type ?? null;
            $arvanDomain = $mapping->arvan_domain ?? null;

            if ($mappingType === 'arvan' || !empty($arvanDomain)) {
                $dnsProvider = 'arvan';
            } elseif ($mappingType === 'direct') {
                $dnsProvider = 'external';
            }

            // Check if already exists in domains table
            $existing = DB::table('domains')->where('domain', $domainName)->first();
            if (!$existing) {
                DB::table('domains')->insert([
                    'domain'          => $domainName,
                    'status'          => 'connected',
                    'service_id'      => $mapping->service_id,
                    'parked_on_id'    => null,
                    'dns_provider'    => $dnsProvider,
                    'arvan_zone'      => $arvanDomain,
                    'arvan_record_id' => $mapping->dns_record_id ?? null,
                    'ssl_status'      => 'none',
                    'created_at'      => $mapping->created_at ?? now(),
                    'updated_at'      => $mapping->updated_at ?? now(),
                ]);
            } else {
                // Update service_id if not set
                DB::table('domains')->where('id', $existing->id)->update([
                    'service_id'      => $mapping->service_id,
                    'status'          => 'connected',
                    'arvan_zone'      => $arvanDomain ?? $existing->arvan_zone,
                    'arvan_record_id' => ($mapping->dns_record_id ?? null) ?? $existing->arvan_record_id,
                ]);
            }
        }

        // 2. Second pass: Migrate all parked domain mappings
        $parkedMappings = DB::table('domain_mappings')
            ->whereNotNull('parent_mapping_id')
            ->get();

        foreach ($parkedMappings as $mapping) {
            $domainName = trim($mapping->source_domain);
            if (empty($domainName)) {
                continue;
            }

            // Find parent domain name from domain_mappings
            $parentMapping = DB::table('domain_mappings')->where('id', $mapping->parent_mapping_id)->first();
            $parkedOnId = null;

            if ($parentMapping) {
                $parentDomainRecord = DB::table('domains')->where('domain', trim($parentMapping->source_domain))->first();
                $parkedOnId = $parentDomainRecord?->id;
            }

            $dnsProvider = 'external';
            if (($mapping->mapping_type ?? null) === 'arvan' || !empty($mapping->arvan_domain)) {
                $dnsProvider = 'arvan';
            }

            $existing = DB::table('domains')->where('domain', $domainName)->first();
            if (!$existing) {
                DB::table('domains')->insert([
                    'domain'          => $domainName,
                    'status'          => 'parked_on',
                    'service_id'      => $mapping->service_id,
                    'parked_on_id'    => $parkedOnId,
                    'dns_provider'    => $dnsProvider,
                    'arvan_zone'      => $mapping->arvan_domain ?? null,
                    'arvan_record_id' => $mapping->dns_record_id ?? null,
                    'ssl_status'      => 'none',
                    'created_at'      => $mapping->created_at ?? now(),
                    'updated_at'      => $mapping->updated_at ?? now(),
                ]);
            } else {
                DB::table('domains')->where('id', $existing->id)->update([
                    'status'       => 'parked_on',
                    'parked_on_id' => $parkedOnId,
                    'service_id'   => $mapping->service_id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to prevent accidental data loss
    }
};
