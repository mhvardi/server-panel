{{-- admin/partials/topbar.blade.php --}}
<header class="sticky top-0 z-30 bg-white/70 dark:bg-[#070a13]/60 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800/60 transition-all duration-300">
    <div class="h-18 px-4 sm:px-6 lg:px-8 flex items-center justify-between py-3">
        <div class="flex items-center gap-4">
            <button class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all focus:outline-none focus:ring-0"
                    @click="sidebarOpen=true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="hidden sm:flex flex-col text-right">
                <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest leading-none mb-1">CORE ADMIN</span>
                <div class="text-sm font-black text-slate-900 dark:text-white leading-none">
                    {{ $title ?? 'داشبورد مدیریتی' }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Theme Toggle (Persistent) --}}
            <button @click="dark=!dark"
                    class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:shadow-lg hover:border-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-0"
                    title="تغییر حالت شب/روز">
                <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>

            {{-- User Dropdown --}}
            <div class="shrink-0 ml-1">
                <style>
                    .shrink-0.ml-1 .rounded-md{
                        border-radius: 2rem !important;
                    }
                </style>
                @auth
                    {{-- align="right" ensures menu opens towards the left (inside the screen) for RTL layouts --}}
                    <x-dropdown align="right" width="64" contentClasses="p-0 bg-transparent !ring-0 !ring-transparent shadow-none border-none overflow-visible !rounded-4xl">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:shadow-xl hover:border-indigo-500/30 transition-all duration-300 group focus:outline-none focus:ring-0 active:ring-0 active:outline-none">
                                <div class="relative">
                                    <img class="h-9 w-9 rounded-xl object-cover border-2 border-slate-50 dark:border-slate-700 group-hover:border-indigo-500 transition-colors" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}"/>
                                    <span class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-800 rounded-full"></span>
                                </div>
                                <div class="hidden lg:flex flex-col items-start ml-2 pe-3 text-right">
                                    <span class="text-xs font-black text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">{{ Auth::user()->name }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ Auth::user()->roles->first()->display_name ?? 'کاربر ارشد' }}</span>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            {{-- Advanced Glassmorphism Submenu --}}
                            <div class="mt-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-slate-200 dark:border-slate-700 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] dark:shadow-none overflow-hidden min-w-[260px] animate-in zoom-in-95 duration-200 ring-0 outline-none">
                                <div class="px-6 py-5 bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800 text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">حساب کاربری فعال</p>
                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 truncate leading-none">{{ Auth::user()->email }}</p>
                                </div>

                                <div class="p-3 space-y-1.5">
                                    <x-dropdown-link href="{{ route('profile.show') }}" class="rounded-2xl flex items-center gap-3 py-3 px-4 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all font-bold group/item focus:bg-indigo-50 dark:focus:bg-indigo-500/10 focus:outline-none ring-0 outline-none border-none">
                                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl group-hover/item:bg-indigo-100 dark:group-hover/item:bg-indigo-500/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                        مدیریت پروفایل
                                    </x-dropdown-link>

                                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                        <x-dropdown-link href="{{ route('api-tokens.index') }}" class="rounded-2xl flex items-center gap-3 py-3 px-4 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 font-bold group/item focus:outline-none border-none">
                                            <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl group-hover/item:bg-indigo-100 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            </div>
                                            کلیدهای API
                                        </x-dropdown-link>
                                    @endif

                                    <div class="border-t border-slate-100 dark:border-slate-800 my-2 mx-2"></div>

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
