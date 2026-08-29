@extends('layouts.app')

@section('title', 'پشتیبان‌گیری هوشمند: ' . $service->name)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ 
    activeTab: 'settings',
    submitting: false,
    loadingAction: null,
    submitManual(target, type) {
        this.loadingAction = target + '_' + type;
        this.$refs['manual_form_' + target + '_' + type].submit();
    }
}">
    <!-- Breadcrumb & Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">داشبورد</a>
                <span>/</span>
                <a href="{{ route('backup_tasks.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">پشتیبان‌گیری</a>
                <span>/</span>
                <span class="text-gray-900 dark:text-white font-bold">{{ $service->name }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-black text-base shadow-md shadow-indigo-500/20">
                    {{ substr($service->name, 0, 2) }}
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>{{ $service->name }}</span>
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $service->type == 'main' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $service->type == 'main' ? 'سرویس اصلی' : 'ساب‌دامین' }}
                        </span>
                    </h1>
                    <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5" dir="ltr">{{ $service->domain }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($queueCount > 0)
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs font-bold text-amber-800 dark:text-amber-300">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>{{ $queueCount }} بکاپ در صف انتظار</span>
                </div>
            @endif
            <a href="{{ route('backup_tasks.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/60 transition duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                <span>بازگشت به لیست</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 rounded-2xl border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 rounded-2xl border border-red-200 dark:bg-red-950/40 dark:border-red-800/60 dark:text-red-300 flex items-center gap-2.5 shadow-sm">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Modern Tab Pills Navigation -->
    <div class="bg-gray-100/80 dark:bg-gray-800/80 p-1.5 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 inline-flex flex-wrap gap-1 w-full sm:w-auto">
        <button type="button" @click="activeTab = 'settings'" 
                :class="activeTab === 'settings' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="flex items-center gap-2 px-4 py-2 text-xs rounded-xl transition duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>تنظیمات و زمان‌بندی خودکار</span>
        </button>

        <button type="button" @click="activeTab = 'manual'" 
                :class="activeTab === 'manual' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="flex items-center gap-2 px-4 py-2 text-xs rounded-xl transition duration-150">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            <span>عملیات بکاپ دستی</span>
        </button>

        <button type="button" @click="activeTab = 'status'" 
                :class="activeTab === 'status' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="flex items-center gap-2 px-4 py-2 text-xs rounded-xl transition duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            <span>وضعیت و تاریخچه محلی</span>
        </button>

        <button type="button" @click="activeTab = 'logs'; fetchLog()" 
                :class="activeTab === 'logs' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="flex items-center gap-2 px-4 py-2 text-xs rounded-xl transition duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <span>لاگ و خروجی سیستم</span>
        </button>
    </div>

    <!-- 1. SETTINGS TAB -->
    <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <form action="{{ route('backup_tasks.save_settings', $service->id) }}" method="POST" x-data="{ 
            db_enabled: {{ old('db_enabled', $settings['db_enabled'] ?? true) ? 'true' : 'false' }},
            files_enabled: {{ old('files_enabled', $settings['files_enabled'] ?? true) ? 'true' : 'false' }},
            local_enabled: {{ old('local_enabled', $settings['local_enabled'] ?? true) ? 'true' : 'false' }},
            remote_enabled: {{ old('remote_enabled', $settings['remote_enabled'] ?? false) ? 'true' : 'false' }}, 
            db_cron_preset: '{{ old('db_cron_preset', in_array($settings['db_cron_expression'] ?? '', ['0 2 * * *','0 0 * * *','0 */12 * * *']) ? $settings['db_cron_expression'] : 'custom') }}',
            files_cron_preset: '{{ old('files_cron_preset', in_array($settings['files_cron_expression'] ?? '', ['0 2 * * 5','0 2 * * 0','0 3 1 * *']) ? $settings['files_cron_expression'] : 'custom') }}',
            fillCentralFtp() {
                this.remote_enabled = true;
                document.getElementById('remote_host').value = '80.249.115.114';
                document.getElementById('remote_user').value = 'mhvardi@backup.vardicrm.ir';
                document.getElementById('remote_password').value = 'pqDd2PZ1V8Pkq6r3';
                document.getElementById('remote_path').value = '/public_html';
            }
        }" class="space-y-6">
            @csrf

            <!-- Section 1: DATABASE BACKUP (DAILY) -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/60 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                            🗄️
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">پشتیبان‌گیری خودکار پایگاه‌داده (Database)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">زمان‌بندی روزانه مستقل و پاک‌سازی هوشمند فایل‌های SQL</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="db_enabled" value="0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="db_enabled" value="1" x-model="db_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="mr-3 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="db_enabled ? 'فعال' : 'غیرفعال'"></span>
                        </label>
                    </div>
                </div>

                <div x-show="db_enabled" x-transition class="space-y-4 pt-1">
                    <!-- DB Name Auto Detection -->
                    <div class="bg-gray-50 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">نام پایگاه‌داده شناسایی‌شده از فایل <code>.env</code>:</span>
                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 mr-2 bg-white dark:bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700" dir="ltr">
                                {{ $service->getDatabaseName() ?? 'یافت نشد' }}
                            </span>
                        </div>
                        <div>
                            @if($service->getDatabaseName())
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-xl border border-emerald-200/50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    دیتابیس متصل و آماده
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 px-2.5 py-1 rounded-xl border border-red-200/50">
                                    ⚠️ در فایل env یافت نشد
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- DB Schedule & Retention Controls -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="db_cron_preset" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">زمان‌بندی دیتابیس (Cron)</label>
                            <select x-model="db_cron_preset" name="db_cron_preset" id="db_cron_preset" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                                <option value="0 2 * * *">هر شب ساعت ۰۲:۰۰ بامداد (پیشنهادی)</option>
                                <option value="0 0 * * *">هر شب ساعت ۰۰:۰۰ بامداد</option>
                                <option value="0 */12 * * *">هر ۱۲ ساعت یک‌بار</option>
                                <option value="custom">تنظیم دستی (Custom Cron)</option>
                            </select>
                        </div>
                        <div x-show="db_cron_preset === 'custom'">
                            <label for="db_cron_custom" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">عبارت سفارشی Cron</label>
                            <input type="text" name="db_cron_custom" id="db_cron_custom" value="{{ old('db_cron_custom', $settings['db_cron_expression'] ?? '0 2 * * *') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono focus:ring-2 focus:ring-blue-500" dir="ltr">
                        </div>
                        <div>
                            <label for="db_local_retention_days" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">تعداد نگهداری دیتابیس در Local</label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" name="db_local_retention_days" id="db_local_retention_days" value="{{ old('db_local_retention_days', $settings['db_local_retention_days'] ?? 3) }}" min="1" class="w-full rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-center focus:ring-2 focus:ring-blue-500">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 text-xs font-medium">نسخه</span>
                            </div>
                        </div>
                        <div>
                            <label for="db_remote_retention_days" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">تعداد نگهداری دیتابیس در FTP</label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" name="db_remote_retention_days" id="db_remote_retention_days" value="{{ old('db_remote_retention_days', $settings['db_remote_retention_days'] ?? 3) }}" min="1" class="w-full rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-center focus:ring-2 focus:ring-blue-500">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 text-xs font-medium">نسخه</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: FILES BACKUP (WEEKLY) -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/60 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                            📁
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">پشتیبان‌گیری خودکار فایل‌های سورس پروژه (Files)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">زمان‌بندی هفتگی یا ماهانه (حذف خودکار <code>vendor</code> و <code>node_modules</code>)</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="files_enabled" value="0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="files_enabled" value="1" x-model="files_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                            <span class="mr-3 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="files_enabled ? 'فعال' : 'غیرفعال'"></span>
                        </label>
                    </div>
                </div>

                <div x-show="files_enabled" x-transition class="space-y-4 pt-1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="files_cron_preset" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">زمان‌بندی سورس فایل‌ها (Cron)</label>
                            <select x-model="files_cron_preset" name="files_cron_preset" id="files_cron_preset" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                                <option value="0 2 * * 5">هر جمعه ساعت ۰۲:۰۰ بامداد (هفتگی پیشنهادی)</option>
                                <option value="0 2 * * 0">هر یکشنبه ساعت ۰۲:۰۰ بامداد</option>
                                <option value="0 3 1 * *">اول هر ماه ساعت ۰۳:۰۰ بامداد (ماهانه)</option>
                                <option value="custom">تنظیم دستی (Custom Cron)</option>
                            </select>
                        </div>
                        <div x-show="files_cron_preset === 'custom'">
                            <label for="files_cron_custom" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">عبارت سفارشی Cron</label>
                            <input type="text" name="files_cron_custom" id="files_cron_custom" value="{{ old('files_cron_custom', $settings['files_cron_expression'] ?? '0 2 * * 5') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono focus:ring-2 focus:ring-emerald-500" dir="ltr">
                        </div>
                        <div>
                            <label for="files_local_retention_days" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">تعداد نگهداری فایل‌ها در Local</label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" name="files_local_retention_days" id="files_local_retention_days" value="{{ old('files_local_retention_days', $settings['files_local_retention_days'] ?? 14) }}" min="1" class="w-full rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-center focus:ring-2 focus:ring-emerald-500">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 text-xs font-medium">نسخه</span>
                            </div>
                        </div>
                        <div>
                            <label for="files_remote_retention_days" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">تعداد نگهداری فایل‌ها در FTP</label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" name="files_remote_retention_days" id="files_remote_retention_days" value="{{ old('files_remote_retention_days', $settings['files_remote_retention_days'] ?? 14) }}" min="1" class="w-full rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-center focus:ring-2 focus:ring-emerald-500">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 text-xs font-medium">نسخه</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: STORAGE DESTINATIONS -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 space-y-5">
                <div class="border-b border-gray-100 dark:border-gray-700/60 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">محل‌های ذخیره‌سازی بکاپ</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">مشخص کنید بکاپ‌ها در سرور محلی، هاست FTP مرکزی یا هر دو ذخیره شوند.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Local Toggle Card -->
                    <div class="bg-gray-50/80 dark:bg-gray-900/40 p-4 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between">
                        <div>
                            <label for="local_enabled" class="block text-xs font-bold text-gray-900 dark:text-white">ذخیره نسخه در سرور محلی (Local)</label>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-mono" dir="ltr">storage/app/backups/{{ $service->id }}</p>
                        </div>
                        <div>
                            <input type="hidden" name="local_enabled" value="0">
                            <input id="local_enabled" name="local_enabled" type="checkbox" value="1" x-model="local_enabled" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Remote FTP Card -->
                    <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-4 rounded-2xl border border-indigo-200/60 dark:border-indigo-800/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label for="remote_enabled" class="block text-xs font-bold text-gray-900 dark:text-white">آپلود در سرور ریموت (FTP مرکزی)</label>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">ارسال خودکار به پوشه <code>/public_html/{{ $service->domain ?: $service->name }}</code></p>
                            </div>
                            <input type="hidden" name="remote_enabled" value="0">
                            <input id="remote_enabled" name="remote_enabled" type="checkbox" value="1" x-model="remote_enabled" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </div>

                        <div x-show="remote_enabled" x-transition class="space-y-3 pt-2 border-t border-indigo-100 dark:border-indigo-900/50">
                            <div class="text-left">
                                <button type="button" @click="fillCentralFtp()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    تکمیل خودکار اطلاعات سرور مرکزی (vardicrm)
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">هاست FTP</label>
                                    <input type="text" name="remote_host" id="remote_host" value="{{ old('remote_host', $settings['remote_host'] ?? '') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">مسیر ریموت پایه</label>
                                    <input type="text" name="remote_path" id="remote_path" value="{{ old('remote_path', $settings['remote_path'] ?? '/public_html') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">نام کاربری FTP</label>
                                    <input type="text" name="remote_user" id="remote_user" value="{{ old('remote_user', $settings['remote_user'] ?? '') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">رمز عبور FTP (خالی = بدون تغییر)</label>
                                    <input type="password" name="remote_password" id="remote_password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 text-left font-mono" dir="ltr">
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div id="ftp-test-result" class="text-xs h-5 flex items-center font-bold"></div>
                                <button type="button" onclick="testFtpConnection()" class="px-3.5 py-1.5 border border-indigo-600 text-xs font-bold rounded-xl text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition">
                                    تست اتصال FTP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-4 border border-gray-200/80 dark:border-gray-700/60 flex items-center justify-between shadow-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400">تغییرات زمان‌بندی به طور خودکار در کران‌جاب‌های سرور اعمال خواهد شد.</p>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl shadow-md shadow-indigo-600/20 transition duration-150">
                    ذخیره تنظیمات هوشمند
                </button>
            </div>
        </form>
    </div>

    <!-- 2. MANUAL BACKUP TAB -->
    <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
        <!-- Optimization Notice -->
        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-2xl border border-blue-200/70 dark:border-blue-800/60 text-xs text-blue-900 dark:text-blue-200 flex items-center gap-3 shadow-sm mb-6">
            <span class="text-lg">⚡</span>
            <div>
                <span class="font-bold">بهینه‌سازی خودکار:</span>
                پوشه‌های حجیم <code>vendor</code> و <code>node_modules</code> و فایل‌های Git برای سبک‌سازی و افزایش سرعت، از تمام بکاپ‌های زیر استثنا می‌شوند.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- 1: DIRECT DOWNLOAD CARD -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">۱. دانلود مستقیم</h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">دانلود آنی فایل روی کامپیوتر شما</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">فایل در حافظه ساخته شده و بلافاصله توسط مرورگر دانلود می‌گردد.</p>

                    <div class="space-y-3">
                        <!-- Download DB -->
                        <form x-ref="manual_form_download_db" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="db">
                            <button type="button" @click="submitManual('download', 'db')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60 transition shadow-sm">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'download_db'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                    <span>🗄️ دانلود دیتابیس (SQL)</span>
                                </span>
                                <span class="text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 rounded-lg">GZIP</span>
                            </button>
                        </form>

                        <!-- Download Files -->
                        <form x-ref="manual_form_download_files" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="files">
                            <button type="button" @click="submitManual('download', 'files')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60 transition shadow-sm">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'download_files'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                    <span>📁 دانلود سورس فایل‌ها</span>
                                </span>
                                <span class="text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 rounded-lg">TAR.GZ</span>
                            </button>
                        </form>

                        <!-- Download Full -->
                        <form x-ref="manual_form_download_full" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="full">
                            <button type="button" @click="submitManual('download', 'full')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-3 text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'download_full'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                    <span>📦 دانلود پکیج کامل (DB + Files)</span>
                                </span>
                                <span class="text-[10px] bg-emerald-800 text-white px-2 py-0.5 rounded-lg">Full</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2: SAVE TO LOCAL CARD -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">۲. ذخیره در سرور محلی</h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">نگهداری نسخه در Storage سرور</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">فایل در پوشه محلی ذخیره شده و در تاریخچه اضافه می‌گردد.</p>

                    <div class="space-y-3">
                        <!-- Local DB -->
                        <form x-ref="manual_form_local_db" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="db">
                            <button type="button" @click="submitManual('local', 'db')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/60 rounded-xl border border-blue-200/60 dark:border-blue-800/60 transition shadow-sm">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'local_db'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                    <span>🗄️ ذخیره دیتابیس در Local</span>
                                </span>
                                <span class="text-[10px] bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded-lg">ذخیره</span>
                            </button>
                        </form>

                        <!-- Local Files -->
                        <form x-ref="manual_form_local_files" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="files">
                            <button type="button" @click="submitManual('local', 'files')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/60 rounded-xl border border-blue-200/60 dark:border-blue-800/60 transition shadow-sm">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'local_files'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                    <span>📁 ذخیره فایل‌های پروژه در Local</span>
                                </span>
                                <span class="text-[10px] bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded-lg">ذخیره</span>
                            </button>
                        </form>

                        <!-- Local Full -->
                        <form x-ref="manual_form_local_full" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="full">
                            <button type="button" @click="submitManual('local', 'full')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-3 text-xs font-black text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 transition">
                                <span class="flex items-center gap-2">
                                    <span x-show="loadingAction === 'local_full'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                    <span>📦 فول بکاپ کامل در Local</span>
                                </span>
                                <span class="text-[10px] bg-blue-800 text-white px-2 py-0.5 rounded-lg">Full</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3: UPLOAD TO FTP CARD -->
            <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">۳. ارسال به سرور FTP</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">آپلود در هاست پشتیبان مرکزی</p>
                            </div>
                        </div>
                        @if(!empty($settings['remote_enabled']))
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold px-2 py-0.5 rounded-lg">فعال</span>
                        @else
                            <span class="text-[10px] bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300 font-bold px-2 py-0.5 rounded-lg">غیرفعال</span>
                        @endif
                    </div>
                    
                    @if(!empty($settings['remote_enabled']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">فایل ساخته شده و مستقیماً به پوشه اختصاصی دامنه در FTP منتقل می‌شود.</p>

                        <div class="space-y-3">
                            <!-- FTP DB -->
                            <form x-ref="manual_form_ftp_db" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="db">
                                <button type="button" @click="submitManual('ftp', 'db')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 rounded-xl border border-indigo-200/60 dark:border-indigo-800/60 transition shadow-sm">
                                    <span class="flex items-center gap-2">
                                        <span x-show="loadingAction === 'ftp_db'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                        <span>🗄️ ارسال دیتابیس به FTP</span>
                                    </span>
                                    <span class="text-[10px] bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded-lg">آپلود</span>
                                </button>
                            </form>

                            <!-- FTP Files -->
                            <form x-ref="manual_form_ftp_files" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="files">
                                <button type="button" @click="submitManual('ftp', 'files')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 rounded-xl border border-indigo-200/60 dark:border-indigo-800/60 transition shadow-sm">
                                    <span class="flex items-center gap-2">
                                        <span x-show="loadingAction === 'ftp_files'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                                        <span>📁 ارسال فایل‌های پروژه به FTP</span>
                                    </span>
                                    <span class="text-[10px] bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded-lg">آپلود</span>
                                </button>
                            </form>

                            <!-- FTP Full -->
                            <form x-ref="manual_form_ftp_full" action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="full">
                                <button type="button" @click="submitManual('ftp', 'full')" :disabled="loadingAction !== null" class="w-full flex items-center justify-between px-3.5 py-3 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-600/20 transition">
                                    <span class="flex items-center gap-2">
                                        <span x-show="loadingAction === 'ftp_full'" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                        <span>📦 فول بکاپ کامل به FTP</span>
                                    </span>
                                    <span class="text-[10px] bg-indigo-800 text-white px-2 py-0.5 rounded-lg">Full</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl text-amber-800 dark:text-amber-300 text-xs text-center space-y-2">
                            <p class="font-medium">⚠️ ذخیره‌سازی ریموت (FTP) برای این سرویس فعال نیست.</p>
                            <button type="button" @click="activeTab = 'settings'" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold underline">فعال‌سازی FTP در تب تنظیمات</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. STATUS & HISTORY TAB -->
    <div x-show="activeTab === 'status'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Execution Status Card -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white dark:bg-gray-800/90 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-700/60 space-y-5">
                    <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-gray-700/60 pb-3">
                        <span class="w-3 h-3 rounded-full {{ ($last_backup_status['status'] === 'موفق') ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">وضعیت آخرین پشتیبان‌گیری</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/40">
                            <span class="text-gray-500 dark:text-gray-400">وضعیت عملیات:</span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ ($last_backup_status['status'] === 'موفق') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                {{ $last_backup_status['status'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/40">
                            <span class="text-gray-500 dark:text-gray-400">زمان اجرا:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-gray-100" dir="ltr">{{ $last_backup_status['date'] }}</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/40">
                            <span class="text-gray-500 dark:text-gray-400">حجم آخرین خروجی:</span>
                            <span class="font-mono font-black text-indigo-600 dark:text-indigo-400">{{ $last_backup_status['size'] }} MB</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/40">
                            <span class="text-gray-500 dark:text-gray-400">انتقال به FTP:</span>
                            @if($last_backup_status['ftp_uploaded'])
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    موفق
                                </span>
                            @else
                                <span class="text-gray-400">انجام نشده</span>
                            @endif
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-700/60">

                    <!-- Quick Pipeline Run Form -->
                    <div class="space-y-2">
                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="db">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 px-3 border border-blue-200 dark:border-blue-800/80 rounded-xl text-xs font-bold text-blue-700 bg-blue-50/60 hover:bg-blue-100 dark:bg-blue-950/30 dark:text-blue-300 transition">
                                <span>🗄️ اجرای دستی خط لوله دیتابیس</span>
                            </button>
                        </form>

                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="files">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 px-3 border border-emerald-200 dark:border-emerald-800/80 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 transition">
                                <span>📁 اجرای دستی خط لوله فایل‌ها</span>
                            </button>
                        </form>

                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="all">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                                <span>▶️ اجرای کامل خط لوله (DB + Files)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Local Files History -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800/90 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-700/60 overflow-hidden h-full flex flex-col">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-base">فایل‌های بکاپ در سرور محلی (Local)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">آرشیوهای موجود در دایرکتوری محلی سرور</p>
                        </div>
                        <span class="text-xs font-mono font-bold bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-xl text-gray-600 dark:text-gray-300">
                            {{ count($recent_backups) }} فایل
                        </span>
                    </div>

                    <div class="flex-1 overflow-y-auto max-h-[500px]">
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700/40">
                            @forelse($recent_backups as $backup)
                                <li class="p-4 hover:bg-gray-50/80 dark:hover:bg-gray-750/50 flex items-center justify-between gap-4 transition">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-lg flex-shrink-0
                                            {{ str_starts_with($backup['name'], 'db_') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : (str_starts_with($backup['name'], 'files_') ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400') }}">
                                            @if(str_starts_with($backup['name'], 'db_')) 🗄️
                                            @elseif(str_starts_with($backup['name'], 'files_')) 📁
                                            @else 📦
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-mono text-xs font-bold text-gray-900 dark:text-white truncate" dir="ltr">{{ $backup['name'] }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $backup['date'] }}</span>
                                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                                <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $backup['size'] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('backup_tasks.download', [$service->id, $backup['name']]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl text-xs font-bold transition flex-shrink-0 shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        <span>دانلود</span>
                                    </a>
                                </li>
                            @empty
                                <li class="py-12 px-4 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">هیچ فایل پشتیبانی در دایرکتوری محلی یافت نشد.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. LOGS TAB -->
    <div x-show="activeTab === 'logs'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
        <div class="bg-gray-900 rounded-3xl shadow-xl border border-gray-800 overflow-hidden">
            <!-- Terminal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 bg-gray-850 border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-yellow-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-green-500/80 inline-block"></span>
                    </div>
                    <span class="mr-3 text-xs font-mono text-gray-400">backup-execution.log</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="fetchLog()" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded-xl transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span>بروزرسانی لاگ</span>
                    </button>
                </div>
            </div>

            <!-- Terminal Body -->
            <div class="p-5 overflow-x-auto max-h-[600px] overflow-y-auto">
                <pre id="log-output" class="text-emerald-400 text-xs font-mono whitespace-pre-wrap leading-relaxed select-all" dir="ltr">در حال فراخوانی لاگ‌های سیستم...</pre>
            </div>
        </div>
    </div>
</div>

<script>
function testFtpConnection() {
    const host = document.getElementById('remote_host').value;
    const user = document.getElementById('remote_user').value;
    const pass = document.getElementById('remote_password').value;
    const resultSpan = document.getElementById('ftp-test-result');
    
    if(!host || !user) {
        resultSpan.textContent = 'نام کاربری و هاست الزامی است.';
        resultSpan.className = 'text-xs text-red-500 font-bold';
        return;
    }
    
    resultSpan.textContent = 'در حال برقراری اتصال به FTP...';
    resultSpan.className = 'text-xs text-indigo-500 font-bold animate-pulse';
    
    fetch('{{ route('backup_tasks.test_ftp') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ remote_host: host, remote_user: user, remote_password: pass })
    }).then(res => res.json()).then(data => {
        resultSpan.textContent = data.message;
        resultSpan.className = 'text-xs font-bold ' + (data.success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500');
    }).catch(e => {
        resultSpan.textContent = 'خطای ارتباط در تست اتصال FTP';
        resultSpan.className = 'text-xs text-red-500 font-bold';
    });
}

function fetchLog() {
    const logEl = document.getElementById('log-output');
    if (!logEl) return;
    logEl.textContent = 'در حال دریافت لاگ...';
    
    fetch('{{ route('backup_tasks.log', $service->id) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        logEl.textContent = data.log || 'لاگی برای این سرویس یافت نشد.';
    })
    .catch(() => {
        logEl.textContent = 'خطا در ارتباط با سرور جهت دریافت لاگ.';
    });
}
</script>
@endsection
