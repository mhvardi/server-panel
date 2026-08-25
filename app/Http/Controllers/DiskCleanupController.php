<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DiskCleanupController extends Controller
{
    /**
     * صفحه اصلی پاکسازی دیسک
     */
    public function index()
    {
        return view('disk-cleanup.index');
    }

    /**
     * دریافت وضعیت لحظه‌ای دیسک (df -h /)
     */
    public function status()
    {
        $dfOutput = shell_exec('df -B1 / 2>/dev/null | tail -1');
        $dfHuman  = shell_exec('df -h / 2>/dev/null | tail -1');

        $totalBytes = 0;
        $usedBytes  = 0;
        $freeBytes  = 0;
        $percent    = 0;
        $mount      = '/';

        if ($dfOutput) {
            $parts = preg_split('/\s+/', trim($dfOutput));
            if (count($parts) >= 6) {
                $totalBytes = (int) ($parts[1] ?? 0);
                $usedBytes  = (int) ($parts[2] ?? 0);
                $freeBytes  = (int) ($parts[3] ?? 0);
                $percent    = (int) str_replace('%', '', $parts[4] ?? '0');
                $mount      = $parts[5] ?? '/';
            }
        }

        $humanParts = $dfHuman ? preg_split('/\s+/', trim($dfHuman)) : [];
        $totalHuman = $humanParts[1] ?? $this->humanSize($totalBytes);
        $usedHuman  = $humanParts[2] ?? $this->humanSize($usedBytes);
        $freeHuman  = $humanParts[3] ?? $this->humanSize($freeBytes);

        return response()->json([
            'ok' => true,
            'disk' => [
                'total_bytes' => $totalBytes,
                'used_bytes'  => $usedBytes,
                'free_bytes'  => $freeBytes,
                'total_human' => $totalHuman,
                'used_human'  => $usedHuman,
                'free_human'  => $freeHuman,
                'percent'     => $percent,
                'mount'       => $mount,
            ]
        ]);
    }

    /**
     * اجرای عملیات پاکسازی خودکار دیسک
     */
    public function cleanup()
    {
        $logs = [];
        $startTime = microtime(true);

        // ۱. پاک کردن لاگ‌های سیستمی قدیمی (نگه داشتن ۳ روز اخیر)
        $cmd1 = 'journalctl --vacuum-time=3d 2>&1';
        $out1 = $this->runSudoCommand($cmd1);
        $logs[] = [
            'title'   => 'پاکسازی لاگ‌های سیستمی Systemd (journalctl)',
            'command' => 'journalctl --vacuum-time=3d',
            'output'  => $out1 ? trim($out1->output()) : 'اجرا شد',
            'success' => $out1 ? $out1->successful() : false,
        ];

        // ۲. پاک کردن لاگ‌های آرشیو شده و فشرده (.gz و .1) در /var/log
        $cmd2 = 'find /var/log -type f \( -name "*.gz" -o -name "*.1" -o -name "*.old" \) -delete 2>&1';
        $out2 = $this->runSudoCommand($cmd2);
        $logs[] = [
            'title'   => 'حذف لاگ‌های آرشیو و فشرده شده در /var/log',
            'command' => 'find /var/log -type f -name "*.gz" / "*.1" -delete',
            'output'  => $out2 ? trim($out2->output()) ?: 'لاگ‌های آرشیو با موفقیت حذف شدند.' : 'انجام شد',
            'success' => $out2 ? $out2->successful() : false,
        ];

        // ۳. پاکسازی کش پکیج‌های لینوکس (apt-get clean)
        $cmd3 = 'apt-get clean 2>&1';
        $out3 = $this->runSudoCommand($cmd3);
        $logs[] = [
            'title'   => 'پاکسازی کش پکیج‌های APT (apt-get clean)',
            'command' => 'apt-get clean',
            'output'  => $out3 ? trim($out3->output()) ?: 'کش پکیج‌ها پاک شد.' : 'انجام شد',
            'success' => $out3 ? $out3->successful() : false,
        ];

        // ۴. حذف پکیج‌ها و کرنل‌های بلااستفاده (apt-get autoremove)
        $cmd4 = 'DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>&1';
        $out4 = $this->runSudoCommand($cmd4);
        $logs[] = [
            'title'   => 'حذف بسته‌های وابسته بلااستفاده (apt-get autoremove)',
            'command' => 'apt-get autoremove -y',
            'output'  => $out4 ? trim($out4->output()) : 'انجام شد',
            'success' => $out4 ? $out4->successful() : false,
        ];

        $duration = round(microtime(true) - $startTime, 2);

        Log::info('DiskCleanup: Auto cleanup executed', ['user' => auth()->id(), 'duration' => $duration]);

        return response()->json([
            'ok'       => true,
            'message'  => 'عملیات پاکسازی با موفقیت انجام شد.',
            'duration' => $duration,
            'logs'     => $logs,
        ]);
    }

    /**
     * استخراج لیست پوشه‌ها/فایل‌های حجیم (Deep Analyze)
     */
    public function analyze(Request $request)
    {
        $targetPath = $request->input('path', '/');
        
        // اطمینان از امنیت مسیر (عدم خروج یا تزریق شل)
        $safePath = '/' . ltrim(preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $targetPath), '/');
        $safePath = rtrim(preg_replace('#/{2,}#', '/', $safePath), '/');
        if ($safePath === '') {
            $safePath = '/';
        }

        if (!file_exists($safePath)) {
            return response()->json(['ok' => false, 'message' => 'مسیر مورد نظر یافت نشد.'], 404);
        }

        // دستور برای استخراج ۱۰ تا از سنگین‌ترین آیتم‌های پوشه با حجم دقیق
        // du -sb <path>/* 2>/dev/null
        $entries = @scandir($safePath);
        $items = [];

        if ($entries) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                // مسیر کامل آیتم
                $fullPath = ($safePath === '/' ? '' : $safePath) . '/' . $entry;

                // دور زدن مسیرهای سیستمی مجازی سنگین در روت /
                if ($safePath === '/' && in_array($entry, ['proc', 'sys', 'dev', 'run'])) {
                    continue;
                }

                $isDir = is_dir($fullPath);

                if ($isDir) {
                    $escaped = escapeshellarg($fullPath);
                    $duOut   = shell_exec("du -sb {$escaped} 2>/dev/null | cut -f1");
                    $size    = (int) trim((string) $duOut);
                } else {
                    $size = (int) @filesize($fullPath);
                }

                $items[] = [
                    'name'      => $entry,
                    'full_path' => $fullPath,
                    'type'      => $isDir ? 'dir' : 'file',
                    'size'      => $size,
                    'human'     => $this->humanSize($size),
                ];
            }
        }

        // مرتب‌سازی بر اساس حجم (نزولی)
        usort($items, fn($a, $b) => ($b['size'] ?? 0) <=> ($a['size'] ?? 0));

        // ۱۰ مورد اول
        $topItems = array_slice($items, 0, 10);

        return response()->json([
            'ok'        => true,
            'path'      => $safePath,
            'items'     => $items,
            'top_items' => $topItems,
        ]);
    }

    /**
     * حذف فایل یا پوشه انتخاب شده از بخش آنالیز
     */
    public function deleteItem(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $path = $request->input('path');
        $safePath = '/' . ltrim(preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $path), '/');

        // جلوگیری از حذف ریشه و دایرکتوری‌های حیاتی سیستم
        $protected = ['/', '/bin', '/boot', '/dev', '/etc', '/lib', '/lib64', '/proc', '/root', '/run', '/sbin', '/sys', '/usr', '/var'];
        if (in_array($safePath, $protected)) {
            return response()->json(['ok' => false, 'message' => 'امکان حذف پوشه‌های حیاتی سیستم وجود ندارد.'], 403);
        }

        if (!file_exists($safePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یا پوشه یافت نشد.'], 404);
        }

        $escaped = escapeshellarg($safePath);
        $proc = $this->runSudoCommand("sudo rm -rf {$escaped}");

        if ($proc && $proc->successful() && !file_exists($safePath)) {
            Log::info('DiskCleanup: Item deleted', ['path' => $safePath, 'user' => auth()->id()]);
            return response()->json(['ok' => true, 'message' => 'مورد با موفقیت حذف شد.']);
        }

        return response()->json(['ok' => false, 'message' => 'خطا در حذف. دسترسی کافی نیست.'], 500);
    }

    // ──────────────────────────────────────────────
    //  Helper Methods
    // ──────────────────────────────────────────────

    private function runSudoCommand(string $command)
    {
        $currentUser = trim((string) shell_exec('whoami 2>/dev/null'));

        if ($currentUser === 'root') {
            $command = preg_replace('/^sudo\s+/', '', $command);
            return Process::run($command);
        }

        $sudoTest = Process::run('sudo -n true 2>&1');
        if ($sudoTest->successful()) {
            return Process::run($command);
        }

        $sudoPassword = env('SUDO_PASSWORD');
        if ($sudoPassword) {
            $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $command);
            $escapedPassword = escapeshellarg($sudoPassword);
            $commandWithPassword = "printf %s {$escapedPassword} | sudo -S " . $commandWithoutSudo;
            return Process::run($commandWithPassword);
        }

        $commandWithoutSudo = preg_replace('/^sudo\s+/', '', $command);
        return Process::run($commandWithoutSudo);
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }
}
