@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('title', 'داشبورد - نمای کلی سرور')

@section('content')
<div x-data="{ copiedIp: false }" class="space-y-6">

    <!-- Hero / Server Identity & Quick Action Hub -->
    <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 backdrop-blur-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            
            <!-- Left: Identity & Live Ping -->
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">نمای کلی سرور</h1>
                    
                    <!-- Live Pulse Status Badge -->
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/60">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <span>سیستم پایدار و آنلاین</span>
                    </div>
                </div>

                <!-- Server Badges Strip -->
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <!-- Hostname -->
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700/70 font-mono">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        <span>{{ $stats['hostname'] ?? gethostname() }}</span>
                    </span>

                    <!-- IP Address with 1-click Copy -->
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $stats['server_ip'] ?? '127.0.0.1' }}'); copiedIp = true; setTimeout(() => copiedIp = false, 2000)"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700/70 hover:bg-gray-200 dark:hover:bg-gray-600 transition font-mono group"
                            title="کلیک برای کپی IP">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span x-text="copiedIp ? 'کپی شد!' : '{{ $stats['server_ip'] ?? '127.0.0.1' }}'" :class="copiedIp ? 'text-emerald-600 font-bold' : ''"></span>
                    </button>

                    <!-- OS & Kernel -->
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700/70">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6V3m0 18v-3m6-6h3m-3 6h3M9 6h6M9 18h6"></path></svg>
                        <span>{{ $stats['os_name'] ?? php_uname('s') }} {{ $stats['kernel_version'] ?? php_uname('r') }}</span>
                    </span>
                </div>
            </div>

            <!-- Right: Quick Actions Toolbar -->
            <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0">
                <!-- Manual Live Refresh Button -->
                <button type="button"
                        onclick="window.refreshServerStats && window.refreshServerStats()"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm"
                        title="بروزرسانی دستی آمار">
                    <svg id="manual_refresh_btn" class="w-4 h-4 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>بروزرسانی</span>
                </button>

                <!-- System Logs Quick Link -->
                <a href="{{ route('admin.logs.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>لاگ‌ها</span>
                </a>

                <!-- Security Center Quick Link -->
                <a href="{{ route('security.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.276a11.952 11.952 0 01-1.292-2.849A4.962 4.962 0 0012 3c-1.373 0-2.684.28-3.868.811a11.952 11.952 0 01-1.292 2.849M5.618 4.276a11.952 11.952 0 00-1.292 2.849A4.962 4.962 0 013 12c0 1.373.28 2.684.811 3.868a11.952 11.952 0 002.849 1.292M4.276 18.382a11.952 11.952 0 012.849 1.292A4.962 4.962 0 0012 21c1.373 0 2.684-.28 3.868-.811a11.952 11.952 0 011.292-2.849M18.382 19.724a11.952 11.952 0 00-2.849-1.292A4.962 4.962 0 0121 12c0-1.373-.28-2.684-.811-3.868a11.952 11.952 0 00-1.292-2.849"></path></svg>
                    <span>امنیت</span>
                </a>

                <!-- Disk Cleanup Quick Link -->
                <a href="{{ route('disk-cleanup.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700/80 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>پاک‌سازی دیسک</span>
                </a>

                <!-- Clear All Cache Form -->
                <form method="POST" action="{{ route('admin.clear-cache') }}" onsubmit="return confirm('آیا از پاک کردن تمامی کش‌های سرور مطمئن هستید؟')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>پاک کردن کش‌ها</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Vital KPI Cards (Smart Gauges) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        
        <!-- 1. CPU Usage Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">مصرف پردازنده (CPU)</p>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="cpu_usage_value" class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ $stats['cpu_usage'] }}</span>
                        <span class="text-lg font-bold text-gray-500 dark:text-gray-400">%</span>
                    </div>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6V3m0 18v-3m6-6h3m-3 6h3M9 6h6M9 18h6"></path></svg>
                </div>
            </div>

            <!-- Dynamic Gauge Bar -->
            <div class="mt-4 bg-gray-100 dark:bg-gray-700/80 rounded-full h-2.5 overflow-hidden">
                <div id="cpu_usage_bar"
                     class="h-2.5 rounded-full transition-all duration-500 {{ $stats['cpu_usage'] >= 85 ? 'bg-rose-500' : ($stats['cpu_usage'] >= 75 ? 'bg-amber-500' : 'bg-blue-500') }}"
                     style="width: {{ $stats['cpu_usage'] }}%"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 font-mono">
                <span>میانگین بار:</span>
                <span id="load_avg_value" class="font-bold text-gray-700 dark:text-gray-300">{{ implode(' / ', $stats['load_avg'] ?? [0,0,0]) }}</span>
            </div>
        </div>

        <!-- 2. Memory Usage Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">حافظه رم (RAM)</p>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="memory_usage_value" class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ $stats['memory_usage'] }}</span>
                        <span class="text-lg font-bold text-gray-500 dark:text-gray-400">%</span>
                    </div>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16M4 12h16"></path></svg>
                </div>
            </div>

            <!-- Dynamic Gauge Bar -->
            <div class="mt-4 bg-gray-100 dark:bg-gray-700/80 rounded-full h-2.5 overflow-hidden">
                <div id="memory_usage_bar"
                     class="h-2.5 rounded-full transition-all duration-500 {{ $stats['memory_usage'] >= 85 ? 'bg-rose-500' : ($stats['memory_usage'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                     style="width: {{ $stats['memory_usage'] }}%"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>مصرف شده:</span>
                <span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">
                    <span id="memory_used_gb">{{ $stats['memory_used_gb'] ?? 0 }}</span> / <span id="memory_total_gb">{{ $stats['memory_total_gb'] ?? 0 }}</span> GB
                </span>
            </div>
        </div>

        <!-- 3. Disk Usage Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">فضای دیسک (Disk)</p>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="disk_usage_value" class="text-3xl font-black text-gray-900 dark:text-white font-mono">{{ $stats['disk_usage'] }}</span>
                        <span class="text-lg font-bold text-gray-500 dark:text-gray-400">%</span>
                    </div>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
            </div>

            <!-- Dynamic Gauge Bar -->
            <div class="mt-4 bg-gray-100 dark:bg-gray-700/80 rounded-full h-2.5 overflow-hidden">
                <div id="disk_usage_bar"
                     class="h-2.5 rounded-full transition-all duration-500 {{ $stats['disk_usage'] >= 85 ? 'bg-rose-500' : ($stats['disk_usage'] >= 75 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                     style="width: {{ $stats['disk_usage'] }}%"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">
                    <span id="disk_used_gb">{{ $stats['disk_used_gb'] ?? 0 }}</span> / <span id="disk_total_gb">{{ $stats['disk_total_gb'] ?? 0 }}</span> GB
                </span>
                <a href="{{ route('disk-cleanup.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">مدیریت ←</a>
            </div>
        </div>

        <!-- 4. System Uptime Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">زمان فعال بودن (Uptime)</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mt-1 leading-tight" id="uptime_value">{{ $stats['uptime'] }}</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>آخرین راه‌اندازی:</span>
                <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ $stats['last_reboot'] ?? 'نامعلوم' }}</span>
            </div>
        </div>
    </div>

    <!-- Resource Trends Interactive Chart -->
    <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">روند مصرف منابع سرور</h3>
            </div>
            <span class="text-xs text-gray-400 font-mono">هر ۳۰ ثانیه</span>
        </div>
        <div class="h-64 sm:h-72">
            <canvas id="resourceChart"
                    data-metrics="{{ json_encode($stats['metrics_history']) }}"
                    data-update-url="{{ url('/dashboard') }}?json=1">
            </canvas>
        </div>
    </div>

    <!-- Core Services Hub (سرویس‌های اصلی سرور) -->
    <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">وضعیت سرویس‌های اصلی سرور</h3>
            </div>
            <a href="{{ route('services.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold">مدیریت کل سرویس‌ها ←</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @php
                $serviceLogLinks = [
                    'nginx' => route('admin.logs.index', ['key' => 'nginx_error']),
                    'php-fpm' => route('admin.logs.index', ['key' => 'php82_fpm']),
                ];
            @endphp

            @foreach($stats['service_status'] as $name => $service)
                @php
                    $state = $service['state'] ?? 'unknown';
                    $isActive = ($state === 'active');
                    $isFailed = ($state === 'failed');

                    $badgeStyle = match($state) {
                        'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
                        'failed'   => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
                        default    => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                    };

                    $dotColor = match($state) {
                        'active' => 'bg-emerald-500',
                        'failed' => 'bg-rose-500',
                        default => 'bg-gray-400',
                    };

                    $stateText = match($state) {
                        'active'   => 'فعال',
                        'failed'   => 'ناموفق',
                        'inactive' => 'غیرفعال',
                        default    => 'نامعلوم',
                    };
                @endphp
                <div class="flex flex-col justify-between p-4 rounded-xl bg-gray-50/70 dark:bg-gray-700/40 border border-gray-200/60 dark:border-gray-700/70 hover:border-gray-300 dark:hover:border-gray-600 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ Str::headline($name) }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                <span>{{ $stateText }}</span>
                            </span>
                        </div>
                        <p class="text-xs font-mono text-gray-400 dark:text-gray-400 truncate" title="{{ $service['unit'] ?? '' }}">{{ $service['unit'] ?? $name }}</p>
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-200/50 dark:border-gray-700/50 flex items-center justify-between text-xs">
                        @if(isset($serviceLogLinks[$name]))
                            <a href="{{ $serviceLogLinks[$name] }}" class="text-blue-600 dark:text-blue-400 hover:underline">مشاهده لاگ</a>
                        @else
                            <span class="text-gray-400">سرویس سیستم</span>
                        @endif
                        <span class="text-gray-400 font-mono">Systemd</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Top Processes & SSH Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Top Processes Table -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">فرآیندهای برتر سیستم</h3>
                </div>
                <span class="text-xs text-gray-400 font-mono">مرتب‌شده بر اساس CPU</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200/70 dark:divide-gray-700/60">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">PID</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">کاربر</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">CPU%</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Mem%</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">دستور</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40 font-mono text-xs">
                        @forelse($stats['top_processes'] as $proc)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-bold">{{ $proc['pid'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $proc['user'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold {{ $proc['cpu'] > 50 ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400' }}">
                                        {{ number_format($proc['cpu'], 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ number_format($proc['mem'], 1) }}%</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 truncate max-w-xs" title="{{ $proc['command'] }}">{{ $proc['command'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400">فرآیندی یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SSH Login Activity -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">فعالیت ورود SSH</h3>
                </div>
                <a href="{{ route('security.index') }}" class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold">مرکز امنیت ←</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200/70 dark:divide-gray-700/60">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">زمان</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">کاربر</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">IP</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40 font-mono text-xs">
                        @forelse($stats['login_history'] as $login)
                            @php
                                $isAccepted = ($login['result'] === 'accepted');
                            @endphp
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $login['timestamp'] }}</td>
                                <td class="px-4 py-3 font-bold text-gray-800 dark:text-gray-200">{{ $login['user'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $login['ip'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border {{ $isAccepted ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isAccepted ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        <span>{{ $isAccepted ? 'پذیرفته شد' : 'رد شد' }}</span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-6 text-gray-400">لاگ ورودی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Health, Queues & Backup Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Server Health Score Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-3">شاخص سلامت سرور</h3>
                @php
                    $health = $stats['health_status'] ?? 'healthy';
                    $healthColor = match($health) {
                        'healthy'  => 'text-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800',
                        'degraded' => 'text-amber-500 bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800',
                        default    => 'text-rose-500 bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800',
                    };
                    $healthTitle = match($health) {
                        'healthy'  => 'سیستم در وضعیت پایدار',
                        'degraded' => 'کاهش کارایی یا بار بالا',
                        default    => 'وضعیت بحرانی سرور',
                    };
                @endphp
                <div class="p-4 rounded-xl border {{ $healthColor }} text-center my-4">
                    <p class="text-2xl font-black capitalize">{{ $healthTitle }}</p>
                    <p class="text-xs opacity-80 mt-1">سرویس‌ها، منابع پردازنده، حافظه و دیسک مانیتور می‌شوند</p>
                </div>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1.5 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                <div class="flex justify-between">
                    <span>سرویس‌های فعال:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $stats['active_services'] ?? 0 }} سرویس</span>
                </div>
                <div class="flex justify-between">
                    <span>هشدارهای جاری:</span>
                    <span class="font-bold {{ count($stats['alerts'] ?? []) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ count($stats['alerts'] ?? []) }} مورد
                    </span>
                </div>
            </div>
        </div>

        <!-- Queues & Background Jobs Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-4">صف پردازش وظایف (Queues)</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200/60 dark:border-gray-700/60 text-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold block mb-1">در انتظار اجرا</span>
                        <span class="text-3xl font-black text-gray-800 dark:text-gray-100 font-mono">{{ $stats['queue'] ?? 0 }}</span>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200/60 dark:border-gray-700/60 text-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold block mb-1">ناموفق (Failed)</span>
                        <span class="text-3xl font-black {{ ($stats['failed_jobs'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} font-mono">
                            {{ $stats['failed_jobs'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                <span>تایم آپدیت:</span>
                <span class="font-mono">{{ $stats['last_updated_at'] ?? now()->format('H:i:s') }}</span>
            </div>
        </div>

        <!-- Backup Status Card -->
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">وضعیت پشتیبان‌گیری</h3>
                    <a href="{{ route('backup_tasks.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">وظایف بکاپ ←</a>
                </div>

                @php $backup = $stats['backup_status'] ?? []; @endphp
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200/60 dark:border-gray-700/60 my-2">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200">سیستم بکاپ خودکار</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $backup['message'] ?? 'تنظیم شده' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400">تنظیمات پشتیبان‌گیری</span>
                <a href="{{ route('backup_tasks.index') }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">مشاهده جزییات</a>
            </div>
        </div>

    </div>

</div>
@endsection
