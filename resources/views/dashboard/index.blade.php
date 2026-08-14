@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('title', 'داشبورد')

@section('content')
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">نمای کلی سرور</h1>
            <form method="POST" action="{{ route('admin.clear-cache') }}" onsubmit="return confirm('آیا از پاک کردن تمامی کش‌ها مطمئن هستید؟')">
                @csrf
                <button type="submit" class="mt-2 sm:mt-0 w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    پاک کردن تمامی کش‌ها
                </button>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <!-- CPU Usage -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">مصرف CPU</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><span id="cpu_usage_value">{{ $stats['cpu_usage'] }}</span>%</p>
                    </div>
                    <div class="p-3 bg-blue-100 text-blue-500 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M12 6V3m0 18v-3m6-6h3m-3 6h3M9 6h6M9 18h6"></path></svg>
                    </div>
                </div>
                <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div id="cpu_usage_bar" class="bg-blue-500 h-2.5 rounded-full" style="width: {{ $stats['cpu_usage'] }}%"></div>
                </div>
            </div>

            <!-- Memory Usage -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">مصرف حافظه</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><span id="memory_usage_value">{{ $stats['memory_usage'] }}</span>%</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <span id="memory_used_gb">{{ $stats['memory_used_gb'] ?? 0 }}</span> گیگابایت / <span id="memory_total_gb">{{ $stats['memory_total_gb'] ?? 0 }}</span> گیگابایت
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 text-green-500 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16M4 12h16"></path></svg>
                    </div>
                </div>
                <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div id="memory_usage_bar" class="bg-green-500 h-2.5 rounded-full" style="width: {{ $stats['memory_usage'] }}%"></div>
                </div>
            </div>

            <!-- Disk Usage -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">مصرف دیسک</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><span id="disk_usage_value">{{ $stats['disk_usage'] }}</span>%</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <span id="disk_used_gb">{{ $stats['disk_used_gb'] ?? 0 }}</span> گیگابایت / <span id="disk_total_gb">{{ $stats['disk_total_gb'] ?? 0 }}</span> گیگابایت
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 text-indigo-500 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    </div>
                </div>
                <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div id="disk_usage_bar" class="bg-indigo-500 h-2.5 rounded-full" style="width: {{ $stats['disk_usage'] }}%"></div>
                </div>
            </div>

            <!-- Uptime -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">زمان فعال بودن</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200" id="uptime_value">{{ $stats['uptime'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 text-yellow-500 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">آخرین راه‌اندازی: {{ $stats['last_reboot'] ?? 'نامعلوم' }}</p>
            </div>
        </div>

        <!-- Resource Trends Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">روند مصرف منابع</h3>
            <div class="h-64">
                <canvas id="resourceChart"
                        data-metrics="{{ json_encode($stats['metrics_history']) }}"
                        data-update-url="{{ url('/dashboard') }}?json=1">
                </canvas>
            </div>
        </div>

        <!-- System Info & Services -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- System Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">اطلاعات سیستم</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">سیستم عامل</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ php_uname('s') }} {{ php_uname('r') }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">نام هاست</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ $stats['hostname'] ?? 'نامعلوم' }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">نسخه کرنل</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ $stats['kernel_version'] ?? 'نامعلوم' }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">میانگین بار (1/5/15)</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ implode(' / ', $stats['load_avg'] ?? [0,0,0]) }}</span>
                    </li>
                </ul>
            </div>

            <!-- Service Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">وضعیت سرویس‌ها</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($stats['service_status'] as $name => $service)
                        @php
                            $state = $service['state'] ?? 'unknown';
                            $badgeColor = match($state) {
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100',
                                'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
                                'failed' => 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100',
                                default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100',
                            };
                            $stateText = match($state) {
                                'active' => 'فعال',
                                'inactive' => 'غیرفعال',
                                'failed' => 'ناموفق',
                                default => 'نامعلوم',
                            };
                        @endphp
                        <li class="px-5 py-3 flex justify-between items-center text-sm">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ Str::headline($name) }}</span>
                            <span class="px-2 py-1 text-xs font-bold uppercase rounded-full {{ $badgeColor }}">{{ $stateText }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Health Summary & Jobs & Uptime Info -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Health Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 flex flex-col items-center justify-center">
                @php
                    $healthColorClass = match($stats['health_status']) {
                        'healthy' => 'text-green-500',
                        'degraded' => 'text-yellow-500',
                        'critical' => 'text-red-500',
                        default => 'text-gray-500',
                    };
                    $healthIcon = match($stats['health_status']) {
                        'healthy' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.276a11.952 11.952 0 01-1.292-2.849A4.962 4.962 0 0012 3c-1.373 0-2.684.28-3.868.811a11.952 11.952 0 01-1.292 2.849M5.618 4.276a11.952 11.952 0 00-1.292 2.849A4.962 4.962 0 013 12c0 1.373.28 2.684.811 3.868a11.952 11.952 0 002.849 1.292M4.276 18.382a11.952 11.952 0 012.849 1.292A4.962 4.962 0 0012 21c1.373 0 2.684-.28 3.868-.811a11.952 11.952 0 011.292-2.849M18.382 19.724a11.952 11.952 0 00-2.849-1.292A4.962 4.962 0 0121 12c0-1.373-.28-2.684-.811-3.868a11.952 11.952 0 00-1.292-2.849"></path></svg>',
                        'degraded' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                        'critical' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        default => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9.247a1 1 0 01.753-1.653H18a1 1 0 01.753 1.653l-7.5 6a1 1 0 01-1.506 0l-7.5-6z"></path></svg>',
                    };
                    $healthStatusText = match($stats['health_status']) {
                        'healthy' => 'سالم',
                        'degraded' => 'کاهش عملکرد',
                        'critical' => 'بحرانی',
                        default => 'نامعلوم',
                    };
                @endphp
                {!! $healthIcon !!}
                <h4 class="mt-3 text-2xl font-bold {{ $healthColorClass }} capitalize">{{ $healthStatusText }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">وضعیت کلی سلامت سرور</p>
            </div>

            <!-- Jobs & Uptime Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">اطلاعات وظایف و زمان فعال بودن</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">وظایف در انتظار</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ $stats['queue'] ?? 0 }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">وظایف ناموفق</span>
                        <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 dark:bg-red-700 dark:text-red-100 rounded-full">{{ $stats['failed_jobs'] ?? 0 }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">زمان فعال بودن</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full" id="uptime_summary">{{ $stats['uptime'] }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">آخرین راه‌اندازی</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ $stats['last_reboot'] ?? 'نامعلوم' }}</span>
                    </li>
                    <li class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-300">کرنل</span>
                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-full">{{ $stats['kernel_version'] ?? 'نامعلوم' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Top Processes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">فرآیندهای برتر</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کاربر</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPU%</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mem%</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">دستور</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($stats['top_processes'] as $proc)
                            <tr class="text-sm text-gray-700 dark:text-gray-300">
                                <td class="px-6 py-4 whitespace-nowrap">{{ $proc['pid'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $proc['user'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($proc['cpu'], 1) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($proc['mem'], 1) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap truncate max-w-xs" title="{{ $proc['command'] }}">{{ $proc['command'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500 dark:text-gray-400">داده‌ای برای فرآیندها موجود نیست.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SSH Login Activity & Alerts + Backup -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- SSH Login Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">فعالیت ورود SSH</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">زمان</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">کاربر</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نتیجه</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($stats['login_history'] as $login)
                                <tr class="text-sm text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $login['timestamp'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $login['user'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $login['ip'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $loginBadgeColor = $login['result'] === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100';
                                            $loginResultText = $login['result'] === 'accepted' ? 'پذیرفته شد' : 'رد شد';
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-bold uppercase rounded-full {{ $loginBadgeColor }}">{{ $loginResultText }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-500 dark:text-gray-400">داده‌ای برای ورود SSH موجود نیست.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alerts Timeline & Backup Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700">جدول زمانی هشدارها</h3>
                <div class="p-5 divide-y divide-gray-200 dark:divide-gray-700">
                    @if(!empty($stats['alerts']))
                        @foreach($stats['alerts'] as $alert)
                            @php
                                $alertColorClass = match($alert['type']) {
                                    'success' => 'bg-green-50 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                    'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                    'danger' => 'bg-red-50 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                    default => 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                };
                                $alertIcon = match($alert['type']) {
                                    'success' => '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                                    'warning' => '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                                    'danger' => '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                                    default => '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                                };
                            @endphp
                            <div class="flex items-start p-3 rounded-md {{ $alertColorClass }} mb-2 last:mb-0">
                                {!! $alertIcon !!}
                                <div>
                                    <p class="text-sm font-medium">{{ $alert['message'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $alert['time'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">اخیراً هشداری وجود ندارد.</p>
                    @endif
                </div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 p-5 border-b border-gray-200 dark:border-gray-700 mt-4">وضعیت پشتیبان‌گیری</h3>
                <div class="p-5">
                    @php $backup = $stats['backup_status']; @endphp
                    @if($backup['status'] === 'not_configured')
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $backup['message'] }}</p>
                    @else
                        <p class="text-sm text-green-600 dark:text-green-400">آخرین پشتیبان‌گیری: {{ $backup['message'] ?? 'موفق' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    setInterval(() => {
        fetch('{{ url('/dashboard') }}?json=1')
            .then(res => res.json())
            .then(data => {
                if(data.cpu_usage !== undefined) {
                    const elValue = document.getElementById('cpu_usage_value');
                    const elBar = document.getElementById('cpu_usage_bar');
                    if(elValue) elValue.innerText = data.cpu_usage;
                    if(elBar) elBar.style.width = data.cpu_usage + '%';
                }
                if(data.memory_usage !== undefined) {
                    const elValue = document.getElementById('memory_usage_value');
                    const elUsed = document.getElementById('memory_used_gb');
                    const elTotal = document.getElementById('memory_total_gb');
                    const elBar = document.getElementById('memory_usage_bar');
                    if(elValue) elValue.innerText = data.memory_usage;
                    if(elUsed) elUsed.innerText = data.memory_used_gb || 0;
                    if(elTotal) elTotal.innerText = data.memory_total_gb || 0;
                    if(elBar) elBar.style.width = data.memory_usage + '%';
                }
                if(data.disk_usage !== undefined) {
                    const elValue = document.getElementById('disk_usage_value');
                    const elUsed = document.getElementById('disk_used_gb');
                    const elTotal = document.getElementById('disk_total_gb');
                    const elBar = document.getElementById('disk_usage_bar');
                    if(elValue) elValue.innerText = data.disk_usage;
                    if(elUsed) elUsed.innerText = data.disk_used_gb || 0;
                    if(elTotal) elTotal.innerText = data.disk_total_gb || 0;
                    if(elBar) elBar.style.width = data.disk_usage + '%';
                }
                if(data.uptime !== undefined) {
                    const uptimeEl = document.getElementById('uptime_value');
                    if (uptimeEl) uptimeEl.innerText = data.uptime;
                    const uptimeSummaryEl = document.getElementById('uptime_summary');
                    if (uptimeSummaryEl) uptimeSummaryEl.innerText = data.uptime;
                }
            })
            .catch(err => console.error('Error fetching live stats:', err));
    }, 30000);
</script>
@endpush
