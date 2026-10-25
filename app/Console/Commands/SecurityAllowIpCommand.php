<?php

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SecurityAllowIpCommand extends Command
{
    protected $signature = 'security:allow-ip
                            {ip? : IP address or CIDR to whitelist}
                            {--disable-restriction : Disable Iran IP restriction completely}
                            {--enable-restriction : Enable Iran IP restriction}
                            {--show : Display current whitelist and security settings}';

    protected $description = 'Whitelist an IP address or manage Iran IP restriction settings';

    public function handle(): int
    {
        if ($this->option('disable-restriction')) {
            SecuritySetting::set('iran_ip_restriction', 'false');
            Cache::forget('security_setting_iran_ip_restriction');
            $this->info('محدودیت دسترسی بر اساس آی‌پی ایران با موفقیت غیرفعال شد.');
            return 0;
        }

        if ($this->option('enable-restriction')) {
            SecuritySetting::set('iran_ip_restriction', 'true');
            Cache::forget('security_setting_iran_ip_restriction');
            $this->info('محدودیت دسترسی بر اساس آی‌پی ایران با موفقیت فعال شد.');
            return 0;
        }

        $ip = $this->argument('ip');

        if (empty($ip) || $this->option('show')) {
            $isRestricted = SecuritySetting::isTrue('iran_ip_restriction', false);
            $whitelist = SecuritySetting::get('whitelisted_ips', '');
            $this->info('وضعیت محدودیت آی‌پی ایران: ' . ($isRestricted ? 'فعال (محدود به ایران)' : 'غیرفعال (آزاد)'));
            $this->line('لیست آی‌پی‌های سفید (Whitelist):');
            $this->line($whitelist ?: 'هیچ آی‌پی ثبت نشده است.');
            return 0;
        }

        // Add IP to whitelist
        $current = SecuritySetting::get('whitelisted_ips', '');
        $list = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $current)));

        if (!in_array($ip, $list, true)) {
            $list[] = $ip;
            $newVal = implode("\n", $list);
            SecuritySetting::set('whitelisted_ips', $newVal);
            Cache::forget('security_setting_whitelisted_ips');
            Cache::forget("geoip_{$ip}");
            $this->info("آی‌پی {$ip} با موفقیت به لیست مجاز (Whitelist) اضافه شد.");
        } else {
            $this->comment("آی‌پی {$ip} از قبل در لیست مجاز قرار دارد.");
        }

        return 0;
    }
}
