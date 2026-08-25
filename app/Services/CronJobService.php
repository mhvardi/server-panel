<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * CronJobService
 *
 * Manages a dedicated cron file under /etc/cron.d (Ubuntu-friendly).
 * We do NOT touch the global crontab or other apps' cron entries.
 *
 * Requirements:
 * - The web/PHP user must be able to write to the cron file path.
 *   Recommended: passwordless sudo for a tiny allowlist (tee, chmod, chown, rm, touch).
 */
class CronJobService
{
    private string $cronFile;
    private string $runAs;

    public function __construct()
    {
        $configured = env('CRON_PANEL_FILE', '/etc/cron.d/server-panel');
        $configured = is_string($configured) ? trim($configured) : '';
        if (env('BACKUP_MOCK_ENABLED', false) || str_starts_with($configured, storage_path())) {
            // Local dev / testing mode
            if ($configured === '' || !str_starts_with($configured, storage_path())) {
                $configured = storage_path('app/cron/server-panel');
            }
            \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($configured));
        } else {
            if ($configured === '' || !str_starts_with($configured, '/etc/cron.d/')) {
                $configured = '/etc/cron.d/server-panel';
            }
            if (str_contains($configured, '..')) {
                $configured = '/etc/cron.d/server-panel';
            }
        }
        $this->cronFile = $configured;
        $this->runAs = env('CRON_RUN_AS', 'www-data');
    }

    /**
     * List jobs from the panel-managed cron file.
     *
     * @return array<int, array{ id:string, name:string, schedule:string, command:string, enabled:bool, run_as:string, raw:string }>
     */
    public function listJobs(): array
    {
        $content = $this->readCronFile();
        $lines = preg_split("/\r\n|\r|\n/", $content);
        $jobs = [];

        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '' || str_starts_with($lineTrim, '#')) {
                continue;
            }

            // We store id marker at the end: # panelcron:<id>
            if (!preg_match('/#\s*panelcron:([a-z0-9\-]+)/i', $lineTrim, $m)) {
                continue;
            }

            $id = strtolower($m[1]);
            $enabled = true;
            $raw = $lineTrim;

            // /etc/cron.d line: <min> <hour> <dom> <mon> <dow> <user> <command...>
            $parts = preg_split('/\s+/', $lineTrim, 7);
            if (count($parts) < 7) {
                continue;
            }

            [$min, $hour, $dom, $mon, $dow, $user, $cmd] = $parts;
            $schedule = trim("$min $hour $dom $mon $dow");
            $command = trim($cmd);
            // name is stored in a meta comment line above (optional), fallback to command headline
            $name = $this->findNameForId($content, $id) ?? $this->guessNameFromCommand($command);

            $jobs[] = [
                'id' => $id,
                'name' => $name,
                'schedule' => $schedule,
                'command' => $this->stripIdMarker($command),
                'enabled' => $enabled,
                'run_as' => $user,
                'raw' => $raw,
            ];
        }

        // Disabled jobs are stored as commented schedule lines with the same id marker
        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '' || !str_starts_with($lineTrim, '#')) {
                continue;
            }

            if (!preg_match('/#\s*panelcron:([a-z0-9\-]+)/i', $lineTrim, $m)) {
                continue;
            }
            $id = strtolower($m[1]);
            if (collect($jobs)->firstWhere('id', $id)) {
                continue;
            }
            // strip leading '#'
            $lineBody = ltrim(substr($lineTrim, 1));
            $parts = preg_split('/\s+/', $lineBody, 7);
            if (count($parts) < 7) {
                continue;
            }
            [$min, $hour, $dom, $mon, $dow, $user, $cmd] = $parts;
            $schedule = trim("$min $hour $dom $mon $dow");
            $command = trim($cmd);
            $name = $this->findNameForId($content, $id) ?? $this->guessNameFromCommand($command);

            $jobs[] = [
                'id' => $id,
                'name' => $name,
                'schedule' => $schedule,
                'command' => $this->stripIdMarker($command),
                'enabled' => false,
                'run_as' => $user,
                'raw' => $lineTrim,
            ];
        }

        usort($jobs, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $jobs;
    }

    /**
     * Create a new cron job.
     */
    public function create(string $name, string $schedule, string $command, ?string $runAs = null, bool $enabled = true): string
    {
        $id = (string) Str::uuid();
        $runAs = $runAs ?: $this->runAs;

        $schedule = trim($schedule);
        $command = trim($command);
        $name = trim($name);

        $this->validateSchedule($schedule);
        $this->validateRunAs($runAs);
        $this->validateCommand($command);

        $meta = $this->buildMetaLine($id, $name);
        $line = $this->buildCronLine($id, $schedule, $runAs, $command);
        if (!$enabled) {
            $line = '# ' . $line;
        }

        $content = $this->readCronFile();
        $content = rtrim($content) . "\n";
        if (!str_contains($content, '# server-panel managed file')) {
            $content = $this->defaultHeader() . "\n" . $content;
        }

        $content .= $meta . "\n" . $line . "\n";
        $this->writeCronFile($content);

        return $id;
    }

    /**
     * Update an existing job by id.
     */
    public function update(string $id, string $name, string $schedule, string $command, ?string $runAs = null, ?bool $enabled = null): void
    {
        $id = strtolower(trim($id));
        $runAs = $runAs ?: $this->runAs;
        $schedule = trim($schedule);
        $command = trim($command);
        $name = trim($name);

        $this->validateSchedule($schedule);
        $this->validateRunAs($runAs);
        $this->validateCommand($command);

        $content = $this->readCronFile();
        $lines = preg_split("/\r\n|\r|\n/", $content);

        $metaLine = $this->buildMetaLine($id, $name);
        $cronLine = $this->buildCronLine($id, $schedule, $runAs, $command);

        $newLines = [];
        $replacedMeta = false;
        $replacedJob = false;
        foreach ($lines as $line) {
            $lineTrim = trim($line);

            // replace meta line if exists
            if (preg_match('/^#\s*id=' . preg_quote($id, '/') . '\b/i', $lineTrim)) {
                $newLines[] = $metaLine;
                $replacedMeta = true;
                continue;
            }

            // replace job line (enabled or disabled)
            if (preg_match('/panelcron:' . preg_quote($id, '/') . '\b/i', $lineTrim)) {
                // decide enabled state
                $isCommented = str_starts_with($lineTrim, '#');
                $finalEnabled = $enabled === null ? !$isCommented : (bool)$enabled;
                $newLines[] = $finalEnabled ? $cronLine : '# ' . $cronLine;
                $replacedJob = true;
                continue;
            }

            $newLines[] = $line;
        }

        // if not found, append
        if (!$replacedMeta) {
            $newLines[] = $metaLine;
        }
        if (!$replacedJob) {
            $newLines[] = ($enabled === false) ? ('# ' . $cronLine) : $cronLine;
        }

        $this->writeCronFile(implode("\n", $newLines) . "\n");
    }

    public function delete(string $id): void
    {
        $id = strtolower(trim($id));
        $content = $this->readCronFile();
        $lines = preg_split("/\r\n|\r|\n/", $content);

        $newLines = [];
        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if (preg_match('/^#\s*id=' . preg_quote($id, '/') . '\b/i', $lineTrim)) {
                continue;
            }
            if (preg_match('/panelcron:' . preg_quote($id, '/') . '\b/i', $lineTrim)) {
                continue;
            }
            $newLines[] = $line;
        }

        $this->writeCronFile(implode("\n", $newLines) . "\n");
    }

    public function toggle(string $id): void
    {
        $id = strtolower(trim($id));
        $content = $this->readCronFile();
        $lines = preg_split("/\r\n|\r|\n/", $content);
        $newLines = [];

        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if (!preg_match('/panelcron:' . preg_quote($id, '/') . '\b/i', $lineTrim)) {
                $newLines[] = $line;
                continue;
            }

            if (str_starts_with($lineTrim, '#')) {
                // enable
                $newLines[] = ltrim(substr($lineTrim, 1));
            } else {
                // disable
                $newLines[] = '# ' . $lineTrim;
            }
        }

        $this->writeCronFile(implode("\n", $newLines) . "\n");
    }

    public function getJob(string $id): ?array
    {
        $id = strtolower(trim($id));
        foreach ($this->listJobs() as $job) {
            if ($job['id'] === $id) return $job;
        }
        return null;
    }

    public function findJobByName(string $name): ?array
    {
        $name = trim($name);
        foreach ($this->listJobs() as $job) {
            if ($job['name'] === $name) return $job;
        }
        return null;
    }

    public function getConfig(): array
    {
        return [
            'cron_file' => $this->cronFile,
            'default_run_as' => $this->runAs,
            'can_write' => $this->canWrite(),
        ];
    }

    public function canWrite(): bool
    {
        // If exists and writable, ok.
        if (file_exists($this->cronFile) && is_writable($this->cronFile)) {
            return true;
        }

        // If not exists, check if parent is writable
        $dir = dirname($this->cronFile);
        if (!file_exists($this->cronFile) && is_dir($dir) && is_writable($dir)) {
            return true;
        }

        // Fallback: sudo -n true
        try {
            $res = Process::run('sudo -n true 2>&1');
            return $res->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function readCronFile(): string
    {
        if (!file_exists($this->cronFile)) {
            return $this->defaultHeader() . "\n";
        }
        $content = @file_get_contents($this->cronFile);
        return $content !== false ? $content : ($this->defaultHeader() . "\n");
    }

    private function writeCronFile(string $content): void
    {
        $content = $this->normalizeContent($content);

        // write direct if possible
        if ((file_exists($this->cronFile) && is_writable($this->cronFile)) || (!file_exists($this->cronFile) && is_writable(dirname($this->cronFile)))) {
            file_put_contents($this->cronFile, $content);
            return;
        }

        // sudo write
        $res = Process::input($content)->run(['sudo', 'tee', $this->cronFile]);
        if (!$res->successful()) {
            throw new \RuntimeException($this->sudoHint($res->errorOutput() ?: $res->output()));
        }

        // ensure mode
        $chmod = Process::run(['sudo', 'chmod', '0644', $this->cronFile]);
        if (!$chmod->successful()) {
            // not fatal
        }
    }

    private function normalizeContent(string $content): string
    {
        // Remove null bytes, ensure newline end.
        $content = str_replace("\0", '', $content);
        $content = preg_replace('/\r\n?/', "\n", $content);
        $content = trim($content) . "\n";
        if (!str_starts_with($content, '# server-panel managed file')) {
            $content = $this->defaultHeader() . "\n" . $content;
        }
        return $content;
    }

    private function defaultHeader(): string
    {
        return "# server-panel managed file\n# DO NOT EDIT MANUALLY unless you know what you're doing.";
    }

    private function buildMetaLine(string $id, string $name): string
    {
        $safeName = str_replace('"', "'", $name);
        return '# id=' . strtolower($id) . ' name="' . $safeName . '"';
    }

    private function buildCronLine(string $id, string $schedule, string $runAs, string $command): string
    {
        // store id marker at end
        return $schedule . ' ' . $runAs . ' ' . $command . ' # panelcron:' . strtolower($id);
    }

    private function findNameForId(string $content, string $id): ?string
    {
        $pattern = '/^#\s*id=' . preg_quote($id, '/') . '\s+name="([^"]*)"/mi';
        if (preg_match($pattern, $content, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function guessNameFromCommand(string $command): string
    {
        $cmd = $this->stripIdMarker($command);
        $cmd = preg_replace('/\s+/', ' ', $cmd);
        return Str::limit($cmd, 40);
    }

    private function stripIdMarker(string $command): string
    {
        return trim(preg_replace('/#\s*panelcron:[a-z0-9\-]+/i', '', $command));
    }

    private function validateSchedule(string $schedule): void
    {
        $parts = preg_split('/\s+/', trim($schedule));
        if (count($parts) !== 5) {
            throw new \InvalidArgumentException('Cron schedule must have 5 fields (min hour dom mon dow).');
        }
        // Lightweight validation: only allow common cron chars
        foreach ($parts as $p) {
            if (!preg_match('/^[\*\d,\-\/]+$/', $p)) {
                throw new \InvalidArgumentException('Invalid cron schedule field: ' . $p);
            }
        }
    }

    private function validateRunAs(string $runAs): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_\-]*$/i', $runAs)) {
            throw new \InvalidArgumentException('Invalid run-as user.');
        }
    }

    private function validateCommand(string $command): void
    {
        if ($command === '') {
            throw new \InvalidArgumentException('Command cannot be empty.');
        }
        // Block newlines to avoid injection into cron file.
        if (str_contains($command, "\n") || str_contains($command, "\r")) {
            throw new \InvalidArgumentException('Command must be a single line.');
        }
    }

    private function sudoHint(string $err): string
    {
        $msg = "Cannot write cron file ({$this->cronFile}).\n\n";
        $msg .= "Fix: allow passwordless sudo for the web user (usually www-data) for tee/chmod.\n\n";
        $msg .= "Example (sudo visudo):\n";
        $msg .= "www-data ALL=(ALL) NOPASSWD: /usr/bin/tee, /usr/bin/chmod\n\n";
        $msg .= "Error: " . trim($err);
        return $msg;
    }
}
