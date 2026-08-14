@extends('layouts.app')

@section('title', 'وظایف زمان‌بندی شده')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">وظایف زمان‌بندی شده (Cron Jobs)</h1>
        <a href="{{ route('cronjobs.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>ایجاد وظیفه جدید</span>
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
            <pre class="mb-0">{{ session('error') }}</pre>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Config Info --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">پیکربندی Cron</h3>
                    @if($config['can_write'] ?? false)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">قابل نوشتن</span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">غیرقابل نوشتن</span>
                    @endif
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">فایل Cron:</p>
                        <code class="block bg-gray-100 dark:bg-gray-700 p-2 rounded-md text-xs text-gray-800 dark:text-gray-200 break-all" dir="ltr">{{ $config['cron_file'] ?? 'نامشخص' }}</code>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">کاربر پیش‌فرض اجرا:</p>
                        <code class="block bg-gray-100 dark:bg-gray-700 p-2 rounded-md text-xs text-gray-800 dark:text-gray-200" dir="ltr">{{ $config['default_run_as'] ?? 'نامشخص' }}</code>
                    </div>
                    @if(!($config['can_write'] ?? false))
                        <div class="p-3 text-xs text-yellow-800 bg-yellow-100 rounded-lg dark:bg-yellow-900/50 dark:text-yellow-300">
                            برای فعال کردن قابلیت نوشتن، کاربر وب باید دسترسی sudo بدون نیاز به رمز عبور برای دستورات <code>tee</code> و <code>chmod</code> داشته باشد.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Jobs List --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">لیست وظایف</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">وضعیت</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">نام</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">زمان‌بندی</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">دستور</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($jobs as $job)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($job['enabled'])
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">فعال</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">غیرفعال</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">{{ $job['name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 font-mono">{{ $job['schedule'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 p-1 rounded" dir="ltr">{{ Str::limit($job['command'], 40) }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('cronjobs.edit', $job['id']) }}" class="text-indigo-600 hover:text-indigo-900 p-1">ویرایش</a>
                                            <form action="{{ route('cronjobs.toggle', $job['id']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-gray-500 hover:text-gray-700 p-1">{{ $job['enabled'] ? 'غیرفعال' : 'فعال' }}</button>
                                            </form>
                                            <form action="{{ route('cronjobs.destroy', $job['id']) }}" method="POST" onsubmit="return confirm('آیا از حذف این وظیفه اطمینان دارید؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 p-1">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        هیچ وظیفه‌ای یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
