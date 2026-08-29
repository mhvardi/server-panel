<div class="h-full flex flex-col font-sans transition-colors duration-500">
    <!-- Logo Section -->
    <div class="h-20 px-6 flex items-center mb-4 mt-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group text-decoration-none">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white grid place-content-center font-black text-xl shadow-xl shadow-indigo-600/30 group-hover:rotate-6 group-hover:scale-110 transition-all duration-300">
                S
            </div>
            <div x-show="!sidebarCollapsed" class="flex flex-col">
                <span class="text-sm font-black text-slate-900 dark:text-white tracking-tight group-hover:text-indigo-600 transition-colors">پنل سرور</span>
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">مدیریت جامع</span>
            </div>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-2 space-y-8 custom-scrollbar pb-10">
        <!-- Section: General -->
        <div>
            <p x-show="!sidebarCollapsed" class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">عمومی</p>
            <ul class="space-y-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-300
                       {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        <span x-show="!sidebarCollapsed">داشبورد</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Section: Services -->
        <div>
            <p x-show="!sidebarCollapsed" class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">سرویس‌ها و دامنه‌ها</p>
            <ul class="space-y-1.5">
                <li>
                    <a href="{{ route('services.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('services.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L12 15.25l5.571-3m-11.142 0l5.571 3L12 15.25l5.571-3M3.25 12l8.75-4.75L20.75 12l-8.75 4.75L3.25 12z" /></svg>
                        <span x-show="!sidebarCollapsed">مدیریت سرویس‌ها</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('domain-mappings.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('domain-mappings.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        <span x-show="!sidebarCollapsed">دامنه‌های اختصاصی</span>
                    </a>
                </li>
                <!-- Domain Center Accordion -->
                <li x-data="{ open: {{ request()->routeIs('domain-center.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full group flex items-center justify-between gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                            {{ request()->routeIs('domain-center.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                            </svg>
                            <span x-show="!sidebarCollapsed">مرکز دامنه</span>
                        </div>
                        <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" class="mt-1 mr-4 space-y-0.5 border-r-2 border-indigo-200 dark:border-indigo-800 pr-2">
                        <a href="{{ route('domain-center.domains') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all
                           {{ request()->routeIs('domain-center.domains') ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/30' }}">
                            <span x-show="!sidebarCollapsed">دامنه‌های اختصاصی</span>
                        </a>
                        <a href="{{ route('domain-center.connect') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all
                           {{ request()->routeIs('domain-center.connect') ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/30' }}">
                            <span x-show="!sidebarCollapsed">اتصال دامنه</span>
                        </a>
                        <a href="{{ route('domain-center.parked') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all
                           {{ request()->routeIs('domain-center.parked') ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/30' }}">
                            <span x-show="!sidebarCollapsed">پارک دامین</span>
                        </a>
                        <a href="{{ route('domain-center.ns-settings') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all
                           {{ request()->routeIs('domain-center.ns-settings') ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/30' }}">
                            <span x-show="!sidebarCollapsed">تنظیمات Name Servers</span>
                        </a>
                    </div>
                </li>


        <!-- Section: System & Server -->
        <div>
            <p x-show="!sidebarCollapsed" class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">سیستم و سرور</p>
            <ul class="space-y-1.5">
                <li>
                    <a href="{{ route('databases.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('databases.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        <span x-show="!sidebarCollapsed">پایگاه داده‌ها</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('file-manager.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('file-manager.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        <span x-show="!sidebarCollapsed">مدیر فایل</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('disk-cleanup.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('disk-cleanup.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span x-show="!sidebarCollapsed">پاکسازی دیسک</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('cronjobs.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('cronjobs.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span x-show="!sidebarCollapsed">وظایف زمانبندی شده</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('backup_tasks.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('backup_tasks.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-2-4l-4 4m0 0l-4-4m4 4v11"></path></svg>
                        <span x-show="!sidebarCollapsed">پشتیبان‌گیری</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Section: Panel & Settings -->
        <div>
            <p x-show="!sidebarCollapsed" class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">تنظیمات و مدیریت</p>
            <ul class="space-y-1.5">
                <li>
                    <a href="{{ route('users.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.67c.12-.318.232-.656.328-1.014a6.375 6.375 0 011.014-3.28c.328-.47.682-.919 1.084-1.332a6.375 6.375 0 011.332-1.084c.413-.401.862-.755 1.332-1.084.318-.24.656-.458 1.014-.645a6.375 6.375 0 013.28-1.014 6.375 6.375 0 014.67 11.964l-.109.001c-1.12.588-2.372.952-3.72.952a9.337 9.337 0 01-4.121-.952v-.003z" /></svg>
                        <span x-show="!sidebarCollapsed">مدیریت کاربران</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('2fa.setup') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('2fa.setup') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.276a11.952 11.952 0 01-1.292-2.849A4.962 4.962 0 0012 3c-1.373 0-2.684.28-3.868.811a11.952 11.952 0 01-1.292 2.849M5.618 4.276a11.952 11.952 0 00-1.292 2.849A4.962 4.962 0 013 12c0 1.373.28 2.684.811 3.868a11.952 11.952 0 002.849 1.292M4.276 18.382a11.952 11.952 0 012.849 1.292A4.962 4.962 0 0012 21c1.373 0 2.684-.28 3.868-.811a11.952 11.952 0 011.292-2.849M18.382 19.724a11.952 11.952 0 00-2.849-1.292A4.962 4.962 0 0121 12c0-1.373-.28-2.684-.811-3.868a11.952 11.952 0 00-1.292-2.849"></path></svg>
                        <span x-show="!sidebarCollapsed">احراز هویت دو مرحله‌ای</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('settings.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span x-show="!sidebarCollapsed">تنظیمات پنل</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.logs.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                       {{ request()->routeIs('admin.logs.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span x-show="!sidebarCollapsed">گزارشات سیستم</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Collapse Button -->
    <div class="px-6 py-4 border-t border-slate-200/60 dark:border-slate-700 mt-auto">
        <button @click="toggleSidebar()" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-300 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white">
            <svg x-show="!sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <svg x-show="sidebarCollapsed" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            <span x-show="!sidebarCollapsed">بستن منو</span>
        </button>
    </div>
</div>
