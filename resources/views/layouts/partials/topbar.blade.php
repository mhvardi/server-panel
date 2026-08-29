<header class="sticky top-0 z-30 bg-white/70 dark:bg-gray-900/60 backdrop-blur-xl border-b border-gray-200 dark:border-gray-700/60 transition-all duration-300">
    <div class="h-18 px-4 sm:px-6 lg:px-8 flex items-center justify-between py-3">
        <!-- Left Section: Mobile Menu & Title -->
        <div class="flex items-center gap-4">
            <button class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all focus:outline-none focus:ring-0"
                    @click="mobileOpen = true">
                <span class="sr-only">باز کردن منو</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="hidden sm:flex flex-col text-right">
                <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest leading-none mb-1">پنل سرور</span>
                <div class="text-sm font-black text-gray-900 dark:text-white leading-none">
                    @yield('title', 'داشبورد')
                </div>
            </div>
        </div>

        <!-- Center Section: Quick Access Icons (Desktop) -->
        <div class="hidden lg:flex items-center gap-2">
            <a href="{{ route('dashboard') }}" title="داشبورد" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0 {{ request()->routeIs('dashboard') ? 'text-indigo-600 shadow-lg border-indigo-500/50' : '' }}">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
            </a>
            <a href="{{ route('services.index') }}" title="سرویس‌ها" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0 {{ request()->routeIs('services.*') ? 'text-indigo-600 shadow-lg border-indigo-500/50' : '' }}">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L12 15.25l5.571-3m-11.142 0l5.571 3L12 15.25l5.571-3M3.25 12l8.75-4.75L20.75 12l-8.75 4.75L3.25 12z" /></svg>
            </a>
            <a href="{{ route('domain-center.domains') }}" title="مرکز دامنه" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0 {{ request()->routeIs('domain-center.*') ? 'text-indigo-600 shadow-lg border-indigo-500/50' : '' }}">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
            </a>
            <a href="{{ route('users.index') }}" title="کاربران" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0 {{ request()->routeIs('users.*') ? 'text-indigo-600 shadow-lg border-indigo-500/50' : '' }}">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.67c.12-.318.232-.656.328-1.014a6.375 6.375 0 011.014-3.28c.328-.47.682-.919 1.084-1.332a6.375 6.375 0 011.332-1.084c.413-.401.862-.755 1.332-1.084.318-.24.656-.458 1.014-.645a6.375 6.375 0 013.28-1.014 6.375 6.375 0 014.67 11.964l-.109.001c-1.12.588-2.372.952-3.72.952a9.337 9.337 0 01-4.121-.952v-.003z" /></svg>
            </a>
            <a href="#" title="تنظیمات" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0 {{ request()->routeIs('settings.*') ? 'text-indigo-600 shadow-lg border-indigo-500/50' : '' }}">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.108 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.108 1.204l.527.738c.32.447.27.96-.12 1.45l-.773.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.93l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527a1.125 1.125 0 01-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.93l.15-.893z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </a>
        </div>

        <!-- Right Section: Theme & User Menu -->
        <div class="flex items-center gap-3">
            <button @click="cycleTheme()"
                    class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0"
                    title="تغییر تم">
                <template x-if="theme === 'dark'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></template>
                <template x-if="theme === 'light'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg></template>
                <template x-if="theme === 'system'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21.75H5.121a3 3 0 01-2.122-.879l-1.286-1.287a1.125 1.125 0 01-.378-.879V17.25m13.5 0v1.007a3 3 0 01-.879 2.122L16.5 21.75h-2.379a3 3 0 01-2.122-.879l-1.286-1.287a1.125 1.125 0 01-.378-.879V17.25m3.75-9.75h4.5m-4.5 0a2.25 2.25 0 012.25 2.25V15a2.25 2.25 0 01-2.25 2.25h-1.5a2.25 2.25 0 01-2.25-2.25V9.75A2.25 2.25 0 0112 7.5zM12 12h.008v.008H12V12zm0 3h.008v.008H12V15z" /></svg></template>
            </button>

            <div class="shrink-0 ml-1">
                @auth
                    <x-dropdown align="right" width="64" contentClasses="p-0 bg-transparent !ring-0 !ring-transparent shadow-none border-none overflow-visible !rounded-4xl">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:shadow-xl hover:border-indigo-500/30 transition-all duration-300 group focus:outline-none focus:ring-0 active:ring-0 active:outline-none">

                                <div class="hidden lg:flex flex-col items-start ml-2 pe-3 text-right">
                                    <span class="text-xs font-black text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">{{ Auth::user()->name }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">{{ Auth::user()->roles?->first()->display_name ?? 'مدیر' }}</span>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="mt-2 bg-white/95 dark:bg-gray-900/95 backdrop-blur-2xl border border-gray-200 dark:border-gray-700 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] dark:shadow-none overflow-hidden min-w-[260px] animate-in zoom-in-95 duration-200 ring-0 outline-none">
                                <div class="px-6 py-5 bg-gray-50/50 dark:bg-gray-800/30 border-b border-gray-100 dark:border-gray-800 text-right">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">حساب کاربری فعال</p>
                                    <p class="text-xs font-bold text-gray-600 dark:text-gray-300 truncate leading-none">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="p-3 space-y-1.5">
                                    <x-dropdown-link href="#" class="rounded-2xl flex items-center gap-3 py-3 px-4 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all font-bold group/item focus:bg-indigo-50 dark:focus:bg-indigo-500/10 focus:outline-none ring-0 outline-none border-none">
                                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-xl group-hover/item:bg-indigo-100 dark:group-hover/item:bg-indigo-500/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                        پروفایل
                                    </x-dropdown-link>
                                    <div class="border-t border-gray-100 dark:border-gray-800 my-2 mx-2"></div>
                                    <form method="POST" action="{{ route('logout') }}" x-data>
                                        @csrf
                                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();" class="rounded-2xl text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 flex items-center gap-3 py-3 px-4 font-bold transition-all group/item focus:outline-none border-none">
                                            <div class="p-2 bg-red-50 dark:bg-red-500/10 rounded-xl group-hover/item:bg-red-100 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            </div>
                                            خروج از سامانه
                                        </x-dropdown-link>
                                    </form>
                                </div>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>
        </div>
    </div>
</header>
