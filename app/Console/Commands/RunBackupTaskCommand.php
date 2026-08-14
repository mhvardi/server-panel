<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackupTask;
use App\Services\BackupTaskService;

/**
 * Command to run backup tasks from the CLI.
 *
 * You can run all tasks or a specific one by passing --task=<id>.
 * This command is useful for integration with cron when queue workers are not available.
 */
class RunBackupTaskCommand extends Command
{
    protected $signature = 'backup:run {--task= : ID of the backup task to run}';

    protected $description = 'Execute backup tasks';

    public function handle(BackupTaskService $service): int
    {
        $taskId = $this->option('task');
        if ($taskId) {
            $task = BackupTask::find($taskId);
            if (!$task) {
                $this->error('Backup task not found');
                return 1;
            }
            $result = $service->run($task);
            $this->info("Task {$task->name}: {$result['status']}");
            return $result['status'] === 'success' ? 0 : 1;
        }

        // run all tasks
        $tasks = BackupTask::all();
        foreach ($tasks as $task) {
            $result = $service->run($task);
            $this->info("Task {$task->name}: {$result['status']}");
        }

        return 0;
    }
}