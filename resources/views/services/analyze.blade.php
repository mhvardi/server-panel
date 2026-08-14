@extends('layouts.app')

@section('title', 'آنالیز سرویس ' . $service->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">آنالیز منابع سرویس: {{ $service->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $service->domain }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3 rtl:space-x-reverse">
            <a href="{{ route('services.show', $service->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                بازگشت به سرویس
            </a>
            <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                بروزرسانی آمار
            </button>
        </div>
    </div>

    <!-- Usage Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Disk Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">حجم مصرفی فایل‌ها</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $diskUsage }}</p>
                </div>
                <div class="p-3 bg-blue-100 text-blue-600 rounded-full dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                مسیر: <span dir="ltr">{{ $service->path }}</span>
            </div>
        </div>

        <!-- Database Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-2 h-full bg-green-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">حجم دیتابیس</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $dbUsage }}</p>
                </div>
                <div class="p-3 bg-green-100 text-green-600 rounded-full dark:bg-green-900/30 dark:text-green-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                نام دیتابیس: <span class="font-semibold text-gray-700 dark:text-gray-300" dir="ltr">{{ $dbName }}</span>
            </div>
        </div>

        <!-- Traffic Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-2 h-full bg-purple-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ترافیک تخمینی (Logs)</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $trafficUsage }}</p>
                </div>
                <div class="p-3 bg-purple-100 text-purple-600 rounded-full dark:bg-purple-900/30 dark:text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                ترافیک محاسبه شده بر اساس مجموع بایت‌های ارسالی
            </div>
        </div>
    </div>

    <!-- Chart / Visual representation -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b pb-2 dark:border-gray-700">توزیع مصرف فضا (دیسک و دیتابیس)</h3>
        @php
            $totalSpace = $diskBytes + $dbBytes;
            $diskPercent = $totalSpace > 0 ? round(($diskBytes / $totalSpace) * 100) : 0;
            $dbPercent = $totalSpace > 0 ? round(($dbBytes / $totalSpace) * 100) : 0;
        @endphp
        
        @if($totalSpace > 0)
        <div class="w-full h-8 flex rounded-full overflow-hidden shadow-inner bg-gray-200 dark:bg-gray-700">
            <div class="h-full bg-blue-500 flex items-center justify-center text-xs text-white font-bold transition-all duration-500" style="width: {{ $diskPercent }}%" title="فایل‌ها: {{ $diskUsage }}">
                @if($diskPercent > 10){{ $diskPercent }}%@endif
            </div>
            <div class="h-full bg-green-500 flex items-center justify-center text-xs text-white font-bold transition-all duration-500" style="width: {{ $dbPercent }}%" title="دیتابیس: {{ $dbUsage }}">
                @if($dbPercent > 10){{ $dbPercent }}%@endif
            </div>
        </div>
        <div class="mt-4 flex space-x-6 rtl:space-x-reverse justify-center text-sm">
            <div class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full mr-2 rtl:ml-2 rtl:mr-0"></span>
                <span class="text-gray-600 dark:text-gray-400">فایل‌ها ({{ $diskUsage }})</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2 rtl:ml-2 rtl:mr-0"></span>
                <span class="text-gray-600 dark:text-gray-400">دیتابیس ({{ $dbUsage }})</span>
            </div>
        </div>
        @else
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            اطلاعاتی برای نمایش وجود ندارد (حجم صفر است).
        </div>
        @endif
    </div>

</div>
@endsection
