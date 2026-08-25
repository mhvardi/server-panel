@extends('layouts.app')

@section('title', 'مدیریت پشتیبان‌گیری')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">مدیریت پشتیبان‌گیری</h1>
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $service)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col border border-gray-100 dark:border-gray-700">
                <div class="p-5 flex-1">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" title="{{ $service->name }}">{{ $service->name }}</h3>
                        
                        @if($service->backup_enabled)
                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400 whitespace-nowrap border border-green-200 dark:border-green-800">
                                فعال
                            </span>
                        @else
                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 whitespace-nowrap border border-red-200 dark:border-red-800">
                                غیرفعال
                            </span>
                        @endif
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400">آخرین پشتیبان‌گیری:</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200" dir="ltr">
                                {{ $service->last_backup ? (is_object($service->last_backup) ? $service->last_backup->format('Y-m-d H:i') : $service->last_backup) : 'هرگز' }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400">دامنه:</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200 truncate ml-2" dir="ltr">
                                {{ $service->domain }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                    <a href="{{ route('backup_tasks.settings', $service->id) }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-indigo-600 shadow-sm text-sm font-medium rounded-lg text-indigo-600 bg-white hover:bg-indigo-50 dark:bg-gray-800 dark:border-indigo-500 dark:text-indigo-400 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        تنظیمات پشتیبان‌گیری
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">سرویسی برای پشتیبان‌گیری یافت نشد</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ابتدا یک سرویس جدید در بخش مدیریت سرویس‌ها اضافه کنید.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
