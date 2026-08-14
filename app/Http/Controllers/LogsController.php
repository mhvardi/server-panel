<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogsController extends Controller
{
    // فقط این فایل ها مجاز هستند
    private function logMap(): array
    {
        return [
            // Laravel
            'laravel_vardi'  => ['label' => 'Laravel - vardi',  'path' => '/var/www/service/vardi.vardicrm.ir/storage/logs/laravel.log'],
            'laravel_zabihi' => ['label' => 'Laravel - zabihi', 'path' => '/var/www/service/zabihi.vardicrm.ir/storage/logs/laravel.log'],

            // Nginx
            'nginx_error' => ['label' => 'Nginx - error.log', 'path' => '/var/log/nginx/error.log'],
            'nginx_access_vardi'  => ['label' => 'Nginx - vardi access',  'path' => '/var/log/nginx/vardi.vardicrm.ir-access.log'],
            'nginx_error_vardi'   => ['label' => 'Nginx - vardi error',   'path' => '/var/log/nginx/vardi.vardicrm.ir-error.log'],
            'nginx_access_zabihi' => ['label' => 'Nginx - zabihi access', 'path' => '/var/log/nginx/zabihi.vardicrm.ir-access.log'],
            'nginx_error_zabihi'  => ['label' => 'Nginx - zabihi error',  'path' => '/var/log/nginx/zabihi.vardicrm.ir-error.log'],

            // PHP-FPM (اگر مسیرت فرق دارد، مطابق سیستم خودت اصلاح کن)
            'php82_fpm' => ['label' => 'PHP 8.2 FPM', 'path' => '/var/log/php8.2-fpm.log'],
        ];
    }

    public function index(Request $request)
    {
        $logs = $this->logMap();

        // پیش فرض اولین لاگ
        $activeKey = $request->query('key', array_key_first($logs));

        return view('admin.logs.index', compact('logs', 'activeKey'));
    }

    public function view(string $key)
    {
        $logs = $this->logMap();

        abort_unless(isset($logs[$key]), 404);

        $path = $logs[$key]['path'];

        if (!File::exists($path)) {
            return response()->json([
                'ok' => false,
                'message' => 'Log file not found: ' . $path,
                'content' => '',
            ], 200);
        }

        // 400 خط آخر
        $content = $this->tailFile($path, 400);

        return response()->json([
            'ok' => true,
            'message' => '',
            'content' => $content,
        ]);
    }

    public function clear(string $key)
    {
        $logs = $this->logMap();

        abort_unless(isset($logs[$key]), 404);

        $path = $logs[$key]['path'];

        abort_unless(File::exists($path), 404);

        // پاک کردن لاگ (truncate)
        // اگر سطح دسترسی مشکل داشت، باید فایل لاگ owner/perm درست باشد یا با sudo script انجام شود
        File::put($path, '');

        return redirect()->route('admin.logs.index', ['key' => $key])->with('status', 'لاگ با موفقیت پاک شد');
    }

    private function tailFile(string $path, int $lines = 200): string
    {
        // روش ساده و سریع: tail
        $cmd = 'tail -n ' . (int)$lines . ' ' . escapeshellarg($path) . ' 2>/dev/null';
        $out = shell_exec($cmd);
        return (string) $out;
    }
}
