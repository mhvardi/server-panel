<?php

namespace App\Jobs;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunServiceBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout after 30 minutes (prevents killing long-running backups)
     */
    public int $timeout = 1800;

    /**
     * Number of attempts
     */
    public int $tries = 1;

    public function __construct(
        public Service $service,
        public string $type = 'all',
        public ?string $target = null
    ) {
        $this->onQueue('backups');
    }

    public function handle(): void
    {
        Log::info("Starting queued sequential backup for service: {$this->service->name} (Type: {$this->type}, Target: {$this->target})");

        $params = [
            'service_id' => $this->service->id,
            '--type' => $this->type,
        ];

        if ($this->target === 'ftp') {
            $params['--ftp-only'] = false;
        }

        try {
            $exitCode = Artisan::call('backup:run-service', $params);
            Log::info("Queued backup job finished for service {$this->service->name} with exit code: {$exitCode}");
        } catch (\Throwable $e) {
            Log::error("Queued backup job failed for service {$this->service->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
