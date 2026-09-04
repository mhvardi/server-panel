<?php

namespace App\Http\Controllers;

use App\Models\FileQuarantine;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Services\FileScanner;
use App\Services\GeoIpService;
use App\Services\ServerAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SecurityController extends Controller
{
    public function __construct(
        protected GeoIpService $geoIp,
        protected FileScanner $scanner,
        protected ServerAuditService $audit
    ) {
    }

    /**
     * Main Security Center Dashboard
     */
    public function index()
    {
        $settings = [
            'iran_ip_restriction'    => SecuritySetting::isTrue('iran_ip_restriction', false),
            'max_login_attempts'     => SecuritySetting::get('max_login_attempts', 3),
            'lockout_minutes'        => SecuritySetting::get('lockout_minutes', 1440),
            'whitelisted_ips'        => SecuritySetting::get('whitelisted_ips', "94.183.100.3\n127.0.0.1"),
            'upload_file_scan'       => SecuritySetting::isTrue('upload_file_scan', true),
            'quarantine_infected'    => SecuritySetting::isTrue('quarantine_infected', true),
            'server_monitor_enabled' => SecuritySetting::isTrue('server_monitor_enabled', true),
            'last_scan_at'           => SecuritySetting::get('last_scan_at', 'انجام نشده'),
        ];

        $serverAudit = $this->audit->getAuditSummary();
        $recentEvents = SecurityEvent::latest()->paginate(15, ['*'], 'events_page')->withQueryString();
        $recentLogins = LoginAttempt::latest()->paginate(15, ['*'], 'logins_page')->withQueryString();
        $quarantinedFiles = FileQuarantine::latest()->take(20)->get();

        $stats = [
            'total_blocked_logins' => LoginAttempt::where('success', false)->count(),
            'total_quarantined'    => FileQuarantine::count(),
            'total_critical_events'=> SecurityEvent::where('severity', 'critical')->count(),
            'client_ip'            => request()->ip(),
            'client_country'       => $this->geoIp->getCountryCode(request()->ip()),
            'security_score'       => $this->calculateSecurityScore($settings, $serverAudit),
        ];

        return view('security.index', compact('settings', 'serverAudit', 'recentEvents', 'recentLogins', 'quarantinedFiles', 'stats'));
    }

    /**
     * Update security settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'iran_ip_restriction'    => 'nullable|boolean',
            'max_login_attempts'     => 'required|integer|min:1|max:20',
            'lockout_minutes'        => 'required|integer|min:1',
            'whitelisted_ips'        => 'nullable|string',
            'upload_file_scan'       => 'nullable|boolean',
            'quarantine_infected'    => 'nullable|boolean',
            'server_monitor_enabled' => 'nullable|boolean',
        ]);

        SecuritySetting::set('iran_ip_restriction', $request->boolean('iran_ip_restriction') ? 'true' : 'false');
        SecuritySetting::set('max_login_attempts', (int) $request->input('max_login_attempts', 3));
        SecuritySetting::set('lockout_minutes', (int) $request->input('lockout_minutes', 1440));
        SecuritySetting::set('whitelisted_ips', trim((string) $request->input('whitelisted_ips')));
        SecuritySetting::set('upload_file_scan', $request->boolean('upload_file_scan') ? 'true' : 'false');
        SecuritySetting::set('quarantine_infected', $request->boolean('quarantine_infected') ? 'true' : 'false');
        SecuritySetting::set('server_monitor_enabled', $request->boolean('server_monitor_enabled') ? 'true' : 'false');

        SecurityEvent::log('settings', 'info', 'تنظیمات مرکز امنیت توسط ادمین به‌روزرسانی شد.');

        return back()->with('success', 'تنظیمات امنیتی با موفقیت ذخیره شدند.');
    }

    /**
     * Run manual file & webshell scan
     */
    public function runFileScan(Request $request)
    {
        $scanTarget = $request->input('target', 'all');

        // Execute asynchronous background scan via CLI / background process to avoid HTTP timeouts
        $artisan = base_path('artisan');
        $cmd = "php {$artisan} security:scan-files --target=" . escapeshellarg($scanTarget) . " > /dev/null 2>&1 &";

        if (function_exists('shell_exec')) {
            shell_exec($cmd);
        } else {
            \App\Jobs\RunSecurityScanJob::dispatch($scanTarget);
        }

        SecuritySetting::set('last_scan_at', now()->toDateTimeString() . ' (در حال اسکن در پس‌زمینه...)');

        return back()->with('success', 'عملیات اسکن عمیق فایل‌ها در پس‌زمینه سرور آغاز گردید. این فرآیند بدون قطعی یا خطای تایم‌اوت انجام می‌شود و نتایج بلافاصله در رویدادها و قرنطینه ثبت خواهد شد.');
    }

    /**
     * Restore file from quarantine
     */
    public function restoreQuarantine(int $id)
    {
        $item = FileQuarantine::findOrFail($id);
        if (file_exists($item->quarantine_path)) {
            $dir = dirname($item->original_path);
            File::ensureDirectoryExists($dir);
            rename($item->quarantine_path, $item->original_path);
        }
        $item->delete();

        SecurityEvent::log('file_scan', 'warning', "فایل {$item->filename} از قرنطینه بازگردانی شد.");

        return back()->with('success', "فایل {$item->filename} با موفقیت به مسیر اصلی بازگردانده شد.");
    }

    /**
     * Delete file from quarantine permanently
     */
    public function deleteQuarantine(int $id)
    {
        $item = FileQuarantine::findOrFail($id);
        if (file_exists($item->quarantine_path)) {
            @unlink($item->quarantine_path);
        }
        $item->delete();

        return back()->with('success', 'فایل آلوده برای همیشه حذف گردید.');
    }

    /**
     * Clear all resolved security events
     */
    public function clearEvents()
    {
        SecurityEvent::truncate();
        return back()->with('success', 'تمام رویدادهای امنیتی پاک شدند.');
    }

    /**
     * Clear login attempts log
     */
    public function clearLoginAttempts()
    {
        LoginAttempt::truncate();
        return back()->with('success', 'تاریخچه لاگین‌ها با موفقیت بازنشانی شد.');
    }

    /**
     * Calculate security health score from 0 to 100
     */
    protected function calculateSecurityScore(array $settings, array $serverAudit): int
    {
        $score = 100;

        // 1. APP_DEBUG active (-20)
        if (config('app.debug') === true) {
            $score -= 20;
        }

        // 2. Iran IP restriction disabled (-15)
        if (!$settings['iran_ip_restriction']) {
            $score -= 15;
        }

        // 3. Dangerous file permissions (-15)
        if (!empty($serverAudit['permission_warnings'])) {
            $score -= 15;
        }

        // 4. Critical sensitive file issues (-20)
        if (!empty($serverAudit['sensitive_files'])) {
            $score -= 20;
        }

        // 5. Active quarantine items (-10)
        if (FileQuarantine::count() > 0) {
            $score -= 10;
        }

        // 6. Suspicious processes (-15)
        if (!empty($serverAudit['suspicious_processes'])) {
            $score -= 15;
        }

        return max(15, min(100, $score));
    }
}
