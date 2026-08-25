<?php
$file = 'resources/views/backup_tasks/settings.blade.php';
$content = file_get_contents($file);

$search = <<<HTML
                        <div x-show="include_db">
                            <label for="db_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده (Database Name)</label>
                            <input type="text" name="db_name" id="db_name" value="{{ old('db_name', \$settings['db_name'] ?? '') }}" class="mt-1 block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 text-left" dir="ltr">
                        </div>
HTML;

$replace = <<<HTML
                        <div x-show="include_db">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده (شناسایی خودکار از .env)</label>
                            <div class="mt-1 flex items-center">
                                <input type="text" disabled value="{{ \$service->getDatabaseName() ?? 'یافت نشد' }}" class="block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-600 dark:border-gray-500 text-gray-500 text-left" dir="ltr">
                                @if(!\$service->getDatabaseName())
                                    <span class="mr-3 text-xs text-red-500 font-bold">⚠️ در فایل env یافت نشد!</span>
                                @else
                                    <span class="mr-3 text-xs text-green-500 font-bold">✅ متصل به env</span>
                                @endif
                            </div>
                        </div>
HTML;

$newContent = str_replace($search, $replace, $content);
file_put_contents($file, $newContent);
echo "Done";
