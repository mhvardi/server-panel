@php
    $job = $job ?? null;
    $defaultRunAs = $config['default_run_as'] ?? 'www-data';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Name Field -->
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نام وظیفه</label>
        <input type="text" id="name" name="name" value="{{ old('name', $job['name'] ?? '') }}" placeholder="مثال: Laravel Scheduler, Backup, Queue Worker" required
               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-white dark:focus:ring-indigo-600 dark:focus:border-indigo-600">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">یک نام خوانا برای پیدا کردن راحت‌تر این وظیفه در لیست.</p>
    </div>

    <!-- Preset Dropdown -->
    <div>
        <label for="cron_preset" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">قالب‌های آماده (Preset)</label>
        <select id="cron_preset" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-white" dir="ltr">
            <option value="">-- سفارشی (Custom) --</option>
            <option value="* * * * *">هر دقیقه (* * * * *)</option>
            <option value="*/5 * * * *">هر ۵ دقیقه (*/5 * * * *)</option>
            <option value="*/10 * * * *">هر ۱۰ دقیقه (*/10 * * * *)</option>
            <option value="0 * * * *">ساعتی (0 * * * *)</option>
            <option value="0 0 * * *">روزانه، نیمه‌شب (0 0 * * *)</option>
            <option value="0 0 * * 0">هفتگی، یکشنبه‌ها (0 0 * * 0)</option>
            <option value="0 0 1 * *">ماهانه، روز اول (0 0 1 * *)</option>
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">انتخاب یک قالب، فیلد زمان‌بندی را به‌طور خودکار پر می‌کند.</p>
    </div>

    <!-- Cron Expression -->
    <div>
        <label for="cron_schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عبارت زمان‌بندی (Cron Expression)</label>
        <div class="relative rounded-md shadow-sm">
            <input type="text" id="cron_schedule" name="schedule" value="{{ old('schedule', $job['schedule'] ?? '*/5 * * * *') }}" required dir="ltr"
                   class="block w-full rounded-md border-gray-300 pl-4 pr-10 font-mono text-left focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-white">
        </div>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">قالب ۵ بخشی: <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded" dir="ltr">min hour day month weekday</code></p>
    </div>

    <!-- Run As User -->
    <div>
        <label for="run_as" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">کاربر اجراکننده (Linux User)</label>
        <input type="text" id="run_as" name="run_as" value="{{ old('run_as', $job['run_as'] ?? $defaultRunAs) }}" placeholder="{{ $defaultRunAs }}" dir="ltr"
               class="block w-full rounded-md border-gray-300 font-mono text-left shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-white">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">پیش‌فرض: <code class="font-mono">{{ $defaultRunAs }}</code>. این کاربر باید دسترسی لازم به دستور را داشته باشد.</p>
    </div>

    <!-- Enabled Toggle -->
    <div class="flex items-center mt-2 md:mt-6">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="enabled" name="enabled" type="checkbox" value="1" {{ old('enabled', ($job['enabled'] ?? true)) ? 'checked' : '' }}
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-900 dark:border-gray-600 dark:checked:bg-indigo-500">
            </div>
            <div class="mr-3 text-sm">
                <label for="enabled" class="font-medium text-gray-700 dark:text-gray-300">فعال بودن وظیفه</label>
                <p class="text-gray-500 dark:text-gray-400 text-xs">وظایف غیرفعال در فایل ذخیره می‌شوند اما اجرا نخواهند شد.</p>
            </div>
        </div>
    </div>

    <!-- Command -->
    <div class="md:col-span-2">
        <label for="cron_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">دستور (Command)</label>
        <textarea id="cron_command" name="command" rows="3" required dir="ltr" placeholder="e.g. /usr/bin/php /var/www/yourapp/artisan schedule:run"
                  class="block w-full rounded-md border-gray-300 font-mono text-left text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white">{{ old('command', $job['command'] ?? '') }}</textarea>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-right">
            دستور باید در یک خط نوشته شود. برای ذخیره لاگ، می‌توانید عبارت <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-gray-800 dark:text-gray-200" dir="ltr">>> /var/log/your.log 2>&1</code> را به انتها اضافه کنید.
        </p>
    </div>

    <!-- Common Examples Info Box -->
    <div class="md:col-span-2">
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                مثال‌های پرکاربرد
            </h4>
            <div class="space-y-2 text-xs font-mono text-left" dir="ltr">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-24 text-blue-600 dark:text-blue-400 font-sans font-medium text-right sm:text-left">Laravel Schedule:</span>
                    <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded border border-blue-100 dark:border-gray-700 flex-1 break-all">*/5 * * * * /usr/bin/php /var/www/app/artisan schedule:run</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-24 text-blue-600 dark:text-blue-400 font-sans font-medium text-right sm:text-left">Bash Script:</span>
                    <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded border border-blue-100 dark:border-gray-700 flex-1 break-all">0 2 * * * /usr/bin/bash /root/backup.sh >> /var/log/backup.log 2>&1</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-24 text-blue-600 dark:text-blue-400 font-sans font-medium text-right sm:text-left">Queue Worker:</span>
                    <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded border border-blue-100 dark:border-gray-700 flex-1 break-all">*/1 * * * * /usr/bin/php /var/www/app/artisan queue:work --sleep=3 --tries=3</code>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const preset = document.getElementById('cron_preset');
            const schedule = document.getElementById('cron_schedule');

            if (preset && schedule) {
                preset.addEventListener('change', function() {
                    if (this.value) {
                        schedule.value = this.value;
                    }
                });
            }
        });
    </script>
@endpush