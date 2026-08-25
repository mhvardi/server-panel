@extends('layouts.app')

@section('title', 'مدیریت پایگاه‌داده')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6" x-data="databaseManager()">
        
        {{-- Toast Notification --}}
        <div x-show="toast.show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-5 left-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-sm font-medium border"
             :class="toast.type === 'success' ? 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20' : 'bg-red-600 text-white border-red-500 shadow-red-500/20'"
             style="display: none;">
            <svg x-show="toast.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <svg x-show="toast.type !== 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-800 dark:text-white">مدیریت پایگاه‌داده</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">مشاهده و مدیریت ارتباطات دیتابیس هر سرویس از روی .env و پایگاه‌های داده سرور</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ $phpmyadminBaseUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 rounded-2xl text-xs font-bold transition border border-amber-200/60 dark:border-amber-800/40">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>باز کردن phpMyAdmin مرکزی</span>
                </a>
                <button type="button" @click="activeTab = 'create'" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>ایجاد دیتابیس و کاربر جدید</span>
                </button>
            </div>
        </div>

        {{-- Server Connection Advisory (If Root MySQL has limited access) --}}
        @if($dbServerError)
            <div class="p-4 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-3">
                <div class="p-1 bg-amber-100 dark:bg-amber-900/50 rounded-lg text-amber-700 dark:text-amber-300 flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1 space-y-1">
                    <p class="font-bold text-amber-800 dark:text-amber-200">وضعیت دسترسی مدیر سرور MySQL:</p>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                        دسترسی مستقیم کاربر root سرور با خطای زیر مواجه شد، اما <strong>دیتابیس هر سرویس از روی فایل .env اختصاصی آن کاملاً در دسترس است</strong>. برای فعال شدن تب پایگاه‌های داده سراسری، متغیرهای <code class="bg-amber-100/80 dark:bg-amber-900/60 px-1.5 py-0.5 rounded font-mono">MYSQL_ROOT_USERNAME</code> و <code class="bg-amber-100/80 dark:bg-amber-900/60 px-1.5 py-0.5 rounded font-mono">MYSQL_ROOT_PASSWORD</code> را در فایل <code class="bg-amber-100/80 dark:bg-amber-900/60 px-1.5 py-0.5 rounded font-mono">.env</code> تنظیم فرمایید.
                    </p>
                    <p class="text-[11px] text-amber-700/80 dark:text-amber-400 font-mono dir-ltr text-right">{{ $dbServerError }}</p>
                </div>
            </div>
        @endif

        {{-- Global Validation Errors & Success Alerts --}}
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-xs">
                <p class="font-bold mb-1">خطا در پردازش اطلاعات:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 block">سرویس‌های متصل</span>
                    <span class="text-xl font-black text-slate-800 dark:text-white">{{ $stats['connected_services'] }} <span class="text-xs font-normal text-slate-400">از {{ $stats['total_services'] }}</span></span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 block">تعداد پایگاه‌های داده</span>
                    <span class="text-xl font-black text-slate-800 dark:text-white">{{ $stats['total_databases'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 block">حجم تخمینی داده‌ها</span>
                    <span class="text-xl font-black text-slate-800 dark:text-white">{{ number_format($stats['total_size_mb'], 2) }} <span class="text-xs font-normal text-slate-400">MB</span></span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 block">کاربران دیتابیس</span>
                    <span class="text-xl font-black text-slate-800 dark:text-white">{{ $stats['total_users'] }}</span>
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700/60 overflow-x-auto pb-1">
            <button type="button" 
                    @click="activeTab = 'services'"
                    class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all whitespace-nowrap"
                    :class="activeTab === 'services' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                <span>دیتابیس سرویس‌ها و پروژه‌ها</span>
                <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'services' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                    {{ count($serviceDatabases) }}
                </span>
            </button>

            <button type="button" 
                    @click="activeTab = 'all_databases'"
                    class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all whitespace-nowrap"
                    :class="activeTab === 'all_databases' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                <span>تمام پایگاه‌های داده سرور</span>
                <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'all_databases' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                    {{ count($databases) }}
                </span>
            </button>

            <button type="button" 
                    @click="activeTab = 'users'"
                    class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all whitespace-nowrap"
                    :class="activeTab === 'users' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>کاربران پایگاه‌داده</span>
                <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'users' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                    {{ count($users) }}
                </span>
            </button>

            <button type="button" 
                    @click="activeTab = 'create'"
                    class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all whitespace-nowrap"
                    :class="activeTab === 'create' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>ایجاد سریع دیتابیس</span>
            </button>
        </div>

        {{-- TAB 1: Service Databases --}}
        <div x-show="activeTab === 'services'" class="space-y-4">
            <div class="mb-4" x-show="{{ count($serviceDatabases) }} > 0">
                <div class="relative max-w-sm">
                    <input type="text" x-model="searchService" placeholder="جستجوی سرویس یا دیتابیس..." class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-rtl pr-10">
                    <svg class="absolute inset-y-0 right-0 mr-3 mt-3 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            @if(count($serviceDatabases) > 0)
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    @foreach($serviceDatabases as $item)
                        @php
                            $srv = $item['service'];
                            $cfg = $item['config'];
                            $hasDb = !empty($cfg['database']);
                        @endphp
                        <div x-show="searchService === '' || $el.innerText.toLowerCase().includes(searchService.toLowerCase())" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm p-5 space-y-4 flex flex-col justify-between hover:border-indigo-200 dark:hover:border-indigo-800/50 transition">
                            
                            {{-- Card Top: Service Header & Status --}}
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                            {{ mb_substr($srv->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('services.show', $srv->id) }}" class="text-base font-black text-slate-800 dark:text-white hover:text-indigo-600 transition flex items-center gap-1.5">
                                                <span>{{ $srv->name }}</span>
                                                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[11px] font-mono text-slate-400 dir-ltr text-right">{{ $srv->domain }}</span>
                                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                                <span class="text-[10px] text-slate-400 truncate max-w-[180px]" title="{{ $srv->path }}">{{ basename($srv->path) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Connection status badge --}}
                                    @if($cfg['is_connected'])
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>متصل و فعال</span>
                                        </span>
                                    @elseif($hasDb)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40" title="{{ $cfg['status_message'] }}">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>نیاز به بررسی</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                            <span>بدون دیتابیس</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Database Details Grid --}}
                                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-800 space-y-2.5 text-xs">
                                    <div class="grid grid-cols-2 gap-3 pb-2.5 border-b border-slate-200/60 dark:border-slate-800">
                                        <div>
                                            <span class="text-slate-400 block text-[11px] font-medium">نام پایگاه‌داده (DB_DATABASE)</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <strong class="text-slate-800 dark:text-white font-mono text-sm font-bold">
                                                    {{ $cfg['database'] ?: 'تنظیم نشده' }}
                                                </strong>
                                                @if($hasDb)
                                                    <button type="button" @click="copyText('{{ $cfg['database'] }}', 'نام دیتابیس کپی شد')" class="text-slate-400 hover:text-indigo-600" title="کپی نام دیتابیس">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 block text-[11px] font-medium">حجم و جداول</span>
                                            <div class="flex items-center gap-2 mt-1 font-bold text-slate-700 dark:text-slate-200">
                                                <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 rounded-lg text-[11px] font-mono">
                                                    {{ $cfg['tables_count'] }} جدول
                                                </span>
                                                <span class="text-slate-400">•</span>
                                                <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                                    {{ number_format($cfg['size_mb'], 2) }} MB
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                                        <div>
                                            <span class="text-slate-400 block text-[11px] font-medium">نام کاربری و هاست</span>
                                            <span class="font-mono text-slate-800 dark:text-slate-200 mt-0.5 block dir-ltr text-right">
                                                {{ $cfg['username'] ?: '-' }} @ {{ $cfg['host'] }}:{{ $cfg['port'] }}
                                            </span>
                                        </div>

                                        {{-- Password with Eye Toggle and Copy --}}
                                        <div x-data="{ showPass: false }">
                                            <span class="text-slate-400 block text-[11px] font-medium">رمز عبور (.env)</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <div class="font-mono text-xs text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 flex-1 dir-ltr text-right flex items-center justify-between">
                                                    <span x-show="!showPass" class="tracking-widest text-slate-400 select-none">••••••••</span>
                                                    <span x-show="showPass" style="display: none;" class="select-all">{{ $cfg['password'] ?: '(خالی)' }}</span>
                                                    
                                                    <button type="button" @click="showPass = !showPass" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 ml-1">
                                                        <svg x-show="!showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                        <svg x-show="showPass" style="display: none;" class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.5-2.5m1.5-1.5A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.39 2.06m-1.61 1.44c-.75.75-1.65 1.34-2.65 1.7M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" /></svg>
                                                    </button>
                                                </div>
                                                @if(!empty($cfg['password']))
                                                    <button type="button" @click="copyText('{{ base64_encode($cfg['password']) }}', 'رمز عبور دیتابیس کپی شد', true)" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition" title="کپی رمز عبور">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card Footer: Action Buttons --}}
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    {{-- Quick phpMyAdmin Button --}}
                                    @if($hasDb)
                                        <button type="button" 
                                                @click="openPma('{{ $item['pma_url'] }}', '{{ $cfg['username'] }}', '{{ base64_encode($cfg['password']) }}', '{{ $cfg['database'] }}')" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition border border-amber-200/50 dark:border-amber-800/40">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            <span>phpMyAdmin</span>
                                        </button>

                                        {{-- View Database Tables Details --}}
                                        <a href="{{ route('databases.show', $cfg['database']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <span>جداول</span>
                                        </a>

                                        {{-- Download SQL Backup --}}
                                        <a href="{{ route('databases.service-backup', $srv->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition" title="دانلود بکاپ مستقیم SQL">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            <span>بکاپ SQL</span>
                                        </a>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5">
                                    {{-- Test Connection Button --}}
                                    @if($hasDb)
                                        <button type="button" 
                                                @click="testServiceDb({{ $srv->id }}, $event)" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition" 
                                                title="تست ارتباط زنده با MySQL">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            <span>تست اتصال</span>
                                        </button>
                                    @endif

                                    {{-- Copy Full .env Snippet --}}
                                    @php
                                        $envSnippet = "DB_CONNECTION={$cfg['connection']}
DB_HOST={$cfg['host']}
DB_PORT={$cfg['port']}
DB_DATABASE={$cfg['database']}
DB_USERNAME={$cfg['username']}
DB_PASSWORD={$cfg['password']}";
                                    @endphp
                                    <button type="button" 
                                            @click="copyText('{{ base64_encode($envSnippet) }}', 'تنظیمات .env دیتابیس کپی شد', true)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-medium text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                            title="کپی بلوک .env">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                        <span>کپی .env</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60">
                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300">هیچ پروژه‌ای ایجاد نشده است</h3>
                    <p class="text-xs text-slate-400 mt-1">با ایجاد سرویس جدید، پایگاه‌های داده و مشخصات .env آن در این بخش لیست می‌شوند.</p>
                </div>
            @endif
        </div>

        {{-- TAB 2: All Databases Table --}}
        <div x-show="activeTab === 'all_databases'" style="display: none;" class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">فهرست کامل پایگاه‌های داده روی MySQL</h3>
                        <p class="text-xs text-slate-400 mt-0.5">شامل تمام دیتابیس‌های مجزا و متصل به پروژه‌ها</p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <input type="text" x-model="searchDb" placeholder="جستجوی نام دیتابیس..." class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 text-xs p-2.5 font-mono pr-9">
                        <svg class="absolute inset-y-0 right-0 mr-2.5 mt-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700/60">
                        <thead class="bg-slate-50/75 dark:bg-slate-900/40 text-slate-400 text-[11px] font-black uppercase">
                            <tr>
                                <th class="px-6 py-3.5 text-right">نام پایگاه‌داده</th>
                                <th class="px-6 py-3.5 text-right">حجم</th>
                                <th class="px-6 py-3.5 text-right">تعداد جداول</th>
                                <th class="px-6 py-3.5 text-right">انکودینگ (Charset)</th>
                                <th class="px-6 py-3.5 text-left">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 text-xs">
                            @forelse($databases as $db)
                                <tr x-show="searchDb === '' || $el.innerText.toLowerCase().includes(searchDb.toLowerCase())" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                                            <span>{{ $db['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600 dark:text-slate-300">
                                        {{ number_format($db['size'], 2) }} MB
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full font-mono text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                            {{ $db['tables'] }} جدول
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-500 dark:text-slate-400">
                                        {{ $db['charset'] }}
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('databases.show', $db['name']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 font-bold transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <span>مشاهده جداول</span>
                                            </a>
                                            <a href="{{ \App\Http\Controllers\DatabaseController::getPhpMyAdminUrl($db['name']) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 font-bold transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                <span>phpMyAdmin</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                        پایگاه داده‌ای در سرور یافت نشد یا دسترسی root پیکربندی نشده است.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 3: Database Users --}}
        <div x-show="activeTab === 'users'" style="display: none;" class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">فهرست کاربران پایگاه‌داده</h3>
                        <p class="text-xs text-slate-400 mt-0.5">مدیریت کاربران، تغییر رمز عبور و مجوزهای دسترسی</p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <input type="text" x-model="searchUser" placeholder="جستجوی نام کاربری..." class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 text-xs p-2.5 font-mono pr-9">
                        <svg class="absolute inset-y-0 right-0 mr-2.5 mt-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700/60">
                        <thead class="bg-slate-50/75 dark:bg-slate-900/40 text-slate-400 text-[11px] font-black uppercase">
                            <tr>
                                <th class="px-6 py-3.5 text-right">نام کاربری</th>
                                <th class="px-6 py-3.5 text-right">هاست مجاز (Host)</th>
                                <th class="px-6 py-3.5 text-right">تعداد دیتابیس‌های متصل</th>
                                <th class="px-6 py-3.5 text-left">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 text-xs">
                            @forelse($users as $user)
                                <tr x-show="searchUser === '' || $el.innerText.toLowerCase().includes(searchUser.toLowerCase())" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span>{{ $user['username'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600 dark:text-slate-300">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 font-mono text-[11px]">
                                            {{ $user['host'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full font-mono text-[11px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                            {{ $user['database_count'] ?? 0 }} دیتابیس
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Change Password Modal Trigger --}}
                                            <button type="button" 
                                                    @click="openChangePassModal('{{ $user['username'] }}', '{{ $user['host'] }}')" 
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 font-bold transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                                <span>تغییر رمز</span>
                                            </button>

                                            {{-- Delete User --}}
                                            <form action="{{ route('databases.user.delete') }}" method="POST" class="inline" onsubmit="return confirm('آیا از حذف این کاربر اطمینان دارید؟');">
                                                @csrf
                                                <input type="hidden" name="username" value="{{ $user['username'] }}">
                                                <input type="hidden" name="host" value="{{ $user['host'] }}">
                                                <button type="submit" class="p-1.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 transition" title="حذف کاربر">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                        کاربری در سرور یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 4: Create Database & User Wizard --}}
        <div x-show="activeTab === 'create'" style="display: none;" class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm p-6 max-w-2xl mx-auto space-y-6">
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white">ایجاد سریع پایگاه‌داده و کاربر</h3>
                    <p class="text-xs text-slate-400 mt-1">با تکمیل این فرم، دیتابیس و کاربر به طور همزمان ساخته شده و دسترسی کامل به یکدیگر متصل می‌شوند.</p>
                </div>

                <form action="{{ route('databases.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="create_user" value="1">

                    <div>
                        <label for="create_db_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">نام پایگاه‌داده (Database Name)</label>
                        <input type="text" name="name" id="create_db_name" required pattern="[a-zA-Z0-9_]+" value="{{ old('name') }}" placeholder="example_db" class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="create_username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">نام کاربری (Username)</label>
                            <input type="text" name="username" id="create_username" required pattern="[a-zA-Z0-9_]+" value="{{ old('username') }}" placeholder="example_user" class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                        </div>
                        <div>
                            <label for="create_host" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">هاست مجاز (Host)</label>
                            <select name="host" id="create_host" class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                                <option value="localhost" selected>localhost</option>
                                <option value="%">% (تمام هاست‌ها)</option>
                                <option value="127.0.0.1">127.0.0.1</option>
                            </select>
                        </div>
                    </div>

                    <div x-data="{ showPass: false, pass: '' }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="create_pass" class="block text-xs font-bold text-slate-700 dark:text-slate-300">رمز عبور کاربر</label>
                            <button type="button" @click="pass = generatePassword(); showPass = true" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>تولید رمز عبور قوی</span>
                            </button>
                        </div>

                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" id="create_pass" x-model="pass" required class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right pl-10">
                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showPass" style="display: none;" class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.5-2.5m1.5-1.5A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.39 2.06m-1.61 1.44c-.75.75-1.65 1.34-2.65 1.7M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3">
                        <button type="button" @click="activeTab = 'services'" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            انصراف
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition">
                            ایجاد دیتابیس و اتصال کاربر
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Change User Password --}}
        <div x-show="changePassModal.open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 p-6">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="changePassModal.open = false"></div>
                
                <div class="relative bg-white dark:bg-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 dark:border-slate-700 text-right space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">
                            تغییر رمز عبور: <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="changePassModal.username"></span>
                        </h3>
                        <button type="button" @click="changePassModal.open = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitChangePass" class="space-y-4">
                        <div x-data="{ showPass: false }">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="modal_new_pass" class="block text-xs font-bold text-slate-700 dark:text-slate-300">رمز عبور جدید</label>
                                <button type="button" @click="changePassModal.password = generatePassword(); changePassModal.password_confirmation = changePassModal.password; showPass = true" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                    تولید خودکار رمز
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showPass ? 'text' : 'password'" id="modal_new_pass" x-model="changePassModal.password" required class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right pl-10">
                                <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPass" style="display: none;" class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.5-2.5m1.5-1.5A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.39 2.06m-1.61 1.44c-.75.75-1.65 1.34-2.65 1.7M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="modal_confirm_pass" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">تکرار رمز عبور جدید</label>
                            <input type="password" id="modal_confirm_pass" x-model="changePassModal.password_confirmation" required class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                        </div>

                        {{-- Backup Checkbox --}}
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <input type="checkbox" id="modal_backup" x-model="changePassModal.take_backup" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <label for="modal_backup" class="text-xs font-bold text-slate-600 dark:text-slate-300">ابتدا از تمام دیتابیس‌های متصل به این کاربر بکاپ بگیر</label>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3">
                            <button type="button" @click="changePassModal.open = false" class="px-4 py-2 rounded-2xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition">انصراف</button>
                            <button type="submit" :disabled="changePassModal.loading" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition flex items-center gap-2">
                                <svg x-show="changePassModal.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>ذخیره رمز جدید</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- Hidden form for phpMyAdmin Auto-Login --}}
    <form id="pmaLoginForm" method="POST" target="_blank" style="display: none;">
        <input type="hidden" name="pma_username" id="pma_user">
        <input type="hidden" name="pma_password" id="pma_pass">
        <input type="hidden" name="server" value="1">
        <input type="hidden" name="target" id="pma_target">
        <input type="hidden" name="route" id="pma_route">
        <input type="hidden" name="db" id="pma_db">
    </form>

    {{-- JavaScript & Alpine.js Helpers --}}
    <script>
        function databaseManager() {
            return {
                activeTab: 'services',
                searchService: '',
                searchDb: '',
                searchUser: '',
                toast: { show: false, message: '', type: 'success' },
                changePassModal: {
                    open: false,
                    loading: false,
                    username: '',
                    host: '',
                    password: '',
                    password_confirmation: '',
                    take_backup: false
                },
                
                showToast(message, type = 'success') {
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3000);
                },

                copyText(text, message, isBase64 = false) {
                    try {
                        const finalString = isBase64 ? atob(text) : text;
                        navigator.clipboard.writeText(finalString).then(() => {
                            this.showToast(message, 'success');
                        }).catch(err => {
                            this.showToast('خطا در کپی کردن متن', 'error');
                        });
                    } catch (e) {
                        this.showToast('خطا در رمزگشایی متن', 'error');
                    }
                },

                openPma(url, username, b64Password, db) {
                    if (b64Password && username) {
                        const password = atob(b64Password);
                        
                        // Auto copy password to clipboard as an instant fallback
                        try {
                            navigator.clipboard.writeText(password);
                        } catch(e) {}

                        const form = document.getElementById('pmaLoginForm');
                        
                        let actionUrl = url.split('?')[0];
                        if(!actionUrl.endsWith('/index.php')) {
                            actionUrl = actionUrl.replace(/\/+$/, '') + '/index.php';
                        }
                        
                        form.action = actionUrl;
                        document.getElementById('pma_user').value = username;
                        document.getElementById('pma_pass').value = password;
                        document.getElementById('pma_db').value = db || '';
                        document.getElementById('pma_target').value = db ? `index.php?route=/database/structure&db=${encodeURIComponent(db)}` : 'index.php';
                        document.getElementById('pma_route').value = db ? '/database/structure' : '/';
                        
                        this.showToast('ورود خودکار به phpMyAdmin با کاربر ' + username + ' (رمز عبور در کلیپ‌بورد نیز کپی شد)', 'success');
                        form.submit();
                    } else {
                        window.open(url, '_blank');
                    }
                },

                async testServiceDb(serviceId, event) {
                    const btn = event.currentTarget;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>تست...</span>';
                    btn.disabled = true;
                    
                    try {
                        const res = await fetch('{{ route("databases.test-connection") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ service_id: serviceId })
                        });
                        const data = await res.json();
                        if (data.status === 'success' || data.success) {
                            this.showToast(data.message, 'success');
                        } else {
                            this.showToast(data.message, 'error');
                        }
                    } catch(e) {
                        this.showToast('خطا در برقراری ارتباط با سرور', 'error');
                    }
                    
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                },

                openChangePassModal(username, host) {
                    this.changePassModal.username = username;
                    this.changePassModal.host = host;
                    this.changePassModal.password = '';
                    this.changePassModal.password_confirmation = '';
                    this.changePassModal.take_backup = false;
                    this.changePassModal.open = true;
                },

                async submitChangePass() {
                    if (this.changePassModal.password !== this.changePassModal.password_confirmation) {
                        this.showToast('رمز عبور و تکرار آن یکسان نیست', 'error');
                        return;
                    }
                    this.changePassModal.loading = true;
                    try {
                        const res = await fetch('{{ route("databases.user.password.ajax") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                username: this.changePassModal.username,
                                host: this.changePassModal.host,
                                password: this.changePassModal.password,
                                password_confirmation: this.changePassModal.password_confirmation,
                                take_backup: this.changePassModal.take_backup
                            })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.showToast(data.message, 'success');
                            this.changePassModal.open = false;
                        } else {
                            this.showToast(data.message, 'error');
                        }
                    } catch(e) {
                        this.showToast('خطا در برقراری ارتباط', 'error');
                    }
                    this.changePassModal.loading = false;
                },

                generatePassword() {
                    const u = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                    const l = "abcdefghijklmnopqrstuvwxyz";
                    const n = "0123456789";
                    const s = "!@#$%^&*()_+=-";
                    const all = u + l + n + s;
                    let p = [
                        u[Math.floor(Math.random() * u.length)],
                        l[Math.floor(Math.random() * l.length)],
                        n[Math.floor(Math.random() * n.length)],
                        s[Math.floor(Math.random() * s.length)]
                    ];
                    for (let i = 0; i < 12; i++) {
                        p.push(all[Math.floor(Math.random() * all.length)]);
                    }
                    for (let i = p.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [p[i], p[j]] = [p[j], p[i]];
                    }
                    return p.join('');
                }
            }
        }
    </script>
@endsection
