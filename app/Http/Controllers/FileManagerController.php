<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class FileManagerController extends Controller
{
    /**
     * مسیر پایه‌ای که همه عملیات به آن محدود می‌شود.
     */
    private string $baseDir = '/var/www';

    // ──────────────────────────────────────────────
    //  صفحه اصلی
    // ──────────────────────────────────────────────

    public function index()
    {
        return view('file-manager.index');
    }

    // ──────────────────────────────────────────────
    //  مرور پوشه
    // ──────────────────────────────────────────────

    public function browse(Request $request)
    {
        $path = $request->input('path', '/');

        try {
            $safePath = $this->safePath($path);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        if (!is_dir($safePath)) {
            return response()->json(['ok' => false, 'message' => 'مسیر یافت نشد یا پوشه نیست.'], 404);
        }

        $items = [];

        $entries = @scandir($safePath);
        if ($entries === false) {
            return response()->json(['ok' => false, 'message' => 'دسترسی به پوشه امکان‌پذیر نیست.'], 403);
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $safePath . '/' . $entry;
            $isDir    = is_dir($fullPath);
            $stat     = @stat($fullPath);

            $items[] = [
                'name'     => $entry,
                'type'     => $isDir ? 'dir' : 'file',
                'size'     => $isDir ? null : ($stat ? $stat['size'] : 0),
                'modified' => $stat ? date('Y-m-d H:i:s', $stat['mtime']) : null,
                'perms'    => $stat ? substr(sprintf('%o', $stat['mode']), -4) : null,
                'ext'      => $isDir ? null : strtolower(pathinfo($entry, PATHINFO_EXTENSION)),
            ];
        }

        // مرتب‌سازی: پوشه‌ها اول، سپس فایل‌ها، هر دو گروه بر اساس نام
        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        // تبدیل مسیر داخلی به مسیر نسبی برای نمایش
        $relativePath = $this->toRelative($safePath);

        return response()->json([
            'ok'       => true,
            'path'     => $relativePath,
            'realPath' => $safePath,
            'items'    => $items,
        ]);
    }

    // ──────────────────────────────────────────────
    //  خواندن محتوای فایل
    // ──────────────────────────────────────────────

    public function read(Request $request)
    {
        $path = $request->input('path', '');

        try {
            $safePath = $this->safePath($path);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        if (!is_file($safePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یافت نشد.'], 404);
        }

        // محدودیت حجم برای خواندن در مرورگر (۵ مگابایت)
        $maxSize = 5 * 1024 * 1024;
        $size    = filesize($safePath);

        if ($size > $maxSize) {
            return response()->json([
                'ok'      => false,
                'message' => 'فایل بزرگ‌تر از ۵ مگابایت است. لطفاً آن را دانلود کنید.',
                'size'    => $size,
            ]);
        }

        $content = @file_get_contents($safePath);

        if ($content === false) {
            return response()->json(['ok' => false, 'message' => 'خطا در خواندن فایل.'], 500);
        }

        // تشخیص باینری
        if (!mb_check_encoding($content, 'UTF-8') && str_contains($content, "\x00")) {
            return response()->json([
                'ok'      => false,
                'message' => 'فایل باینری است و قابل نمایش نیست.',
                'binary'  => true,
            ]);
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));

        return response()->json([
            'ok'      => true,
            'content' => $content,
            'ext'     => $ext,
            'size'    => $size,
        ]);
    }

    // ──────────────────────────────────────────────
    //  ذخیره فایل
    // ──────────────────────────────────────────────

    public function save(Request $request)
    {
        $request->validate([
            'path'    => 'required|string',
            'content' => 'required|string',
        ]);

        try {
            $safePath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        if (!is_file($safePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یافت نشد.'], 404);
        }

        $result = @file_put_contents($safePath, $request->input('content'));

        if ($result === false) {
            return response()->json(['ok' => false, 'message' => 'خطا در ذخیره فایل. دسترسی کافی نیست.'], 500);
        }

        Log::info('FileManager: file saved', ['path' => $safePath, 'user' => auth()->id()]);

        return response()->json(['ok' => true, 'message' => 'فایل با موفقیت ذخیره شد.']);
    }

    // ──────────────────────────────────────────────
    //  حذف فایل یا پوشه
    // ──────────────────────────────────────────────

    public function delete(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        try {
            $safePath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        // جلوگیری از حذف ریشه /var/www
        if ($safePath === rtrim($this->baseDir, '/')) {
            return response()->json(['ok' => false, 'message' => 'امکان حذف پوشه اصلی /var/www وجود ندارد.'], 403);
        }

        if (!file_exists($safePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یا پوشه یافت نشد.'], 404);
        }

        $result = false;

        // ابتدا روش مستقیم PHP
        if (is_dir($safePath)) {
            $result = $this->deleteDirectory($safePath);
        } else {
            $result = @unlink($safePath);
        }

        // در صورت عدم دسترسی در سطح PHP، تلاش با sudo rm -rf
        if (!$result && file_exists($safePath)) {
            $escaped = escapeshellarg($safePath);
            $proc = $this->runSudoCommand("sudo rm -rf {$escaped}");
            $result = $proc && $proc->successful() && !file_exists($safePath);
        }

        if (!$result) {
            return response()->json([
                'ok' => false, 
                'message' => 'خطا در حذف. دسترسی سیستم کافی نیست. لطفاً دسترسی‌های پوشه را با chown/chmod تنظیم کنید یا دسترسی sudo بدون پسورد به کاربر بدهید.'
            ], 500);
        }

        Log::info('FileManager: item deleted', ['path' => $safePath, 'user' => auth()->id()]);

        return response()->json(['ok' => true, 'message' => 'با موفقیت حذف شد.']);
    }

    // ──────────────────────────────────────────────
    //  آپلود فایل
    // ──────────────────────────────────────────────

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // max 100MB
            'path' => 'required|string',
        ]);

        try {
            $safePath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        if (!is_dir($safePath)) {
            return response()->json(['ok' => false, 'message' => 'مسیر مقصد پوشه نیست.'], 400);
        }

        $uploadedFile = $request->file('file');
        $filename     = $uploadedFile->getClientOriginalName();

        // پاک‌سازی نام فایل
        $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);

        $destination = $safePath . '/' . $filename;

        if (move_uploaded_file($uploadedFile->getRealPath(), $destination)) {
            Log::info('FileManager: file uploaded', ['path' => $destination, 'user' => auth()->id()]);
            return response()->json(['ok' => true, 'message' => 'فایل آپلود شد.', 'filename' => $filename]);
        }

        return response()->json(['ok' => false, 'message' => 'خطا در آپلود فایل.'], 500);
    }

    // ──────────────────────────────────────────────
    //  دانلود فایل
    // ──────────────────────────────────────────────

    public function download(Request $request)
    {
        $path = $request->input('path', '');

        try {
            $safePath = $this->safePath($path);
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }

        if (!is_file($safePath)) {
            abort(404, 'فایل یافت نشد.');
        }

        return response()->download($safePath);
    }

    // ──────────────────────────────────────────────
    //  ایجاد پوشه جدید
    // ──────────────────────────────────────────────

    public function mkdir(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $name = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $request->input('name'));

        try {
            $parentPath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        $newDir = $parentPath . '/' . $name;

        if (file_exists($newDir)) {
            return response()->json(['ok' => false, 'message' => 'این نام از قبل وجود دارد.'], 409);
        }

        if (!@mkdir($newDir, 0755, true)) {
            return response()->json(['ok' => false, 'message' => 'خطا در ساخت پوشه.'], 500);
        }

        return response()->json(['ok' => true, 'message' => 'پوشه ساخته شد.', 'name' => $name]);
    }

    // ──────────────────────────────────────────────
    //  ایجاد فایل جدید
    // ──────────────────────────────────────────────

    public function touch(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $name = basename($request->input('name'));

        try {
            $parentPath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        $newFile = $parentPath . '/' . $name;

        if (file_exists($newFile)) {
            return response()->json(['ok' => false, 'message' => 'این نام از قبل وجود دارد.'], 409);
        }

        if (file_put_contents($newFile, '') === false) {
            return response()->json(['ok' => false, 'message' => 'خطا در ساخت فایل.'], 500);
        }

        return response()->json(['ok' => true, 'message' => 'فایل ساخته شد.', 'name' => $name]);
    }

    // ──────────────────────────────────────────────
    //  تغییر نام
    // ──────────────────────────────────────────────

    public function rename(Request $request)
    {
        $request->validate([
            'path'    => 'required|string',
            'newName' => 'required|string|max:255',
        ]);

        $newName = basename($request->input('newName'));

        try {
            $safePath = $this->safePath($request->input('path'));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        if (!file_exists($safePath)) {
            return response()->json(['ok' => false, 'message' => 'فایل یا پوشه یافت نشد.'], 404);
        }

        $newPath = dirname($safePath) . '/' . $newName;

        // مطمئن می‌شویم newPath هم داخل base است
        try {
            $this->safePath($this->toRelative($newPath));
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'نام جدید غیرمجاز است.'], 403);
        }

        if (file_exists($newPath)) {
            return response()->json(['ok' => false, 'message' => 'این نام از قبل وجود دارد.'], 409);
        }

        if (!@rename($safePath, $newPath)) {
            return response()->json(['ok' => false, 'message' => 'خطا در تغییر نام.'], 500);
        }

        return response()->json(['ok' => true, 'message' => 'تغییر نام داده شد.', 'newName' => $newName]);
    }

    // ──────────────────────────────────────────────
    //  آنالیز مصرف فضا
    // ──────────────────────────────────────────────

    public function diskUsage(Request $request)
    {
        $path = $request->input('path', '/');

        try {
            $safePath = $this->safePath($path);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 403);
        }

        // اطلاعات کل دیسک
        $dfOutput  = shell_exec('df -B1 ' . escapeshellarg($safePath) . ' 2>/dev/null | tail -1');
        $diskTotal = 0;
        $diskUsed  = 0;
        $diskFree  = 0;

        if ($dfOutput) {
            $parts = preg_split('/\s+/', trim($dfOutput));
            if (count($parts) >= 4) {
                $diskTotal = (int) $parts[1];
                $diskUsed  = (int) $parts[2];
                $diskFree  = (int) $parts[3];
            }
        }

        // حجم هر پوشه/فایل در مسیر
        $items       = [];
        $entries     = @scandir($safePath);

        if ($entries) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $fullPath = $safePath . '/' . $entry;
                $isDir    = is_dir($fullPath);

                if ($isDir) {
                    // du برای پوشه‌ها
                    $duOut = shell_exec('du -sb ' . escapeshellarg($fullPath) . ' 2>/dev/null | cut -f1');
                    $size  = (int) trim((string) $duOut);
                } else {
                    $size = (int) @filesize($fullPath);
                }

                $items[] = [
                    'name'  => $entry,
                    'type'  => $isDir ? 'dir' : 'file',
                    'size'  => $size,
                    'human' => $this->humanSize($size),
                ];
            }
        }

        // مرتب‌سازی از بزرگ به کوچک
        usort($items, fn($a, $b) => $b['size'] <=> $a['size']);

        return response()->json([
            'ok'         => true,
            'path'       => $this->toRelative($safePath),
            'disk_total' => $diskTotal,
            'disk_used'  => $diskUsed,
            'disk_free'  => $diskFree,
            'disk_total_human' => $this->humanSize($diskTotal),
            'disk_used_human'  => $this->humanSize($diskUsed),
            'disk_free_human'  => $this->humanSize($diskFree),
            'items'      => $items,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Helper: امنیت مسیر (Path Traversal Protection)
    // ──────────────────────────────────────────────

    /**
     * مسیر نسبی (از /var/www) یا مطلق را به مسیر تمیز تبدیل کرده و
     * اطمینان حاصل می‌کند که خارج از $baseDir نیست.
     */
    private function safePath(string $path): string
    {
        $base = rtrim($this->baseDir, '/');
        $trimmed = trim($path);

        // اگر خالی یا اسلش ریشه فایل منیجر بود
        if ($trimmed === '' || $trimmed === '/' || $trimmed === $base || $trimmed === $base . '/') {
            return $base;
        }

        // اگر مسیر به صورت مطلق ارسال شده اما با base شروع نشده
        // و مسیر شروعش با اسلش است، بررسی می‌کنیم آیا زیرشاخه نسبی است یا مسیر مطلق سیستمی
        if (str_starts_with($trimmed, '/')) {
            if (str_starts_with($trimmed, $base . '/') || $trimmed === $base) {
                $target = $trimmed;
            } else {
                // اگر مثلاً /service یا /html فرستاده شده که زیرشاخه /var/www است
                $target = $base . '/' . ltrim($trimmed, '/');
            }
        } else {
            $target = $base . '/' . $trimmed;
        }

        // نرمال‌سازی مسیر (حل .. و . و // بدون وابستگی به فایل‌سیستم محلی)
        $normalized = $this->normalizePath($target);

        // اطمینان از اینکه مسیر در محدوده /var/www است
        if ($normalized !== $base && !str_starts_with($normalized, $base . '/')) {
            throw new \Exception('دسترسی به خارج از مسیر مجاز ممنوع است.');
        }

        // اگر فایل یا پوشه وجود دارد، بررسی symlink به خارج از محدوده
        if (file_exists($normalized)) {
            $real = realpath($normalized);
            $realBase = realpath($base) ?: $base;
            if ($real && $realBase && $real !== $realBase && !str_starts_with($real, $realBase . '/')) {
                throw new \Exception('دسترسی به خارج از مسیر مجاز ممنوع است.');
            }
        }

        return $normalized;
    }

    /**
     * حل کردن .. و . و اسلش‌های اضافی به شکل قطعی
     */
    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $isAbsolute = str_starts_with($path, '/');
        $parts = array_filter(explode('/', $path), fn($part) => $part !== '' && $part !== '.');
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }

        $result = ($isAbsolute ? '/' : '') . implode('/', $resolved);
        return $result === '' ? '/' : $result;
    }

    /**
     * مسیر مطلق را به مسیر نسبی (از /var/www) تبدیل می‌کند.
     */
    private function toRelative(string $path): string
    {
        $base = rtrim($this->baseDir, '/');
        if (str_starts_with($path, $base)) {
            $rel = substr($path, strlen($base));
            return $rel === '' ? '/' : $rel;
        }
        return $path;
    }

    // ──────────────────────────────────────────────
    //  Helper: حذف پوشه به‌صورت بازگشتی
    // ──────────────────────────────────────────────

    private function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $entries = @scandir($path);
        if (!$entries) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . '/' . $entry;
            if (is_dir($full)) {
                $this->deleteDirectory($full);
            } else {
                @unlink($full);
            }
        }

        return @rmdir($path);
    }

    // ──────────────────────────────────────────────
    //  Helper: نمایش انسانی حجم
    // ──────────────────────────────────────────────

    private function humanSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }

    // ──────────────────────────────────────────────
    //  Helper: اجرای دستورات سیستمی با دسترسی Sudo
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
}
