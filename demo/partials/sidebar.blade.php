{{-- admin/partials/sidebar.blade.php --}}
<div class="h-full flex flex-col font-iranYekan transition-colors duration-500">
    {{-- Logo Section --}}
    <div class="h-20 px-6 flex items-center mb-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group text-decoration-none">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white grid place-content-center font-black text-xl shadow-xl shadow-indigo-600/30 group-hover:rotate-6 group-hover:scale-110 transition-all duration-300">
                S
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-black text-slate-900 dark:text-white tracking-tight group-hover:text-indigo-600 transition-colors">SITO CRM</span>
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Admin Control</span>
            </div>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-2 space-y-8 custom-scrollbar pb-10">
        {{-- Section: General --}}
        <div>
            <p class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">اصلی</p>
            <ul class="space-y-1.5">
                <li>
                    <a href="{{ route('admin.dashboard') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-300
{{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>داشبورد مدیریت</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- Section: Core Configuration --}}
        <div>
            <p class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">مدیریت زیرساخت</p>
            <ul class="space-y-1.5">
                @can('menu.see.users')
                    <li>
                        <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <span>کاربران ادمین</span>
                        </a>
                    </li>
                @endcan

                @can('menu.see.roles')
                    <li>
                        <a href="{{ route('admin.roles.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.roles.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            <span>نقش‌ها و سطح دسترسی</span>
                        </a>
                    </li>
                @endcan

                <li>
                    <a href="{{ route('admin.modules.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.modules.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        <span>مدیریت ماژول‌ها</span>
                    </a>
                </li>

                @can('menu.see.custom-fields')
                    <li>
                        <a href="{{ route('admin.custom-fields.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.custom-fields.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
                            <span>فیلدهای سفارشی</span>
                        </a>
                    </li>
                @endcan

                @if(auth()->user()->hasRole('super-admin'))
                    <li>
                        <a href="{{ route('admin.version-control.index') }}"
                           class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.version-control.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.version-control.*') ? 'opacity-100' : 'opacity-60' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>کنترل نسخه‌ها (Git)</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        {{-- Section: System Settings --}}
        <div>
            <p class="px-4 mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">پیکربندی ماژول‌ها</p>
            <ul class="space-y-1.5">
                {{-- اینجا فقط لینک تنظیمات اختصاصی ماژول‌ها در ادمین قرار می‌گیرد --}}
                @if(Route::has('admin.booking.settings.edit'))
                    <li>
                        <a href="{{ route('admin.booking.settings.edit') }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('admin.booking.settings.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>تنظیمات رزرواسیون</span>
                        </a>
                    </li>
                @endif

                <li>
                    <a href="{{ route('settings.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
{{ request()->routeIs('settings.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <span>تنظیمات عمومی</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    {{-- Persistent Version Badge --}}
    <div class="px-6 py-6 border-t border-slate-200/60 dark:border-slate-800/50">
        <div class="p-4 rounded-[1.5rem] bg-indigo-50 dark:bg-indigo-500/5 border border-indigo-100 dark:border-indigo-500/10 transition-colors">
            @php $v = \App\Models\VersionLog::current(); @endphp
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-indigo-400 dark:text-indigo-500 uppercase tracking-widest leading-none">Core Status</span>
                <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-xs font-black text-slate-800 dark:text-slate-200">Version</span>
                <span class="text-lg font-mono font-black text-indigo-600 dark:text-indigo-400 leading-none">
                    {{ $v ? $v->version_number : '1.0.0' }}
                </span>
            </div>
            <div class="mt-2 text-[8px] font-bold text-slate-400 dark:text-slate-600 italic">Sito Framework Engine</div>
        </div>
    </div>
</div>
