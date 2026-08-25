@extends('layouts.app')

@section('title', 'پشتیبان‌گیری: ' . $service->name)

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ activeTab: 'settings' }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">پشتیبان‌گیری: {{ $service->name }}</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" dir="ltr">{{ $service->domain }} ({{ env('BACKUP_MOCK_ENABLED') ? 'MOCK MODE' : $service->path }})</p>
        </div>
        <a href="{{ route('backup_tasks.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-lg shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
            <span>بازگشت</span>
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">{{ session('error') }}</div>
    @endif

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8 space-x-reverse" aria-label="Tabs">
            <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                ⚙️ تنظیمات خودکار
            </button>
            <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                ⚡ عملیات بکاپ دستی
            </button>
            <button @click="activeTab = 'status'" :class="activeTab === 'status' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🖥️ وضعیت و تاریخچه
            </button>
            <button @click="activeTab = 'logs'; fetchLog()" :class="activeTab === 'logs' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📜 لاگ سیستم
            </button>
        </nav>
    </div>

    <!-- 1. SETTINGS TAB -->
    <div x-show="activeTab === 'settings'">
        <form action="{{ route('backup_tasks.save_settings', $service->id) }}" method="POST" x-data="{ 
            local_enabled: {{ old('local_enabled', $settings['local_enabled'] ?? true) ? 'true' : 'false' }},
            remote_enabled: {{ old('remote_enabled', $settings['remote_enabled'] ?? false) ? 'true' : 'false' }}, 
            include_db: {{ old('include_db', $settings['include_db'] ?? false) ? 'true' : 'false' }},
            cron_preset: '{{ old('cron_preset', in_array($settings['cron_expression'] ?? '', ['0 0 * * *','0 2 * * *','0 2 * * 5']) ? $settings['cron_expression'] : 'custom') }}',
            fillCentralFtp() {
                this.remote_enabled = true;
                document.getElementById('remote_host').value = '80.249.115.114';
                document.getElementById('remote_user').value = 'mhvardi@backup.vardicrm.ir';
                document.getElementById('remote_password').value = 'pqDd2PZ1V8Pkq6r3';
                document.getElementById('remote_path').value = '/public_html';
                document.getElementById('remote_retention_days').value = '7';
            }
        }">
            @csrf
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
                <div class="p-6 space-y-6">
                    <fieldset class="space-y-4">
                        <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">تنظیمات پایه و زمان‌بندی خودکار</legend>
                        <div class="flex items-center">
                            <input type="hidden" name="enabled" value="0">
                            <input id="enabled" name="enabled" type="checkbox" value="1" {{ old('enabled', $settings['enabled'] ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                            <label for="enabled" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">فعال کردن پشتیبان‌گیری خودکار و دوره‌ای (CronJob)</label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="cron_preset" class="block text-sm font-medium text-gray-700 dark:text-gray-300">انتخاب زمان‌بندی</label>
                                <select x-model="cron_preset" name="cron_preset" id="cron_preset" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                    <option value="0 0 * * *">هر شب ساعت ۰۰:۰۰ بامداد</option>
                                    <option value="0 2 * * *">هر شب ساعت ۰۲:۰۰ بامداد (پیشنهادی)</option>
                                    <option value="0 2 * * 5">هر جمعه ساعت ۰۲:۰۰ بامداد</option>
                                    <option value="custom">تنظیم دستی (Custom Cron)</option>
                                </select>
                            </div>
                            <div x-show="cron_preset === 'custom'">
                                <label for="cron_custom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">عبارت Cron سفارشی</label>
                                <input type="text" name="cron_custom" id="cron_custom" value="{{ old('cron_custom', $settings['cron_expression'] ?? '0 2 * * *') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">محتوای پشتیبان در اجرای خودکار</legend>
                        <div class="flex items-center">
                            <input type="hidden" name="include_files" value="0">
                            <input id="include_files" name="include_files" type="checkbox" value="1" {{ old('include_files', $settings['include_files'] ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                            <label for="include_files" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                پشتیبان‌گیری از فایل‌های پروژه <span class="text-xs text-gray-500 font-normal">(پوشه‌های vendor و node_modules به طور خودکار استثنا می‌شوند)</span>
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="include_db" value="0">
                            <input id="include_db" name="include_db" type="checkbox" value="1" x-model="include_db" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                            <label for="include_db" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">پشتیبان‌گیری از پایگاه‌داده</label>
                        </div>
                        <div x-show="include_db">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده (شناسایی خودکار از .env)</label>
                            <div class="mt-1 flex items-center">
                                <input type="text" disabled value="{{ $service->getDatabaseName() ?? 'یافت نشد' }}" class="block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-600 dark:border-gray-500 text-gray-500 text-left font-mono" dir="ltr">
                                @if(!$service->getDatabaseName())
                                    <span class="mr-3 text-xs text-red-500 font-bold">⚠️ در فایل env یافت نشد!</span>
                                @else
                                    <span class="mr-3 text-xs text-green-500 font-bold">✅ متصل به .env سرویس</span>
                                @endif
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">محل‌های ذخیره‌سازی و سیاست نگهداری (Retention)</legend>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Local Storage -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center mb-4">
                                    <input type="hidden" name="local_enabled" value="0">
                                    <input id="local_enabled" name="local_enabled" type="checkbox" value="1" x-model="local_enabled" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                    <label for="local_enabled" class="mr-3 block text-sm font-bold text-gray-800 dark:text-gray-200">ذخیره بکاپ در سرور محلی (Local)</label>
                                </div>
                                <div x-show="local_enabled">
                                    <label for="local_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">مدت نگهداری در سرور محلی</label>
                                    <div class="flex items-center mt-1">
                                        <input type="number" name="local_retention_days" id="local_retention_days" value="{{ old('local_retention_days', $settings['local_retention_days'] ?? 7) }}" min="1" class="block w-24 rounded-r-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-center">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 sm:text-sm dark:bg-gray-600 dark:border-gray-500 dark:text-gray-300">روز</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">بکاپ‌های محلی قدیمی‌تر از این تعداد روز به صورت خودکار حذف می‌شوند.</p>
                                </div>
                            </div>

                            <!-- FTP Storage -->
                            <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <input type="hidden" name="remote_enabled" value="0">
                                        <input id="remote_enabled" name="remote_enabled" type="checkbox" value="1" x-model="remote_enabled" class="h-4 w-4 rounded border-indigo-400 text-indigo-600">
                                        <label for="remote_enabled" class="mr-3 block text-sm font-bold text-gray-800 dark:text-gray-200">آپلود در سرور ریموت (FTP)</label>
                                    </div>
                                    <button type="button" @click="fillCentralFtp()" class="text-xs px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded shadow-sm">تکمیل خودکار اطلاعات سرور مرکزی</button>
                                </div>
                                
                                <div x-show="remote_enabled" class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">هاست FTP</label>
                                            <input type="text" name="remote_host" id="remote_host" value="{{ old('remote_host', $settings['remote_host'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">مسیر ریموت پایه</label>
                                            <input type="text" name="remote_path" id="remote_path" value="{{ old('remote_path', $settings['remote_path'] ?? '/public_html') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">نام کاربری FTP</label>
                                            <input type="text" name="remote_user" id="remote_user" value="{{ old('remote_user', $settings['remote_user'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">رمز عبور FTP (خالی = بدون تغییر)</label>
                                            <input type="password" name="remote_password" id="remote_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                    </div>
                                    <hr class="border-indigo-200 dark:border-indigo-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label for="remote_retention_days" class="block text-xs font-medium text-gray-700 dark:text-gray-300">مدت نگهداری در FTP</label>
                                            <div class="flex items-center mt-1">
                                                <input type="number" name="remote_retention_days" id="remote_retention_days" value="{{ old('remote_retention_days', $settings['remote_retention_days'] ?? 7) }}" min="1" class="block w-20 rounded-r-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 text-center">
                                                <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 sm:text-xs dark:bg-gray-600 dark:border-gray-500 dark:text-gray-300">روز</span>
                                            </div>
                                        </div>
                                        <div class="text-left mt-4">
                                            <button type="button" onclick="testFtpConnection()" class="px-3 py-1.5 border border-indigo-600 text-xs font-medium rounded text-indigo-600 hover:bg-indigo-100 dark:border-indigo-400 dark:text-indigo-400 transition">تست اتصال FTP</button>
                                            <div id="ftp-test-result" class="text-xs mt-1 block h-4"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-lg border-t border-gray-200 dark:border-gray-700 text-left">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium shadow-sm transition">ذخیره پیکربندی</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 2. MANUAL BACKUP TAB (DEDICATED SECTION) -->
    <div x-show="activeTab === 'manual'" style="display: none;">
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>در تمام بخش‌های زیر، پوشه‌های حجیم <code>vendor</code> و <code>node_modules</code> و فایل‌های Git برای سبک‌سازی و افزایش سرعت، به طور خودکار از بکاپ استثنا می‌شوند.</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Action 1: DIRECT DOWNLOAD -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-t-4 border-emerald-500 flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-2 bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        </span>
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">۱. دانلود مستقیم به سیستم</h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">بکاپ گرفته شده مستقیماً روی مرورگر شما دانلود می‌شود.</p>

                    <div class="space-y-3">
                        <!-- Download DB -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="db">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40 rounded-md border border-emerald-200 dark:border-emerald-800 transition">
                                <span class="flex items-center gap-1.5">🗄️ دانلود دیتابیس (SQL)</span>
                                <span class="text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-1.5 py-0.5 rounded">GZIP</span>
                            </button>
                        </form>

                        <!-- Download Files -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="files">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40 rounded-md border border-emerald-200 dark:border-emerald-800 transition">
                                <span class="flex items-center gap-1.5">📁 دانلود فایل‌های پروژه</span>
                                <span class="text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-1.5 py-0.5 rounded">TAR.GZ</span>
                            </button>
                        </form>

                        <!-- Download Full -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="download">
                            <input type="hidden" name="type" value="full">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md shadow-sm transition">
                                <span class="flex items-center gap-1.5">📦 دانلود کامل (دیتابیس + فایل‌ها)</span>
                                <span class="text-[10px] bg-emerald-800 text-white px-1.5 py-0.5 rounded">Full</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Action 2: SAVE TO LOCAL -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-t-4 border-blue-500 flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-2 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/50 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                        </span>
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">۲. ذخیره در سرور محلی (Local)</h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">بکاپ در پوشه ذخیره‌سازی سرور قرار می‌گیرد و در تاریخچه اضافه می‌شود.</p>

                    <div class="space-y-3">
                        <!-- Local DB -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="db">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/40 rounded-md border border-blue-200 dark:border-blue-800 transition">
                                <span class="flex items-center gap-1.5">🗄️ بکاپ دیتابیس در Local</span>
                                <span class="text-[10px] bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-1.5 py-0.5 rounded">ذخیره</span>
                            </button>
                        </form>

                        <!-- Local Files -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="files">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/40 rounded-md border border-blue-200 dark:border-blue-800 transition">
                                <span class="flex items-center gap-1.5">📁 بکاپ فایل‌های پروژه در Local</span>
                                <span class="text-[10px] bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-1.5 py-0.5 rounded">ذخیره</span>
                            </button>
                        </form>

                        <!-- Local Full -->
                        <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target" value="local">
                            <input type="hidden" name="type" value="full">
                            <button type="submit" class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm transition">
                                <span class="flex items-center gap-1.5">📦 فول بکاپ کامل در Local</span>
                                <span class="text-[10px] bg-blue-800 text-white px-1.5 py-0.5 rounded">Full</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Action 3: UPLOAD TO FTP -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-t-4 border-indigo-500 flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-2 bg-indigo-100 text-indigo-600 rounded-lg dark:bg-indigo-900/50 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            </span>
                            <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">۳. ارسال به سرور FTP</h3>
                        </div>
                        @if(!empty($settings['remote_enabled']))
                            <span class="text-[10px] bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 font-bold px-2 py-0.5 rounded">فعال</span>
                        @else
                            <span class="text-[10px] bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 font-bold px-2 py-0.5 rounded">غیرفعال</span>
                        @endif
                    </div>
                    
                    @if(!empty($settings['remote_enabled']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">فایل مستقیماً فشرده و به هاست FTP مرکزی ارسال می‌شود.</p>

                        <div class="space-y-3">
                            <!-- FTP DB -->
                            <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="db">
                                <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 dark:hover:bg-indigo-900/40 rounded-md border border-indigo-200 dark:border-indigo-800 transition">
                                    <span class="flex items-center gap-1.5">🗄️ ارسال دیتابیس به FTP</span>
                                    <span class="text-[10px] bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 px-1.5 py-0.5 rounded">آپلود</span>
                                </button>
                            </form>

                            <!-- FTP Files -->
                            <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="files">
                                <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 dark:hover:bg-indigo-900/40 rounded-md border border-indigo-200 dark:border-indigo-800 transition">
                                    <span class="flex items-center gap-1.5">📁 ارسال فایل‌های پروژه به FTP</span>
                                    <span class="text-[10px] bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 px-1.5 py-0.5 rounded">آپلود</span>
                                </button>
                            </form>

                            <!-- FTP Full -->
                            <form action="{{ route('backup_tasks.manual', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target" value="ftp">
                                <input type="hidden" name="type" value="full">
                                <button type="submit" class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm transition">
                                    <span class="flex items-center gap-1.5">📦 فول بکاپ کامل به FTP</span>
                                    <span class="text-[10px] bg-indigo-800 text-white px-1.5 py-0.5 rounded">Full</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-md text-amber-800 dark:text-amber-300 text-xs text-center space-y-2">
                            <p>⚠️ ذخیره‌سازی ریموت (FTP) برای این سرویس فعال نیست.</p>
                            <button type="button" @click="activeTab = 'settings'" class="text-xs text-indigo-600 dark:text-indigo-400 underline font-bold">ورود به تنظیمات و فعال‌سازی FTP</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. STATUS & HISTORY TAB -->
    <div x-show="activeTab === 'status'" style="display: none;">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border-t-4 border-indigo-500">
                    <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">آخرین وضعیت اجرای خودکار</h3>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">وضعیت:</span>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ ($last_backup_status['status'] === 'موفق') ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $last_backup_status['status'] }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">زمان اتمام:</span>
                        <span class="text-sm font-mono" dir="ltr">{{ $last_backup_status['date'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">حجم بکاپ:</span>
                        <span class="text-sm font-mono text-indigo-600 font-bold">{{ $last_backup_status['size'] }} MB</span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-5">
                        <span class="text-sm text-gray-500">ارسال به FTP:</span>
                        @if($last_backup_status['ftp_uploaded'])
                            <span class="text-green-500 font-bold">✅ موفق</span>
                        @else
                            <span class="text-gray-400">❌ انجام نشده</span>
                        @endif
                    </div>
                    
                    <hr class="border-gray-200 dark:border-gray-700 my-4">
                    
                    <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            ▶️ اجرای خط لوله کامل (Full Pipeline)
                        </button>
                    </form>
                    
                    <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="ftp_only" value="1">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-indigo-200 rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800">
                            ☁️ ارسال مجدد آخرین بکاپ به FTP
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="lg:col-span-2">
                <!-- History -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md h-full">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800 dark:text-gray-200">بکاپ‌های موجود در سرور محلی (Local)</h3>
                        <span class="text-xs text-gray-500">{{ count($recent_backups) }} فایل موجود</span>
                    </div>
                    <div class="p-0">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recent_backups as $backup)
                                <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex justify-between items-center">
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" dir="ltr">{{ $backup['name'] }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $backup['date'] }} | حجم: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $backup['size'] }}</span></p>
                                    </div>
                                    <a href="{{ route('backup_tasks.download', [$service->id, $backup['name']]) }}" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white rounded text-xs font-medium">دانلود مستقیم</a>
                                </li>
                            @empty
                                <li class="p-8 text-center text-gray-500">هیچ فایلی در Local یافت نشد.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. LOGS TAB -->
    <div x-show="activeTab === 'logs'" style="display: none;">
        <div class="bg-gray-900 rounded-lg shadow-xl border border-gray-700 overflow-hidden">
            <div class="flex justify-between items-center px-4 py-2 bg-gray-800 border-b border-gray-700">
                <span class="text-gray-300 text-sm font-mono">Terminal Output — آخرین اجرا</span>
                <button @click="fetchLog()" class="text-xs text-indigo-400 hover:text-indigo-300">🔄 بروزرسانی لاگ</button>
            </div>
            <div class="p-4 overflow-x-auto">
                <pre id="log-output" class="text-green-400 text-sm font-mono whitespace-pre-wrap leading-relaxed" dir="ltr">در حال دریافت لاگ...</pre>
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
        resultSpan.className = 'text-xs mt-1 block h-4 text-red-500';
        return;
    }
    
    resultSpan.textContent = 'در حال تست اتصال FTP...';
    resultSpan.className = 'text-xs mt-1 block h-4 text-indigo-500 animate-pulse';
    
    fetch('{{ route('backup_tasks.test_ftp') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ remote_host: host, remote_user: user, remote_password: pass })
    }).then(res => res.json()).then(data => {
        resultSpan.textContent = data.message;
        resultSpan.className = 'text-xs mt-1 block h-4 font-bold ' + (data.success ? 'text-green-500' : 'text-red-500');
    }).catch(e => {
        resultSpan.textContent = 'خطای شبکه در تست اتصال';
        resultSpan.className = 'text-xs mt-1 block h-4 text-red-500';
    });
}

function fetchLog() {
    document.getElementById('log-output').textContent = 'در حال دریافت لاگ...';
    fetch('{{ route('backup_tasks.log', $service->id) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('log-output').textContent = data.log || 'لاگی برای نمایش وجود ندارد.';
    });
}
</script>
@endsection
