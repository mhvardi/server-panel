@extends('layouts.app')

@section('title', 'پشتیبان‌گیری: ' . $service->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">پشتیبان‌گیری: {{ $service->name }}</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" dir="ltr">{{ $service->domain }} ({{ $service->path }})</p>
        </div>
        <a href="{{ route('backup_tasks.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-lg shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
            <span>بازگشت</span>
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Settings Form --}}
        <div class="lg:col-span-2">
            <form action="{{ route('backup_tasks.save_settings', $service->id) }}" method="POST" x-data="{ 
                remote_enabled: {{ old('remote_enabled', $settings['remote_enabled'] ?? false) ? 'true' : 'false' }}, 
                include_db: {{ old('include_db', $settings['include_db'] ?? false) ? 'true' : 'false' }},
                fillCentralFtp() {
                    this.remote_enabled = true;
                    document.getElementById('remote_host').value = '80.249.115.114';
                    document.getElementById('remote_user').value = 'mhvardi@backup.vardicrm.ir';
                    document.getElementById('remote_password').value = 'pqDd2PZ1V8Pkq6r3';
                    document.getElementById('remote_path').value = '/public_html';
                    document.getElementById('remote_retention').value = '2';
                }
            }">
                @csrf
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6 space-y-6">
                        <fieldset class="space-y-4">
                            <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">تنظیمات عمومی</legend>
                            <div class="flex items-center">
                                <input type="hidden" name="enabled" value="0">
                                <input id="enabled" name="enabled" type="checkbox" value="1" {{ old('enabled', $settings['enabled'] ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <label for="enabled" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">فعال کردن پشتیبان‌گیری خودکار زمان‌بندی‌شده</label>
                            </div>
                            <div>
                                <label for="cron_expression" class="block text-sm font-medium text-gray-700 dark:text-gray-300">زمان‌بندی (Cron)</label>
                                <input type="text" name="cron_expression" id="cron_expression" value="{{ old('cron_expression', $settings['cron_expression'] ?? '0 2 * * *') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr">
                                <p class="text-xs text-gray-500 mt-1">مثال: <code>0 2 * * *</code> (هر شب ساعت ۲ بامداد)</p>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-4">
                            <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">محتوای پشتیبان</legend>
                            <div class="flex items-center">
                                <input type="hidden" name="include_files" value="0">
                                <input id="include_files" name="include_files" type="checkbox" value="1" {{ old('include_files', $settings['include_files'] ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <label for="include_files" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">پشتیبان‌گیری از فایل‌های پروژه</label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="include_db" value="0">
                                <input id="include_db" name="include_db" type="checkbox" value="1" x-model="include_db" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <label for="include_db" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">پشتیبان‌گیری از پایگاه‌داده</label>
                            </div>
                            <div x-show="include_db">
                                <label for="db_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده (Database Name)</label>
                                <input type="text" name="db_name" id="db_name" value="{{ old('db_name', $settings['db_name'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr" placeholder="مثلاً: crm_demo">
                            </div>
                        </fieldset>

                        <fieldset class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 dark:border-gray-700 pb-2 mb-4 gap-2">
                                <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">ذخیره‌سازی ریموت (FTP)</legend>
                                <button type="button" @click="fillCentralFtp()" class="inline-flex items-center px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-md shadow-sm transition">
                                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                                    تنظیم خودکار سرور بکاپ مرکزی (vardicrm)
                                </button>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="remote_enabled" value="0">
                                <input id="remote_enabled" name="remote_enabled" type="checkbox" value="1" x-model="remote_enabled" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <label for="remote_enabled" class="mr-3 block text-sm font-medium text-gray-700 dark:text-gray-300">فعال کردن آپلود در سرور ریموت FTP</label>
                            </div>
                            <div x-show="remote_enabled" class="space-y-4 border-r-4 border-indigo-500 pr-4 mr-1">
                                <div>
                                    <label for="remote_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">هاست FTP</label>
                                    <input type="text" name="remote_host" id="remote_host" value="{{ old('remote_host', $settings['remote_host'] ?? '80.249.115.114') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr" placeholder="80.249.115.114">
                                </div>
                                <div>
                                    <label for="remote_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام کاربری FTP</label>
                                    <input type="text" name="remote_user" id="remote_user" value="{{ old('remote_user', $settings['remote_user'] ?? 'mhvardi@backup.vardicrm.ir') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr" placeholder="mhvardi@backup.vardicrm.ir">
                                </div>
                                <div>
                                    <label for="remote_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">رمز عبور FTP</label>
                                    <input type="password" name="remote_password" id="remote_password" value="{{ old('remote_password', $settings['remote_password'] ?? 'pqDd2PZ1V8Pkq6r3') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr">
                                </div>
                                <div>
                                    <label for="remote_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300">مسیر ریموت پایه (Base Remote Path)</label>
                                    <input type="text" name="remote_path" id="remote_path" value="{{ old('remote_path', $settings['remote_path'] ?? '/public_html') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr" placeholder="/public_html">
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                        💡 بکاپ‌ها بر اساس نام دامنه در پوشه <code>/public_html/{{ $service->domain ?: $service->name }}</code> قرار می‌گیرند.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="testFtpConnection()" class="inline-flex items-center px-3 py-1.5 border border-indigo-600 text-xs font-medium rounded-md text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                                        تست اتصال FTP
                                    </button>
                                    <span id="ftp-test-result" class="text-xs mr-2"></span>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-4">
                            <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">سیاست نگهداری و پاک‌سازی خودکار</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="local_retention" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نگهداری در سرور محلی (روز)</label>
                                    <input type="number" name="local_retention" id="local_retention" value="{{ old('local_retention', $settings['local_retention'] ?? 7) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                    <p class="text-xs text-gray-500 mt-1">بکاپ‌های قدیمی‌تر از این تعداد روز از سرور محلی حذف می‌شوند.</p>
                                </div>
                                <div>
                                    <label for="remote_retention" class="block text-sm font-medium text-gray-700 dark:text-gray-300">حداکثر تعداد نگهداری در FTP ریموت</label>
                                    <input type="number" name="remote_retention" id="remote_retention" value="{{ old('remote_retention', $settings['remote_retention'] ?? 2) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">برای جلوگیری از پر شدن حجم هاست بکاپ، فقط ۲ بکاپ آخر نگهداری و فایل‌های قدیمی‌تر حذف می‌شوند.</p>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 text-left">
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700">
                            ذخیره تنظیمات
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Status & History Widget --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">وضعیت و اجرا</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="text-sm">
                        <p class="text-gray-500 dark:text-gray-400">آخرین وضعیت:</p>
                        <p class="font-semibold {{ ($last_backup_status['status'] ?? '') === 'موفق' ? 'text-green-600' : 'text-amber-600' }}">{{ $last_backup_status['status'] ?? 'نامشخص' }}</p>
                    </div>
                    <div class="text-sm">
                        <p class="text-gray-500 dark:text-gray-400">تاریخ آخرین پشتیبان‌گیری:</p>
                        <p class="font-semibold" dir="ltr">{{ $last_backup_status['date'] ?? 'هرگز' }}</p>
                    </div>
                    
                    <form action="{{ route('backup_tasks.db_now', $service->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md shadow-sm text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20">
                            <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m-1.5 1.5h8.25A3.375 3.375 0 0012 9.75h0a3.375 3.375 0 00-3.375 3.375H2.25M12 12.75h8.25" /></svg>
                            دریافت بکاپ دیتابیس (فوری)
                        </button>
                    </form>

                    <form action="{{ route('backup_tasks.files_now', $service->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-indigo-600 text-sm font-medium rounded-md shadow-sm text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                            <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                            دریافت بکاپ فایل‌ها (فوری)
                        </button>
                    </form>
                    
                    <hr class="border-gray-200 dark:border-gray-700">

                    <form action="{{ route('backup_tasks.run', $service->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                            <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" /></svg>
                            اجرای کامل بکاپ و آپلود به FTP
                        </button>
                    </form>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">تاریخچه پشتیبان‌گیری‌ها</h3>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recent_backups as $backup)
                        <li class="p-3 flex justify-between items-center text-sm">
                            <div class="overflow-hidden">
                                <p class="font-mono text-xs text-gray-800 dark:text-gray-200 truncate" dir="ltr">{{ $backup['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $backup['date'] }} - {{ $backup['size'] }}</p>
                            </div>
                            <a href="{{ route('backup_tasks.download', [$service->id, $backup['name']]) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 font-medium bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded text-xs mr-2 whitespace-nowrap">دانلود</a>
                        </li>
                    @empty
                        <li class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            هیچ فایل پشتیبانی یافت نشد.
                        </li>
                    @endforelse
                </ul>
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
    
    if(!host || !user || !pass) {
        resultSpan.textContent = 'لطفاً تمام فیلدهای هاست، نام کاربری و رمز عبور را پر کنید.';
        resultSpan.className = 'text-xs mr-2 text-red-500';
        return;
    }
    
    resultSpan.textContent = 'در حال تست اتصال به سرور FTP...';
    resultSpan.className = 'text-xs mr-2 text-indigo-600 animate-pulse';
    
    fetch('{{ route('backup_tasks.test_ftp') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            remote_host: host,
            remote_user: user,
            remote_password: pass
        })
    })
    .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.message || 'خطای سرور (' + response.status + ')');
        }
        return data;
    })
    .then(data => {
        resultSpan.textContent = data.message;
        resultSpan.className = 'text-xs mr-2 font-bold ' + (data.success ? 'text-green-600' : 'text-red-600');
    })
    .catch(e => {
        resultSpan.textContent = e.message || 'خطا در ارتباط با سرور';
        resultSpan.className = 'text-xs mr-2 font-bold text-red-600';
    });
}
</script>
@endsection
