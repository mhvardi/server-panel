<?php

namespace App\Jobs;

use App\Models\BackupTask;
use App\Services\BackupTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * RunBackupTaskJob executes a backup task asynchronously via queue.
 *
 * It uses BackupTaskService to perform the backup and logs the outcome.
 */
class RunBackupTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected BackupTask $task)
    {
    }

    public function handle(BackupTaskService $service): void
    {
        $service->run($this->task);
    }
}