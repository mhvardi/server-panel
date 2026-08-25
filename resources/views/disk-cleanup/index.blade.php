@extends('layouts.app')

@section('title', 'پاکسازی دیسک')

@section('content')
<div x-data="diskCleanupManager()" x-init="init()" class="space-y-6">

    {{-- ─── Header ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                پاکسازی دیسک (Disk Cleanup)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">پاکسازی لاگ‌های قدیمی، کش APT، و شناسایی پوشه‌های حجیم برای آزادسازی فضای هارد</p>
        </div>

        {{-- Auto Cleanup Big Action Button --}}
        <div>
            <button @click="runAutoCleanup()"
                    :disabled="cleaning"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-sm font-bold text-white shadow-lg transition-all duration-300 transform active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="cleaning ? 'bg-indigo-400' : 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 shadow-indigo-600/30 hover:shadow-indigo-600/40'">
                <svg x-show="!cleaning" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <svg x-show="cleaning" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="cleaning ? 'در حال اجرای پاکسازی...' : 'شروع پاکسازی خودکار (Auto Cleanup)'"></span>
            </button>
        </div>
    </div>

    {{-- ─── ۱. کارت وضعیت مصرف هارد (df -h /) ─────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/80 dark:border-gray-700/80 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                وضعیت لحظه‌ای فضای دیسک روت (<code class="text-indigo-600 dark:text-indigo-400 font-mono text-xs">/</code>)
            </h2>
            <button @click="loadStatus()" title="بروزرسانی وضعیت" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 font-semibold">
                <svg class="w-3.5 h-3.5" :class="statusLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                بروزرسانی
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
            {{-- کارت حجم کل --}}
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">کل ظرفیت دیسک</span>
                <p class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1" x-text="disk?.total_human ?? '—'"></p>
            </div>

            {{-- کارت مصرف شده --}}
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">فضای مصرف شده</span>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-1" x-text="disk?.used_human ?? '—'"></p>
            </div>

            {{-- کارت فضای آزاد --}}
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">فضای آزاد و در دسترس</span>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1" x-text="disk?.free_human ?? '—'"></p>
            </div>

            {{-- درصد گرافیکی --}}
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-4 border border-gray-100 dark:border-gray-800 flex flex-col justify-center">
                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                    <span class="text-gray-600 dark:text-gray-300">درصد پر بودن:</span>
                    <span class="font-mono text-sm" :class="disk?.percent > 85 ? 'text-red-500' : (disk?.percent > 65 ? 'text-amber-500' : 'text-indigo-500')" x-text="(disk?.percent ?? 0) + '%'"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-700"
                         :class="disk?.percent > 85 ? 'bg-red-500' : (disk?.percent > 65 ? 'bg-amber-500' : 'bg-indigo-600')"
                         :style="'width: ' + (disk?.percent ?? 0) + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── ۲. گزارش نتایج آخرین پاکسازی (Cleanup Logs) ────────── --}}
    <div x-show="cleanupResults.length > 0" x-cloak class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/80 dark:border-gray-700/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                نتایج آخرین عملیات پاکسازی خودکار
                <span class="text-xs text-gray-400 font-normal" x-text="'(مدت زمان: ' + cleanupDuration + ' ثانیه)'"></span>
            </h2>
            <button @click="cleanupResults = []" class="text-xs text-gray-400 hover:text-gray-600">بستن گزارش</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="(res, idx) in cleanupResults" :key="idx">
                <div class="rounded-2xl border p-4 bg-gray-50 dark:bg-gray-900/60 border-gray-100 dark:border-gray-800 text-xs">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="res.title"></span>
                        <span class="px-2 py-0.5 rounded-full font-mono font-bold text-[10px]"
                              :class="res.success ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'"
                              x-text="res.success ? 'موفق' : 'انجام شد'"></span>
                    </div>
                    <div class="text-[11px] font-mono text-gray-400 mb-2" x-text="res.command"></div>
                    <pre class="bg-gray-950 text-gray-200 p-2.5 rounded-xl font-mono text-[11px] overflow-x-auto max-h-24 custom-scrollbar whitespace-pre-wrap" x-text="res.output || 'بدون خروجی'"></pre>
                </div>
            </template>
        </div>
    </div>

    {{-- ─── ۳. بخش آنالیز عمیق پوشه‌ها و فایل‌های سنگین (Deep Analyze) ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/80 dark:border-gray-700/80 shadow-sm overflow-hidden flex flex-col">
        
        {{-- Toolbar & Breadcrumb --}}
        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50">
            <div>
                <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    آنالیز ۱۰ پوشه و فایل پرحجم کل سرور (Deep Analyze)
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">شناسایی فایل‌های فشرده، بکاپ‌های قدیمی و پوشه‌های حجیم</p>
            </div>

            {{-- Breadcrumb Navigation --}}
            <div class="flex items-center gap-1.5 flex-wrap text-xs bg-white dark:bg-gray-900 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700">
                <span class="text-gray-400 font-bold">مسیر:</span>
                <button @click="navigateAnalyze('/')" class="font-bold text-indigo-600 hover:underline">/ (Root)</button>
                <template x-for="(crumb, idx) in analyzeBreadcrumbs" :key="idx">
                    <span class="flex items-center gap-1">
                        <span class="text-gray-400">/</span>
                        <button @click="navigateAnalyze(crumb.path)"
                                :class="idx === analyzeBreadcrumbs.length - 1 ? 'font-black text-gray-800 dark:text-gray-200' : 'text-indigo-600 hover:underline'"
                                class="truncate max-w-[120px]" x-text="crumb.name"></button>
                    </span>
                </template>
                <button @click="loadAnalyze(analyzeCurrentPath)" title="آنالیز مجدد این مسیر" class="mr-2 text-gray-400 hover:text-indigo-600">
                    <svg class="w-3.5 h-3.5" :class="analyzeLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="analyzeLoading" class="flex flex-col items-center justify-center py-20 text-gray-400 gap-3">
            <svg class="w-8 h-8 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <span class="text-sm font-medium">در حال اسکن و محاسبه حجم با دستور <code class="font-mono text-xs">du -sh</code> (چند لحظه صبر کنید)...</span>
        </div>

        {{-- Content --}}
        <div x-show="!analyzeLoading">
            
            {{-- Visual Top Bars --}}
            <template x-if="topItems.length > 0">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/20 dark:bg-gray-800/20">
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">نمای میله‌ای بیشترین مصرف:</p>
                    <div class="space-y-2">
                        <template x-for="item in topItems.slice(0, 6)" :key="item.name">
                            <div class="group cursor-pointer" @click="item.type === 'dir' ? navigateAnalyze(item.full_path) : null">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <template x-if="item.type === 'dir'">
                                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                        </template>
                                        <template x-if="item.type === 'file'">
                                            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </template>
                                        <span class="font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 transition-colors truncate" x-text="item.full_path"></span>
                                        <span x-show="item.type === 'dir'" class="text-[10px] text-indigo-500 font-normal">(ورود)</span>
                                    </div>
                                    <span class="font-mono font-bold text-gray-700 dark:text-gray-300 mr-2 flex-shrink-0" x-text="item.human"></span>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         :class="item.type === 'dir' ? 'bg-gradient-to-l from-amber-400 to-amber-600' : 'bg-gradient-to-l from-blue-400 to-blue-600'"
                                         :style="'width: ' + itemPercent(item) + '%'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-3 text-right font-bold w-12">#</th>
                        <th class="px-5 py-3 text-right font-bold">نام و مسیر پوشه / فایل</th>
                        <th class="px-5 py-3 text-center font-bold w-24">نوع</th>
                        <th class="px-5 py-3 text-center font-bold w-36">حجم تخمینی</th>
                        <th class="px-5 py-3 text-center font-bold w-32">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <template x-for="(item, idx) in topItems" :key="item.name">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors group">
                            <td class="px-5 py-3.5 text-gray-400 text-xs font-mono" x-text="idx + 1"></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <template x-if="item.type === 'dir'">
                                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                    </template>
                                    <template x-if="item.type === 'file'">
                                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </template>

                                    <template x-if="item.type === 'dir'">
                                        <button @click="navigateAnalyze(item.full_path)"
                                                class="font-bold text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-right flex items-center gap-1.5 group-hover:underline">
                                            <span x-text="item.name"></span>
                                            <span class="text-[11px] text-gray-400 font-mono font-normal" x-text="'(' + item.full_path + ')'"></span>
                                            <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                    </template>
                                    <template x-if="item.type === 'file'">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-gray-800 dark:text-gray-200" x-text="item.name"></span>
                                            <span class="text-[11px] text-gray-400 font-mono" x-text="'(' + item.full_path + ')'"></span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                                      :class="item.type === 'dir' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400'"
                                      x-text="item.type === 'dir' ? 'پوشه' : 'فایل'">
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono font-bold text-xs text-gray-800 dark:text-gray-200" x-text="item.human"></td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- اگر پوشه است دکمه ورود --}}
                                    <template x-if="item.type === 'dir'">
                                        <button @click="navigateAnalyze(item.full_path)"
                                                title="آنالیز داخل این پوشه"
                                                class="p-1.5 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </template>

                                    {{-- دکمه حذف اضطراری --}}
                                    <button @click="deleteItem(item.full_path, item.name, item.type)"
                                            title="حذف این آیتم"
                                            class="p-1.5 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/40 text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="topItems.length === 0">
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-400 text-xs">
                                آیتمی در این پوشه یافت نشد.
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function diskCleanupManager() {
    return {
        disk: null,
        statusLoading: false,
        cleaning: false,
        cleanupResults: [],
        cleanupDuration: 0,

        // Deep Analyze State
        analyzeCurrentPath: '/',
        analyzeLoading: false,
        topItems: [],

        init() {
            this.loadStatus();
            this.loadAnalyze('/');
        },

        get analyzeBreadcrumbs() {
            if (!this.analyzeCurrentPath || this.analyzeCurrentPath === '/') return [];
            const parts = this.analyzeCurrentPath.replace(/^\//, '').split('/');
            return parts.map((part, idx) => ({
                name: part,
                path: '/' + parts.slice(0, idx + 1).join('/'),
            }));
        },

        async loadStatus() {
            this.statusLoading = true;
            try {
                const res = await fetch('{{ route("disk-cleanup.status") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.disk = data.disk;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.statusLoading = false;
            }
        },

        async runAutoCleanup() {
            if (!confirm('آیا از شروع پاکسازی خودکار اطمینان دارید؟\nاین عملیات لاگ‌های سیستمی قدیمی‌تر از ۳ روز، آرشیوهای .gz و کش پکیج‌های APT را پاک می‌کند.')) return;

            this.cleaning = true;
            try {
                const res = await fetch('{{ route("disk-cleanup.cleanup") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (data.ok) {
                    this.cleanupResults = data.logs || [];
                    this.cleanupDuration = data.duration || 0;
                    this.notify('success', data.message || 'پاکسازی انجام شد.');
                    // بروزرسانی وضعیت دیسک و آنالیز
                    this.loadStatus();
                    this.loadAnalyze(this.analyzeCurrentPath);
                } else {
                    this.notify('error', data.message || 'خطا در پاکسازی');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            } finally {
                this.cleaning = false;
            }
        },

        async loadAnalyze(path = '/') {
            this.analyzeCurrentPath = path || '/';
            this.analyzeLoading = true;

            try {
                const res = await fetch(`{{ route("disk-cleanup.analyze") }}?path=${encodeURIComponent(this.analyzeCurrentPath)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.ok) {
                    this.topItems = data.top_items || [];
                    this.analyzeCurrentPath = data.path || '/';
                } else {
                    this.notify('error', data.message || 'خطا در اسکن مسیر');
                }
            } catch (e) {
                this.notify('error', 'خطا در دریافت اطلاعات آنالیز');
            } finally {
                this.analyzeLoading = false;
            }
        },

        navigateAnalyze(path) {
            this.loadAnalyze(path);
        },

        itemPercent(item) {
            if (!this.topItems || !this.topItems.length) return 0;
            const max = this.topItems[0]?.size || 1;
            return Math.min(100, Math.round(((item.size || 0) / max) * 100));
        },

        async deleteItem(path, name, type) {
            const typeText = type === 'dir' ? 'پوشه' : 'فایل';
            if (!confirm(`آیا از حذف دائمی این ${typeText} ("${name}") در مسیر "${path}" اطمینان دارید؟`)) return;

            try {
                const res = await fetch('{{ route("disk-cleanup.delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ path })
                });
                const data = await res.json();

                if (data.ok) {
                    this.notify('success', 'مورد با موفقیت حذف شد.');
                    this.loadStatus();
                    this.loadAnalyze(this.analyzeCurrentPath);
                } else {
                    this.notify('error', data.message || 'خطا در حذف');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            }
        },

        notify(type, message) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { type, text: message } }));
        }
    };
}
</script>
@endpush
