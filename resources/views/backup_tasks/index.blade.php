@extends('layouts.app')

@section('title', 'مدیریت پشتیبان‌گیری هوشمند')

@section('content')
<div class="space-y-6">
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
                <span class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                </span>
                مدیریت وظایف پشتیبان‌گیری
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">سیستم پشتیبان‌گیری خودکار و هوشمند با تفکیک پایگاه‌داده و سورس پروژه (Local & Remote FTP)</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 rounded-2xl border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 rounded-2xl border border-red-200 dark:bg-red-950/40 dark:border-red-800/60 dark:text-red-300 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Quick Overview Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-4 border border-gray-200/80 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">تعداد سرویس‌ها</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ count($services) }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-4 border border-gray-200/80 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">بکاپ دیتابیس فعال</p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $services->where('db_enabled', true)->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <span class="text-lg">🗄️</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-4 border border-gray-200/80 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">بکاپ سورس فعال</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $services->where('files_enabled', true)->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <span class="text-lg">📁</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/90 rounded-2xl p-4 border border-gray-200/80 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">متصل به FTP مرکزی</p>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $services->where('remote_enabled', true)->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white dark:bg-gray-800/90 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-700/60 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">لیست سرویس‌ها و وضعیت پشتیبان‌گیری</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">تنظیمات و برنامه زمانی مجزای هر سایت را از ستون عملیات مدیریت کنید.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                <thead class="bg-gray-50/75 dark:bg-gray-900/40 text-xs font-semibold text-gray-500 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3.5 px-6 text-right">سرویس و دامنه</th>
                        <th scope="col" class="py-3.5 px-4 text-center">نوع</th>
                        <th scope="col" class="py-3.5 px-4 text-right">وضعیت بکاپ خودکار</th>
                        <th scope="col" class="py-3.5 px-4 text-center">محل ذخیره‌سازی</th>
                        <th scope="col" class="py-3.5 px-6 text-right">آخرین وضعیت اجرا</th>
                        <th scope="col" class="py-3.5 px-6 text-left">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40 text-sm">
                    @forelse ($services as $service)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-750/50 transition-colors duration-150">
                            <!-- Service Name & Domain -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-sm uppercase flex-shrink-0">
                                        {{ substr($service->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white hover:text-indigo-600 transition">{{ $service->name }}</div>
                                        <div class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5" dir="ltr">{{ $service->domain }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $service->type == 'main' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $service->type == 'main' ? 'اصلی' : 'ساب‌دامین' }}
                                </span>
                            </td>

                            <!-- Detailed Status -->
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $service->db_enabled ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                        <span class="text-xs font-medium {{ $service->db_enabled ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400' }}">
                                            دیتابیس: {{ $service->db_enabled ? 'فعال' : 'غیرفعال' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $service->files_enabled ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                        <span class="text-xs font-medium {{ $service->files_enabled ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400' }}">
                                            فایل‌ها: {{ $service->files_enabled ? 'فعال' : 'غیرفعال' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Storage Destinations -->
                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex items-center gap-1 bg-gray-50 dark:bg-gray-800/80 p-1 rounded-xl border border-gray-200/60 dark:border-gray-700/60">
                                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-lg {{ $service->local_enabled ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : 'text-gray-400 opacity-50' }}">
                                        Local
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-lg {{ $service->remote_enabled ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-400 opacity-50' }}">
                                        FTP
                                    </span>
                                </div>
                            </td>

                            <!-- Last Execution -->
                            <td class="py-4 px-6 text-right">
                                @if ($service->last_backup)
                                    <div class="font-mono text-xs font-bold text-gray-800 dark:text-gray-200" dir="ltr">
                                        {{ $service->last_backup->format('Y-m-d H:i') }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $service->last_backup_status == 'موفق' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/50' : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 border border-red-200/50' }}">
                                            {{ $service->last_backup_status }}
                                        </span>
                                        @if($service->last_backup_size)
                                            <span class="text-[10px] text-gray-400 font-mono">({{ $service->last_backup_size }} MB)</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">بدون سابقه</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-left">
                                <a href="{{ route('backup_tasks.settings', $service->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 rounded-xl transition duration-150 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    تنظیمات و اجرا
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-6 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">هیچ سرویسی یافت نشد</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ابتدا از منوی سرویس‌ها، یک سرویس ایجاد نمایید.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
