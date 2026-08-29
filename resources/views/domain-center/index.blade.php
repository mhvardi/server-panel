@extends('layouts.app')

@section('title', 'مرکز دامنه')

@section('content')
<div class="space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">مرکز دامنه</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">مدیریت متمرکز تمام دامنه‌های متصل به سرور</p>
        </div>
        <a href="{{ route('domain-center.connect') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            اتصال دامنه جدید
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Total Domains --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $total }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">کل دامنه‌ها</p>
            </div>
        </div>

        {{-- Connected --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $connected }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">دامنه‌های متصل</p>
            </div>
        </div>

        {{-- Parked Default --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $parkedDefault }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">در انتظار تخصیص</p>
            </div>
        </div>

        {{-- SSL Expiring --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $sslExpiring }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">SSL در حال انقضا (۳۰ روز)</p>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('domain-center.domains') }}"
           class="group bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 transition-colors">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-slate-800 dark:text-white text-sm">لیست دامنه‌ها</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">مشاهده همه دامنه‌ها</p>
            </div>
        </a>

        <a href="{{ route('domain-center.connect') }}"
           class="group bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/50 transition-colors">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 015.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-slate-800 dark:text-white text-sm">اتصال دامنه</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">آروان یا مستقیم</p>
            </div>
        </a>

        <a href="{{ route('domain-center.parked') }}"
           class="group bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center group-hover:bg-violet-100 dark:group-hover:bg-violet-900/50 transition-colors">
                <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-slate-800 dark:text-white text-sm">پارک دامنه</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">افزودن دامنه دوم</p>
            </div>
        </a>

        <a href="{{ route('domain-center.ns-settings') }}"
           class="group bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center group-hover:bg-sky-100 dark:group-hover:bg-sky-900/50 transition-colors">
                <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-slate-800 dark:text-white text-sm">تنظیمات NS</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">آدرس‌های Name Server</p>
            </div>
        </a>
    </div>

</div>
@endsection
