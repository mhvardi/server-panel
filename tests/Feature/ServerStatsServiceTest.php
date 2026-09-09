<?php

namespace Tests\Feature;

use App\Services\ServerStatsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServerStatsServiceTest extends TestCase
{
    public function test_get_overview_returns_expected_structure(): void
    {
        $service = app(ServerStatsService::class);
        $stats = $service->getOverview('/');

        $this->assertIsArray($stats);
        $expectedKeys = [
            'cpu_usage',
            'memory_usage',
            'disk_usage',
            'uptime',
            'uptime_seconds',
            'health_status',
            'service_status',
            'active_services',
            'load_avg',
            'hostname',
            'kernel_version',
            'last_reboot',
            'alerts',
            'warnings',
            'top_processes',
            'queue',
            'failed_jobs',
            'metrics_history',
            'login_history',
            'backup_status',
            'last_updated_at',
            'memory_used_gb',
            'memory_total_gb',
            'disk_used_gb',
            'disk_total_gb',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing expected key: {$key}");
        }

        $this->assertGreaterThanOrEqual(0, $stats['cpu_usage']);
        $this->assertLessThanOrEqual(100, $stats['cpu_usage']);
        $this->assertGreaterThanOrEqual(0, $stats['memory_usage']);
        $this->assertLessThanOrEqual(100, $stats['memory_usage']);
        $this->assertGreaterThanOrEqual(0, $stats['disk_usage']);
        $this->assertLessThanOrEqual(100, $stats['disk_usage']);
    }

    public function test_consecutive_calls_are_fast_and_cached(): void
    {
        $service = app(ServerStatsService::class);

        // First call
        $service->getOverview('/');

        // Second call should hit cached items and take minimal time
        $start = microtime(true);
        $stats = $service->getOverview('/');
        $elapsed = microtime(true) - $start;

        // Must complete in well under 200ms (verifying absence of 200ms usleep)
        $this->assertLessThan(0.15, $elapsed, "Consecutive call took {$elapsed}s, expected < 0.15s");
        $this->assertIsArray($stats);
    }
}
