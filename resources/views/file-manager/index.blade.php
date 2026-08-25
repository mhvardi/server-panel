@extends('layouts.app')

@section('title', 'مدیر فایل')

@section('content')
<div x-data="fileManager()" x-init="init()" class="flex flex-col h-full">

    {{-- ─── Header ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-5 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">مدیر فایل</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">مرور و مدیریت فایل‌های <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">/var/www</code></p>
        </div>
        {{-- تب‌ها --}}
        <div class="flex gap-2">
            <button @click="activeTab = 'browser'"
                    :class="activeTab === 'browser' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all duration-200 border border-gray-200 dark:border-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                مرور فایل‌ها
            </button>
            <button @click="activeTab = 'analytics'; loadDiskUsage()"
                    :class="activeTab === 'analytics' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all duration-200 border border-gray-200 dark:border-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                آنالیز فضا
            </button>
        </div>
    </div>

    {{-- ─── TAB: File Browser ───────────────────────────────── --}}
    <div x-show="activeTab === 'browser'" x-cloak class="flex gap-4 flex-1 min-h-0" style="height: calc(100vh - 220px)">

        {{-- Sidebar: درخت پوشه‌ها --}}
        <div class="w-72 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="text-sm font-bold text-gray-700 dark:text-gray-200">پوشه‌ها</span>
                <button @click="navigateTo('/')" title="بازگشت به ریشه" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-0.5 custom-scrollbar" id="folderTree">
                <template x-if="loading && !items.length">
                    <div class="text-center py-8 text-gray-400 text-sm">در حال بارگذاری...</div>
                </template>
                <template x-for="item in items.filter(i => i.type === 'dir')" :key="item.name">
                    <button @click="navigateTo(currentPath + '/' + item.name)"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-right transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <span class="truncate text-right" x-text="item.name"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Main Area --}}
        <div class="flex-1 flex flex-col gap-4 min-w-0">

            {{-- Breadcrumb + Toolbar --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3 shadow-sm">
                {{-- Breadcrumb --}}
                <div class="flex items-center gap-1 flex-1 flex-wrap text-sm min-w-0">
                    <button @click="navigateTo('/')" class="font-bold text-indigo-600 hover:underline flex-shrink-0">var/www</button>
                    <template x-for="(crumb, idx) in breadcrumbs" :key="idx">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <button @click="navigateTo(crumb.path)"
                                    :class="idx === breadcrumbs.length - 1 ? 'text-gray-700 dark:text-gray-300 font-bold' : 'text-indigo-600 hover:underline'"
                                    class="truncate max-w-[120px]" x-text="crumb.name"></button>
                        </span>
                    </template>
                </div>

                {{-- Toolbar --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    {{-- جستجو --}}
                    <div class="relative">
                        <input x-model="searchQuery" type="text" placeholder="جستجو در این پوشه..."
                               class="text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 pr-8 w-44 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 absolute right-2.5 top-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    {{-- دکمه‌های عملیات --}}
                    <button @click="showMkdirModal = true" title="پوشه جدید"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 border border-amber-200 dark:border-amber-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        پوشه
                    </button>
                    <button @click="showTouchModal = true" title="فایل جدید"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 border border-green-200 dark:border-green-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        فایل
                    </button>
                    <label title="آپلود فایل" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-800 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        آپلود
                        <input type="file" class="hidden" @change="uploadFile($event)">
                    </label>
                </div>
            </div>

            {{-- File List --}}
            <div class="flex-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                {{-- Upload Progress --}}
                <div x-show="uploading" class="px-4 py-2 bg-blue-50 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800 text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    در حال آپلود...
                </div>

                {{-- Table Header --}}
                <div class="grid text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 px-4 py-2.5"
                     style="grid-template-columns: auto 80px 140px 80px 120px">
                    <span>نام</span>
                    <span class="text-center">نوع</span>
                    <span class="text-center">تاریخ تغییر</span>
                    <span class="text-center">حجم</span>
                    <span class="text-center">عملیات</span>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="flex-1 flex items-center justify-center text-gray-400 py-12">
                    <svg class="w-6 h-6 animate-spin ml-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    در حال بارگذاری...
                </div>

                {{-- Empty State --}}
                <div x-show="!loading && filteredItems.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400 py-12">
                    <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    <span class="text-sm">پوشه خالی است</span>
                </div>

                {{-- Items List --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-gray-50 dark:divide-gray-700/50">
                    <template x-for="item in filteredItems" :key="item.name">
                        <div class="grid items-center px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors group"
                             style="grid-template-columns: auto 80px 140px 80px 120px">

                            {{-- نام --}}
                            <div class="flex items-center gap-2.5 min-w-0">
                                {{-- آیکون --}}
                                <template x-if="item.type === 'dir'">
                                    <svg class="w-5 h-5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                </template>
                                <template x-if="item.type === 'file'">
                                    <svg class="w-5 h-5 flex-shrink-0" :class="fileIconColor(item.ext)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </template>

                                {{-- نام کلیک‌پذیر --}}
                                <button @click="item.type === 'dir' ? navigateTo(currentPath + '/' + item.name) : openFile(currentPath + '/' + item.name, item.name)"
                                        class="truncate text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 text-right"
                                        x-text="item.name">
                                </button>
                            </div>

                            {{-- نوع --}}
                            <div class="text-center">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                      :class="item.type === 'dir' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                                      x-text="item.type === 'dir' ? 'پوشه' : (item.ext ? item.ext.toUpperCase() : 'فایل')">
                                </span>
                            </div>

                            {{-- تاریخ --}}
                            <div class="text-center text-xs text-gray-500 dark:text-gray-400" x-text="item.modified ?? '—'"></div>

                            {{-- حجم --}}
                            <div class="text-center text-xs text-gray-500 dark:text-gray-400"
                                 x-text="item.size !== null ? humanSize(item.size) : '—'">
                            </div>

                            {{-- عملیات --}}
                            <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <template x-if="item.type === 'file'">
                                    <a :href="'{{ route('file-manager.download') }}?path=' + encodeURIComponent(currentPath + '/' + item.name)"
                                       title="دانلود" class="p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 text-blue-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                </template>
                                <button @click="startRename(item)" title="تغییر نام"
                                        class="p-1.5 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/30 text-amber-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteItem(currentPath + '/' + item.name, item.name)" title="حذف"
                                        class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── TAB: Disk Analytics ─────────────────────────────── --}}
    <div x-show="activeTab === 'analytics'" x-cloak>

        {{-- Disk Overview Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" x-show="diskData">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">حجم کل دیسک</p>
                <p class="text-3xl font-black text-gray-800 dark:text-gray-100" x-text="diskData?.disk_total_human ?? '—'"></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">مصرف شده</p>
                <p class="text-3xl font-black text-red-600 dark:text-red-400" x-text="diskData?.disk_used_human ?? '—'"></p>
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-red-500 h-2 rounded-full transition-all duration-700"
                         :style="'width:' + (diskData ? Math.round(diskData.disk_used / diskData.disk_total * 100) : 0) + '%'"></div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">آزاد</p>
                <p class="text-3xl font-black text-green-600 dark:text-green-400" x-text="diskData?.disk_free_human ?? '—'"></p>
            </div>
        </div>

        {{-- Disk Usage Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 dark:text-gray-200 text-sm">مصرف فضا در <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs" x-text="currentPath || '/'"></code></h2>
                <button @click="loadDiskUsage()" class="text-xs text-indigo-600 hover:underline font-semibold flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" :class="diskLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    بروزرسانی
                </button>
            </div>

            {{-- Loading --}}
            <div x-show="diskLoading" class="flex items-center justify-center py-16 text-gray-400">
                <svg class="w-6 h-6 animate-spin ml-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                در حال آنالیز (ممکن است چند ثانیه طول بکشد)...
            </div>

            <div x-show="!diskLoading && diskData">
                {{-- بار گرافیکی (Bar Chart ساده) --}}
                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                    <template x-for="item in (diskData?.items ?? []).slice(0, 10)" :key="item.name">
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1 text-sm">
                                <div class="flex items-center gap-2">
                                    <template x-if="item.type === 'dir'">
                                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                    </template>
                                    <template x-if="item.type === 'file'">
                                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </template>
                                    <span class="font-medium text-gray-700 dark:text-gray-300" x-text="item.name"></span>
                                </div>
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400" x-text="item.human"></span>
                            </div>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full transition-all duration-700"
                                     :class="item.type === 'dir' ? 'bg-amber-500' : 'bg-blue-500'"
                                     :style="'width:' + diskItemPercent(item) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- جدول کامل --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-3 text-right font-bold">#</th>
                            <th class="px-5 py-3 text-right font-bold">نام</th>
                            <th class="px-5 py-3 text-center font-bold">نوع</th>
                            <th class="px-5 py-3 text-center font-bold">حجم (دقیق)</th>
                            <th class="px-5 py-3 text-center font-bold">درصد از کل</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        <template x-for="(item, idx) in (diskData?.items ?? [])" :key="item.name">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-5 py-3 text-gray-400 text-xs" x-text="idx + 1"></td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <template x-if="item.type === 'dir'">
                                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                        </template>
                                        <template x-if="item.type === 'file'">
                                            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </template>
                                        <span class="font-medium text-gray-800 dark:text-gray-200" x-text="item.name"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          :class="item.type === 'dir' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400'"
                                          x-text="item.type === 'dir' ? 'پوشه' : 'فایل'">
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-400" x-text="item.human"></td>
                                <td class="px-5 py-3 text-center text-xs text-gray-500 dark:text-gray-400"
                                    x-text="diskData ? diskItemPercent(item).toFixed(1) + '%' : '—'">
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Modal: ویرایش فایل ──────────────────────────────── --}}
    <div x-show="editorOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="editorOpen = false">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col"
             style="height: 85vh"
             @click.stop>

            {{-- Editor Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300" x-text="editorFileName"></span>
                    <span x-show="editorDirty" class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="saveFile()"
                            :disabled="editorSaving"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors disabled:opacity-60">
                        <svg x-show="!editorSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <svg x-show="editorSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        ذخیره
                    </button>
                    <button @click="editorOpen = false" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Editor Body --}}
            <div class="flex-1 overflow-hidden relative">
                <textarea id="fileEditorArea"
                          x-model="editorContent"
                          @input="editorDirty = true"
                          @keydown.ctrl.s.prevent="saveFile()"
                          @keydown.meta.s.prevent="saveFile()"
                          class="w-full h-full p-4 font-mono text-sm bg-gray-950 text-gray-100 resize-none focus:outline-none leading-relaxed"
                          spellcheck="false"
                          dir="ltr">
                </textarea>
                <div x-show="editorLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 text-gray-400">
                    <svg class="w-6 h-6 animate-spin ml-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    در حال بارگذاری...
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Modal: پوشه جدید ────────────────────────────────── --}}
    <div x-show="showMkdirModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showMkdirModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4">ایجاد پوشه جدید</h3>
            <input x-model="newDirName" type="text" placeholder="نام پوشه..."
                   @keydown.enter="createDir()"
                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4" dir="ltr">
            <div class="flex gap-2 justify-end">
                <button @click="showMkdirModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">لغو</button>
                <button @click="createDir()" class="px-4 py-2 text-sm font-bold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">ساخت</button>
            </div>
        </div>
    </div>

    {{-- ─── Modal: فایل جدید ────────────────────────────────── --}}
    <div x-show="showTouchModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showTouchModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4">ایجاد فایل جدید</h3>
            <input x-model="newFileName" type="text" placeholder="نام فایل (مثلاً: config.php)"
                   @keydown.enter="createFile()"
                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4" dir="ltr">
            <div class="flex gap-2 justify-end">
                <button @click="showTouchModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">لغو</button>
                <button @click="createFile()" class="px-4 py-2 text-sm font-bold bg-green-600 text-white rounded-xl hover:bg-green-700">ساخت</button>
            </div>
        </div>
    </div>

    {{-- ─── Modal: تغییر نام ────────────────────────────────── --}}
    <div x-show="showRenameModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showRenameModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4">تغییر نام</h3>
            <input x-model="renameNewName" type="text"
                   @keydown.enter="confirmRename()"
                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4" dir="ltr">
            <div class="flex gap-2 justify-end">
                <button @click="showRenameModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">لغو</button>
                <button @click="confirmRename()" class="px-4 py-2 text-sm font-bold bg-amber-600 text-white rounded-xl hover:bg-amber-700">تغییر نام</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function fileManager() {
    return {
        // State
        activeTab:       'browser',
        currentPath:     '/',
        items:           [],
        loading:         false,
        searchQuery:     '',
        uploading:       false,

        // Editor
        editorOpen:      false,
        editorLoading:   false,
        editorSaving:    false,
        editorDirty:     false,
        editorContent:   '',
        editorFilePath:  '',
        editorFileName:  '',

        // Modals
        showMkdirModal:  false,
        newDirName:      '',
        showTouchModal:  false,
        newFileName:     '',
        showRenameModal: false,
        renameItem:      null,
        renameNewName:   '',

        // Analytics
        diskData:        null,
        diskLoading:     false,

        // ───────────────────────────────────────
        init() {
            this.navigateTo('/');
        },

        // ───────────────────────────────────────
        //  Computed
        // ───────────────────────────────────────
        get breadcrumbs() {
            if (!this.currentPath || this.currentPath === '/') return [];
            const parts = this.currentPath.replace(/^\//, '').split('/');
            return parts.map((part, idx) => ({
                name: part,
                path: '/' + parts.slice(0, idx + 1).join('/'),
            }));
        },

        get filteredItems() {
            if (!this.searchQuery.trim()) return this.items;
            const q = this.searchQuery.toLowerCase();
            return this.items.filter(i => i.name.toLowerCase().includes(q));
        },

        // ───────────────────────────────────────
        //  Navigation
        // ───────────────────────────────────────
        async navigateTo(path) {
            this.currentPath = path || '/';
            this.searchQuery = '';
            this.loading     = true;
            this.items       = [];

            try {
                const res  = await fetch(`{{ route('file-manager.browse') }}?path=${encodeURIComponent(this.currentPath)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();

                if (data.ok) {
                    this.items       = data.items;
                    this.currentPath = data.path;
                } else {
                    this.notify('error', data.message || 'خطا در بارگذاری');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            } finally {
                this.loading = false;
            }
        },

        // ───────────────────────────────────────
        //  File Operations
        // ───────────────────────────────────────
        async openFile(path, name) {
            this.editorFilePath = path;
            this.editorFileName = name;
            this.editorContent  = '';
            this.editorDirty    = false;
            this.editorLoading  = true;
            this.editorOpen     = true;

            try {
                const res  = await fetch(`{{ route('file-manager.read') }}?path=${encodeURIComponent(path)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();

                if (data.ok) {
                    this.editorContent = data.content;
                } else {
                    this.editorContent = '';
                    this.notify('error', data.message || 'خطا در خواندن فایل');
                    if (data.binary) {
                        this.editorOpen = false;
                    }
                }
            } catch (e) {
                this.notify('error', 'خطا در بارگذاری فایل');
            } finally {
                this.editorLoading = false;
            }
        },

        async saveFile() {
            if (this.editorSaving) return;
            this.editorSaving = true;

            try {
                const res  = await fetch(`{{ route('file-manager.save') }}`, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ path: this.editorFilePath, content: this.editorContent }),
                });
                const data = await res.json();

                if (data.ok) {
                    this.editorDirty = false;
                    this.notify('success', 'فایل ذخیره شد.');
                } else {
                    this.notify('error', data.message || 'خطا در ذخیره');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            } finally {
                this.editorSaving = false;
            }
        },

        async deleteItem(path, name) {
            if (!confirm(`آیا از حذف "${name}" مطمئن هستید؟ این عمل قابل بازگشت نیست.`)) return;

            try {
                const res  = await fetch(`{{ route('file-manager.delete') }}`, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ path }),
                });
                const data = await res.json();

                if (data.ok) {
                    this.notify('success', 'حذف شد.');
                    this.navigateTo(this.currentPath);
                } else {
                    this.notify('error', data.message || 'خطا در حذف');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            }
        },

        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.uploading = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', this.currentPath);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res  = await fetch(`{{ route('file-manager.upload') }}`, { method: 'POST', body: formData });
                const data = await res.json();

                if (data.ok) {
                    this.notify('success', `فایل "${data.filename}" آپلود شد.`);
                    this.navigateTo(this.currentPath);
                } else {
                    this.notify('error', data.message || 'خطا در آپلود');
                }
            } catch (e) {
                this.notify('error', 'خطا در آپلود');
            } finally {
                this.uploading   = false;
                event.target.value = '';
            }
        },

        async createDir() {
            if (!this.newDirName.trim()) return;
            try {
                const res  = await fetch(`{{ route('file-manager.mkdir') }}`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ path: this.currentPath, name: this.newDirName }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.notify('success', `پوشه "${data.name}" ساخته شد.`);
                    this.showMkdirModal = false;
                    this.newDirName     = '';
                    this.navigateTo(this.currentPath);
                } else {
                    this.notify('error', data.message || 'خطا در ساخت پوشه');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            }
        },

        async createFile() {
            if (!this.newFileName.trim()) return;
            try {
                const res  = await fetch(`{{ route('file-manager.touch') }}`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ path: this.currentPath, name: this.newFileName }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.notify('success', `فایل "${data.name}" ساخته شد.`);
                    this.showTouchModal = false;
                    this.newFileName    = '';
                    this.navigateTo(this.currentPath);
                } else {
                    this.notify('error', data.message || 'خطا در ساخت فایل');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            }
        },

        startRename(item) {
            this.renameItem    = item;
            this.renameNewName = item.name;
            this.showRenameModal = true;
        },

        async confirmRename() {
            if (!this.renameNewName.trim() || !this.renameItem) return;
            try {
                const res  = await fetch(`{{ route('file-manager.rename') }}`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        path:    this.currentPath + '/' + this.renameItem.name,
                        newName: this.renameNewName,
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.notify('success', 'تغییر نام داده شد.');
                    this.showRenameModal = false;
                    this.renameItem      = null;
                    this.navigateTo(this.currentPath);
                } else {
                    this.notify('error', data.message || 'خطا در تغییر نام');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            }
        },

        // ───────────────────────────────────────
        //  Disk Analytics
        // ───────────────────────────────────────
        async loadDiskUsage() {
            this.diskLoading = true;
            try {
                const res  = await fetch(`{{ route('file-manager.disk-usage') }}?path=${encodeURIComponent(this.currentPath)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (data.ok) {
                    this.diskData = data;
                } else {
                    this.notify('error', data.message || 'خطا در دریافت آمار');
                }
            } catch (e) {
                this.notify('error', 'خطا در ارتباط با سرور');
            } finally {
                this.diskLoading = false;
            }
        },

        diskItemPercent(item) {
            if (!this.diskData || !this.diskData.items?.length) return 0;
            const max = this.diskData.items[0]?.size || 1;
            return Math.round((item.size / max) * 100);
        },

        // ───────────────────────────────────────
        //  Helpers
        // ───────────────────────────────────────
        humanSize(bytes) {
            if (bytes === null || bytes === undefined) return '—';
            if (bytes === 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i     = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[Math.min(i, units.length - 1)];
        },

        fileIconColor(ext) {
            const map = {
                php: 'text-purple-500', js: 'text-yellow-500', ts: 'text-blue-400',
                html: 'text-orange-500', htm: 'text-orange-500', css: 'text-blue-500',
                json: 'text-green-500', yaml: 'text-green-400', yml: 'text-green-400',
                env: 'text-red-500', log: 'text-gray-500', md: 'text-gray-600',
                txt: 'text-gray-400', sql: 'text-blue-600', sh: 'text-green-600',
                py: 'text-blue-500', rb: 'text-red-600', go: 'text-cyan-500',
                xml: 'text-orange-400', svg: 'text-pink-500',
            };
            return map[ext] ?? 'text-gray-400';
        },

        notify(type, message) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { type, text: message } }));
        },
    };
}
</script>
@endpush
