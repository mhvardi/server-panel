@extends('layouts.app')

@section('title', 'پشتیبان‌گیری: ' . $service->name)

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ activeTab: 'settings' }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">پشتیبان‌گیری هوشمند: {{ $service->name }}</h1>
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
                ⚙️ زمان‌بندی مستقل و نگهداری
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

    <!-- 1. SETTINGS TAB (INDEPENDENT DB & FILES SCHEDULING) -->
    <div x-show="activeTab === 'settings'">
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
        }">
            @csrf
            <div class="space-y-6 mb-6">
                <!-- Section 1: DATABASE BACKUP SCHEDULE & RETENTION -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-r-4 border-blue-500 overflow-hidden">
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/50 dark:text-blue-400">
                                    🗄️
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">پشتیبان‌گیری خودکار پایگاه‌داده (Database)</h3>
                                    <p class="text-xs text-gray-500">پایگاه‌داده‌ها حجم کمی دارند و معمولاً به صورت روزانه بکاپ گرفته می‌شوند.</p>
                                </div>
                            </div>
                            <div>
                                <input type="hidden" name="db_enabled" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="db_enabled" value="1" x-model="db_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="mr-3 text-xs font-semibold text-gray-700 dark:text-gray-300" x-text="db_enabled ? 'فعال' : 'غیرفعال'"></span>
                                </label>
                            </div>
                        </div>

                        <div x-show="db_enabled" class="space-y-4 pt-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده شناسایی‌شده:</label>
                                <div class="mt-1 flex items-center">
                                    <input type="text" disabled value="{{ $service->getDatabaseName() ?? 'یافت نشد' }}" class="block w-full md:w-1/3 rounded-md border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-600 dark:border-gray-500 text-gray-600 text-left font-mono text-xs" dir="ltr">
                                    @if(!$service->getDatabaseName())
                                        <span class="mr-3 text-xs text-red-500 font-bold">⚠️ در فایل env یافت نشد!</span>
                                    @else
                                        <span class="mr-3 text-xs text-green-600 font-bold">✅ متصل به .env</span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="db_cron_preset" class="block text-xs font-medium text-gray-700 dark:text-gray-300">زمان‌بندی دیتابیس (Cron)</label>
                                    <select x-model="db_cron_preset" name="db_cron_preset" id="db_cron_preset" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600">
                                        <option value="0 2 * * *">هر شب ساعت ۰۲:۰۰ بامداد (پیشنهادی)</option>
                                        <option value="0 0 * * *">هر شب ساعت ۰۰:۰۰ بامداد</option>
                                        <option value="0 */12 * * *">هر ۱۲ ساعت یک‌بار</option>
                                        <option value="custom">تنظیم دستی (Custom Cron)</option>
                                    </select>
                                </div>
                                <div x-show="db_cron_preset === 'custom'">
                                    <label for="db_cron_custom" class="block text-xs font-medium text-gray-700 dark:text-gray-300">عبارت Cron سفارشی دیتابیس</label>
                                    <input type="text" name="db_cron_custom" id="db_cron_custom" value="{{ old('db_cron_custom', $settings['db_cron_expression'] ?? '0 2 * * *') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                </div>
                                <div>
                                    <label for="db_local_retention_days" class="block text-xs font-medium text-gray-700 dark:text-gray-300">نگهداری دیتابیس در Local</label>
                                    <div class="flex items-center mt-1">
                                        <input type="number" name="db_local_retention_days" id="db_local_retention_days" value="{{ old('db_local_retention_days', $settings['db_local_retention_days'] ?? 3) }}" min="1" class="block w-20 rounded-r-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-center">
                                        <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-xs dark:bg-gray-600 dark:text-gray-300">روز</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">فایل‌های sql قدیمی‌تر از این روز پاک می‌شوند.</p>
                                </div>
                                <div>
                                    <label for="db_remote_retention_days" class="block text-xs font-medium text-gray-700 dark:text-gray-300">نگهداری دیتابیس در FTP</label>
                                    <div class="flex items-center mt-1">
                                        <input type="number" name="db_remote_retention_days" id="db_remote_retention_days" value="{{ old('db_remote_retention_days', $settings['db_remote_retention_days'] ?? 3) }}" min="1" class="block w-20 rounded-r-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-center">
                                        <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-xs dark:bg-gray-600 dark:text-gray-300">روز</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">در هاست FTP فقط فایل‌های N روز اخیر می‌مانند.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: FILES BACKUP SCHEDULE & RETENTION -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-r-4 border-emerald-500 overflow-hidden">
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                                    📁
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">پشتیبان‌گیری خودکار فایل‌های سورس پروژه (Files)</h3>
                                    <p class="text-xs text-gray-500">فایل‌های سورس به علت حجم بیشتر معمولاً به صورت هفتگی بکاپ‌گیری می‌شوند (بدون vendor و node_modules).</p>
                                </div>
                            </div>
                            <div>
                                <input type="hidden" name="files_enabled" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="files_enabled" value="1" x-model="files_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                                    <span class="mr-3 text-xs font-semibold text-gray-700 dark:text-gray-300" x-text="files_enabled ? 'فعال' : 'غیرفعال'"></span>
                                </label>
                            </div>
                        </div>

                        <div x-show="files_enabled" class="space-y-4 pt-2">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="files_cron_preset" class="block text-xs font-medium text-gray-700 dark:text-gray-300">زمان‌بندی فایل‌ها (Cron)</label>
                                    <select x-model="files_cron_preset" name="files_cron_preset" id="files_cron_preset" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600">
                                        <option value="0 2 * * 5">هر جمعه ساعت ۰۲:۰۰ بامداد (هفتگی پیشنهادی)</option>
                                        <option value="0 2 * * 0">هر یکشنبه ساعت ۰۲:۰۰ بامداد</option>
                                        <option value="0 3 1 * *">اول هر ماه ساعت ۰۳:۰۰ بامداد (ماهانه)</option>
                                        <option value="custom">تنظیم دستی (Custom Cron)</option>
                                    </select>
                                </div>
                                <div x-show="files_cron_preset === 'custom'">
                                    <label for="files_cron_custom" class="block text-xs font-medium text-gray-700 dark:text-gray-300">عبارت Cron سفارشی فایل‌ها</label>
                                    <input type="text" name="files_cron_custom" id="files_cron_custom" value="{{ old('files_cron_custom', $settings['files_cron_expression'] ?? '0 2 * * 5') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                </div>
                                <div>
                                    <label for="files_local_retention_days" class="block text-xs font-medium text-gray-700 dark:text-gray-300">نگهداری فایل‌ها در Local</label>
                                    <div class="flex items-center mt-1">
                                        <input type="number" name="files_local_retention_days" id="files_local_retention_days" value="{{ old('files_local_retention_days', $settings['files_local_retention_days'] ?? 14) }}" min="1" class="block w-20 rounded-r-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-center">
                                        <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-xs dark:bg-gray-600 dark:text-gray-300">روز</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">آرشیوهای قدیمی‌تر از این روز پاک می‌شوند.</p>
                                </div>
                                <div>
                                    <label for="files_remote_retention_days" class="block text-xs font-medium text-gray-700 dark:text-gray-300">نگهداری فایل‌ها در FTP</label>
                                    <div class="flex items-center mt-1">
                                        <input type="number" name="files_remote_retention_days" id="files_remote_retention_days" value="{{ old('files_remote_retention_days', $settings['files_remote_retention_days'] ?? 14) }}" min="1" class="block w-20 rounded-r-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-center">
                                        <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-xs dark:bg-gray-600 dark:text-gray-300">روز</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">آرشیوهای قدیمی‌تر در هاست FTP پاک می‌شوند.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: STORAGE DESTINATIONS (LOCAL & FTP CONFIG) -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-r-4 border-indigo-500 overflow-hidden">
                    <div class="p-6 space-y-4">
                        <h3 class="text-base font-bold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-3">محل‌های ذخیره‌سازی بکاپ‌ها</h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Local Storage Toggle -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-between">
                                <div>
                                    <label for="local_enabled" class="block text-sm font-bold text-gray-800 dark:text-gray-200">ذخیره بکاپ در سرور محلی (Local)</label>
                                    <p class="text-xs text-gray-500 mt-1">در مسیر <code>storage/app/backups/{{ $service->id }}</code> ذخیره می‌شود.</p>
                                </div>
                                <div>
                                    <input type="hidden" name="local_enabled" value="0">
                                    <input id="local_enabled" name="local_enabled" type="checkbox" value="1" x-model="local_enabled" class="h-5 w-5 rounded border-gray-300 text-indigo-600">
                                </div>
                            </div>

                            <!-- FTP Storage Toggle -->
                            <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <label for="remote_enabled" class="block text-sm font-bold text-gray-800 dark:text-gray-200">آپلود در سرور ریموت (FTP)</label>
                                        <p class="text-xs text-gray-500 mt-0.5">در پوشه مجزای <code>/public_html/{{ $service->domain ?: $service->name }}</code></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="remote_enabled" value="0">
                                        <input id="remote_enabled" name="remote_enabled" type="checkbox" value="1" x-model="remote_enabled" class="h-5 w-5 rounded border-indigo-400 text-indigo-600">
                                    </div>
                                </div>

                                <div x-show="remote_enabled" class="space-y-3 pt-2 border-t border-indigo-200 dark:border-indigo-800">
                                    <div class="text-left">
                                        <button type="button" @click="fillCentralFtp()" class="text-xs px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded shadow-sm">تکمیل خودکار اطلاعات سرور مرکزی</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">هاست FTP</label>
                                            <input type="text" name="remote_host" id="remote_host" value="{{ old('remote_host', $settings['remote_host'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">مسیر پایه</label>
                                            <input type="text" name="remote_path" id="remote_path" value="{{ old('remote_path', $settings['remote_path'] ?? '/public_html') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">نام کاربری FTP</label>
                                            <input type="text" name="remote_user" id="remote_user" value="{{ old('remote_user', $settings['remote_user'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">رمز عبور FTP (خالی = بدون تغییر)</label>
                                            <input type="password" name="remote_password" id="remote_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-xs dark:bg-gray-700 dark:border-gray-600 text-left font-mono" dir="ltr">
                                        </div>
                                    </div>
                                    <div class="text-left pt-1 flex items-center justify-between">
                                        <div id="ftp-test-result" class="text-xs h-4"></div>
                                        <button type="button" onclick="testFtpConnection()" class="px-3 py-1.5 border border-indigo-600 text-xs font-medium rounded text-indigo-600 hover:bg-indigo-100 dark:border-indigo-400 dark:text-indigo-400 transition">تست اتصال FTP</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-lg border-t border-gray-200 dark:border-gray-700 text-left">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-bold shadow-sm transition">ذخیره تمام تنظیمات هوشمند</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 2. MANUAL BACKUP TAB -->
    <div x-show="activeTab === 'manual'" style="display: none;">
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>پوشه‌های حجیم <code>vendor</code> و <code>node_modules</code> و فایل‌های Git برای سبک‌سازی و افزایش سرعت، به طور خودکار از تمام بکاپ‌های زیر استثنا می‌شوند.</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- 1: DIRECT DOWNLOAD -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-t-4 border-emerald-500 flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-2 bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        </span>
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">۱. دانلود مستقیم به سیستم</h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">بکاپ ایجاد شده مستقیماً روی سیستم شما دانلود خواهد شد.</p>

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

            <!-- 2: SAVE TO LOCAL -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-t-4 border-blue-500 flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-2 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/50 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                        </span>
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">۲. ذخیره در سرور محلی (Local)</h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">فایل در پوشه نگهداری سرور ذخیره می‌شود و طبق روزهای تنظیم‌شده پاک خواهد شد.</p>

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

            <!-- 3: UPLOAD TO FTP -->
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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">فایل بلافاصله آماده و به هاست FTP مرکزی ارسال می‌گردد.</p>

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
                        <span class="text-sm text-gray-500">حجم آخرین خروجی:</span>
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
                    
                    <div class="space-y-2">
                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="db">
                            <button type="submit" class="w-full flex justify-center py-2 px-3 border border-blue-600 rounded-md shadow-sm text-xs font-bold text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                🗄️ اجرای بکاپ دیتابیس (بر اساس تنظیمات)
                            </button>
                        </form>
                        
                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="files">
                            <button type="submit" class="w-full flex justify-center py-2 px-3 border border-emerald-600 rounded-md shadow-sm text-xs font-bold text-emerald-600 hover:bg-emerald-50 dark:border-emerald-400 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                                📁 اجرای بکاپ فایل‌ها (بر اساس تنظیمات)
                            </button>
                        </form>

                        <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="all">
                            <button type="submit" class="w-full flex justify-center py-2 px-3 border border-transparent rounded-md shadow-sm text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700">
                                ▶️ اجرای کامل هر دو (DB + Files)
                            </button>
                        </form>
                    </div>
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
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg">
                                            @if(str_starts_with($backup['name'], 'db_')) 🗄️
                                            @elseif(str_starts_with($backup['name'], 'files_')) 📁
                                            @else 📦
                                            @endif
                                        </span>
                                        <div>
                                            <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" dir="ltr">{{ $backup['name'] }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $backup['date'] }} | حجم: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $backup['size'] }}</span></p>
                                        </div>
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
        resultSpan.className = 'text-xs h-4 text-red-500';
        return;
    }
    
    resultSpan.textContent = 'در حال تست اتصال FTP...';
    resultSpan.className = 'text-xs h-4 text-indigo-500 animate-pulse';
    
    fetch('{{ route('backup_tasks.test_ftp') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ remote_host: host, remote_user: user, remote_password: pass })
    }).then(res => res.json()).then(data => {
        resultSpan.textContent = data.message;
        resultSpan.className = 'text-xs h-4 font-bold ' + (data.success ? 'text-green-500' : 'text-red-500');
    }).catch(e => {
        resultSpan.textContent = 'خطای شبکه در تست اتصال';
        resultSpan.className = 'text-xs h-4 text-red-500';
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
