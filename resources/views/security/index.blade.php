@extends('layouts.app')

@section('title', 'مرکز جامع امنیت سرور')

@section('content')
<div class="space-y-8" x-data="{ activeTab: '{{ request()->has('logins_page') ? 'logins' : (request()->has('events_page') ? 'events' : 'overview') }}' }">

    <!-- Top Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-indigo-600 flex items-center justify-center text-white shadow-xl shadow-rose-500/20">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3">
                    مرکز امنیت هسته سرور
                    <span class="text-xs px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold">پایش فعال</span>
                </h1>
                <p class="text-slate-400 text-sm mt-1">
                    محافظت چندلایه‌ای از ورود به پنل، فایل‌های آپلود شده، دسترسی‌ها و پورت‌های سرور
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
            <form action="{{ route('security.scan') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="target" value="all">
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>اجرای اسکن پس‌زمینه</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Score & Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Security Score -->
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">امتیاز امنیت سرور</span>
                <span class="text-2xl font-black {{ $stats['security_score'] >= 80 ? 'text-emerald-500' : ($stats['security_score'] >= 50 ? 'text-amber-500' : 'text-rose-500') }}">
                    {{ $stats['security_score'] }}%
                </span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $stats['security_score'] >= 80 ? 'bg-emerald-500' : ($stats['security_score'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $stats['security_score'] }}%"></div>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3">
                {{ $stats['security_score'] >= 80 ? 'سطح محافظت عالی و استاندارد' : 'نیاز به بهینه‌سازی تنظیمات امنیتی' }}
            </p>
        </div>

        <!-- GeoIP Status -->
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">محدودیت ورود به ایران</span>
                <span class="px-2.5 py-1 text-xs rounded-full font-bold {{ $settings['iran_ip_restriction'] ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-100 dark:bg-slate-700 text-slate-400' }}">
                    {{ $settings['iran_ip_restriction'] ? 'فعال' : 'غیرفعال' }}
                </span>
            </div>
            <div class="mt-3">
                <div class="text-sm font-bold text-slate-800 dark:text-slate-200">آی‌پی فعلی شما:</div>
                <div class="font-mono text-xs text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">{{ $stats['client_ip'] }} ({{ $stats['client_country'] }})</div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                {{ $settings['iran_ip_restriction'] ? 'تنها آی‌پی‌های ایران و لیست سفید مجازند' : 'ورود از تمام کشورهای جهان باز است' }}
            </p>
        </div>

        <!-- Blocked Logins -->
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">تلاش‌های مسدود شده</span>
                <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-slate-100 mt-2">
                {{ $stats['total_blocked_logins'] }}
            </div>
            <p class="text-[11px] text-slate-500 mt-2">قانون: ۳ تلاش ناموفق = ۲۴ ساعت مسدودی</p>
        </div>

        <!-- Quarantined Files -->
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">فایل‌های قرنطینه</span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-slate-100 mt-2">
                {{ $stats['total_quarantined'] }}
            </div>
            <p class="text-[11px] text-slate-500 mt-2">آخرین اسکن: {{ $settings['last_scan_at'] ?: 'تاکنون انجام نشده' }}</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-3 overflow-x-auto">
        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-5 py-2.5 rounded-2xl text-sm font-bold transition-all whitespace-nowrap">
            وضعیت کلی سرور
        </button>
        <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-5 py-2.5 rounded-2xl text-sm font-bold transition-all whitespace-nowrap">
            پیکربندی امنیت و لیست سفید
        </button>
        <button @click="activeTab = 'quarantine'" :class="activeTab === 'quarantine' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-5 py-2.5 rounded-2xl text-sm font-bold transition-all whitespace-nowrap">
            قرنطینه و فایل‌های مشکوک ({{ $stats['total_quarantined'] }})
        </button>
        <button @click="activeTab = 'logins'" :class="activeTab === 'logins' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-5 py-2.5 rounded-2xl text-sm font-bold transition-all whitespace-nowrap">
            لاگ تلاش‌های ورود
        </button>
        <button @click="activeTab = 'events'" :class="activeTab === 'events' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-5 py-2.5 rounded-2xl text-sm font-bold transition-all whitespace-nowrap">
            رویدادهای امنیتی
        </button>
    </div>

    <!-- TAB 1: SERVER OVERVIEW -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Critical Warnings if any -->
        @if (!empty($serverAudit['sensitive_files']))
            <div class="bg-rose-500/10 border border-rose-500/30 rounded-3xl p-6">
                <h3 class="text-rose-400 font-black text-lg flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    آسیب‌پذیری‌های بحرانی سرور
                </h3>
                <div class="space-y-2">
                    @foreach($serverAudit['sensitive_files'] as $vuln)
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-rose-500/5 text-sm text-rose-300">
                            <div>
                                <span class="font-bold">{{ $vuln['title'] }}:</span>
                                <span class="opacity-80 mr-2">{{ $vuln['desc'] }}</span>
                            </div>
                            <span class="px-2.5 py-1 text-xs rounded-full bg-rose-500/20 text-rose-300 font-mono font-bold uppercase">Critical</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Listening Ports -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        پورت‌های باز شبکه سرور
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">{{ count($serverAudit['listening_ports']) }} پورت فعال</span>
                </div>
                <div class="overflow-y-auto max-h-72 custom-scrollbar">
                    <table class="w-full text-right text-xs">
                        <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700/60">
                            <tr>
                                <th class="pb-2">پورت</th>
                                <th class="pb-2">پروتکل</th>
                                <th class="pb-2">بایند</th>
                                <th class="pb-2">سرویس احتمالی</th>
                                <th class="pb-2">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-mono">
                            @forelse($serverAudit['listening_ports'] as $p)
                                <tr>
                                    <td class="py-2.5 font-bold text-slate-700 dark:text-slate-200">{{ $p['port'] }}</td>
                                    <td class="py-2.5 text-slate-500">{{ $p['protocol'] }}</td>
                                    <td class="py-2.5 text-slate-500">{{ $p['bind'] }}</td>
                                    <td class="py-2.5 text-indigo-500 font-sans font-bold">{{ $p['service'] }}</td>
                                    <td class="py-2.5">
                                        @if($p['is_exposed'])
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-500">پابلیک</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-400">لوکال</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 font-sans">اطلاعات پورت‌های سرور در دسترس نیست.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Permission Audit -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        مجوز فایل‌ها و دایرکتوری‌های حساس
                    </h3>
                </div>
                @if(empty($serverAudit['permission_warnings']))
                    <div class="p-8 text-center bg-emerald-500/5 rounded-2xl border border-emerald-500/20">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-500 mx-auto flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm font-bold text-emerald-500">تمامی مجوزهای فایل‌های حساس سرور در وضعیت ایمن قرار دارند.</p>
                        <p class="text-xs text-slate-400 mt-1">هیچ فایل هسته‌ای با دسترسی خطرناک 777 یا 666 یافت نشد.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($serverAudit['permission_warnings'] as $warn)
                            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-amber-500">{{ $warn['name'] }}</div>
                                    <div class="font-mono text-slate-400 text-[11px] mt-0.5">{{ $warn['path'] }}</div>
                                </div>
                                <div class="text-left font-mono">
                                    <span class="text-rose-400 font-bold">{{ $warn['current_perms'] }}</span>
                                    <span class="text-slate-400 mx-1">→</span>
                                    <span class="text-emerald-400">{{ $warn['recommended'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- TAB 2: SETTINGS & WHITELIST -->
    <div x-show="activeTab === 'settings'" class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/60 shadow-sm">
        <form action="{{ route('security.settings.update') }}" method="POST" class="space-y-8">
            @csrf
            
            <div class="border-b border-slate-100 dark:border-slate-700/60 pb-6">
                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 mb-1">تنظیمات لایه ورود و دسترسی جغرافیایی</h3>
                <p class="text-slate-400 text-xs">پیکربندی هوشمند دسترسی به پنل و محافظت در برابر حملات Brute-Force</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Iran IP Restriction Switch -->
                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/50 flex items-start justify-between">
                    <div>
                        <label class="font-bold text-slate-800 dark:text-slate-200 text-sm block">محدودسازی ورود فقط به سرورهای ایران</label>
                        <p class="text-slate-400 text-xs mt-1 leading-relaxed">
                            در صورت فعال بودن، هرگونه درخواست ورود از خارج از کشور مسدود شده و تنها آی‌پی‌های ایران و لیست سفید مجاز خواهند بود.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer mr-4">
                        <input type="checkbox" name="iran_ip_restriction" value="1" {{ $settings['iran_ip_restriction'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Rate Limit Lockout -->
                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/50 space-y-4">
                    <div>
                        <label class="font-bold text-slate-800 dark:text-slate-200 text-sm block">سیاست مسدودسازی تلاش‌های ناموفق (Brute-Force)</label>
                        <p class="text-slate-400 text-xs mt-1">تعداد تلاش‌های نامعتبر قبل از مسدودی ۲۴ ساعته</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-400">حداکثر دفعات تلاش:</label>
                            <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] }}" min="1" max="10" class="mt-1 w-full rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-mono font-bold">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400">مدت زمان مسدودی (دقیقه):</label>
                            <input type="number" name="lockout_minutes" value="{{ $settings['lockout_minutes'] }}" min="1" class="mt-1 w-full rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-mono font-bold">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Whitelisted IPs Textarea -->
            <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/50">
                <div class="flex items-center justify-between mb-2">
                    <label class="font-bold text-slate-800 dark:text-slate-200 text-sm">لیست سفید آی‌پی‌ها (IP Whitelist)</label>
                    <span class="text-[11px] text-indigo-500 font-bold">آی‌پی شرکت: 94.183.100.3 (پیش‌فرض دائمی)</span>
                </div>
                <p class="text-slate-400 text-xs mb-3">
                    هر آی‌پی یا محدوده CIDR را در یک خط جداگانه وارد نمایید. این آی‌پی‌ها هیچ‌گاه مسدود نخواهند شد.
                </p>
                <textarea name="whitelisted_ips" rows="4" class="w-full rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 text-xs font-mono text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="94.183.100.3&#10;127.0.0.1">{{ $settings['whitelisted_ips'] }}</textarea>
            </div>

            <!-- Antivirus & File Scanner Toggles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/50 flex items-start justify-between">
                    <div>
                        <label class="font-bold text-slate-800 dark:text-slate-200 text-sm block">اسکن خودکار کلیه فایل‌های آپلودی</label>
                        <p class="text-slate-400 text-xs mt-1">بررسی لحظه‌ای امضای وب‌شل‌ها و توابع خطرناک در حین آپلود در پنل</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer mr-4">
                        <input type="checkbox" name="upload_file_scan" value="1" {{ $settings['upload_file_scan'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/50 flex items-start justify-between">
                    <div>
                        <label class="font-bold text-slate-800 dark:text-slate-200 text-sm block">انتقال خودکار فایل‌های آلوده به قرنطینه</label>
                        <p class="text-slate-400 text-xs mt-1">در صورت کشف فایل مخرب، فوراً غیرفعال و به پوشه قرنطینه منتقل می‌شود</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer mr-4">
                        <input type="checkbox" name="quarantine_infected" value="1" {{ $settings['quarantine_infected'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="px-8 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all">
                    ذخیره تنظیمات مرکز امنیت
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 3: QUARANTINE -->
    <div x-show="activeTab === 'quarantine'" class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/60 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">فایل‌های قرنطینه‌شده سرور</h3>
                <p class="text-slate-400 text-xs mt-0.5">فایل‌هایی که دارای الگوهای وب‌شل یا پسوند مخرب بوده و از دسترس خارج شده‌اند</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700/60">
                    <tr>
                        <th class="pb-3">نام فایل</th>
                        <th class="pb-3">مسیر اصلی</th>
                        <th class="pb-3">علت قرنطینه</th>
                        <th class="pb-3">نوع تهدید</th>
                        <th class="pb-3">تاریخ کشف</th>
                        <th class="pb-3 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @forelse($quarantinedFiles as $file)
                        <tr>
                            <td class="py-3 font-mono font-bold text-rose-500">{{ $file->filename }}</td>
                            <td class="py-3 font-mono text-slate-400 text-[11px]">{{ $file->original_path }}</td>
                            <td class="py-3 text-slate-700 dark:text-slate-300">{{ $file->reason }}</td>
                            <td class="py-3 font-mono text-amber-400">{{ $file->threat_type }}</td>
                            <td class="py-3 text-slate-400">{{ $file->created_at->diffForHumans() }}</td>
                            <td class="py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('security.quarantine.restore', $file->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" onclick="return confirm('آیا از بازگردانی این فایل اطمینان دارید؟');" class="px-2.5 py-1 text-[11px] rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-500 font-bold hover:bg-indigo-100">
                                            بازگردانی
                                        </button>
                                    </form>
                                    <form action="{{ route('security.quarantine.delete', $file->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('این فایل برای همیشه حذف خواهد شد. ادامه می‌دهید؟');" class="px-2.5 py-1 text-[11px] rounded-lg bg-rose-50 dark:bg-rose-900/40 text-rose-500 font-bold hover:bg-rose-100">
                                            حذف دائم
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                هیچ فایل مشکوکی در قرنطینه موجود نیست.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: LOGIN ATTEMPTS -->
    <div x-show="activeTab === 'logins'" class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/60 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">تاریخچه ورود و تلاش‌های مسدود شده</h3>
                <p class="text-slate-400 text-xs mt-0.5">مشاهده آی‌پی‌ها، موقعیت جغرافیایی و دلایل عدم دسترسی</p>
            </div>
            <form action="{{ route('security.clear-attempts') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('آیا مطمئن هستید؟');" class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                    پاکسازی لاگ ورودها
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700/60">
                    <tr>
                        <th class="pb-3">ایمیل</th>
                        <th class="pb-3">آدرس IP</th>
                        <th class="pb-3">کشور</th>
                        <th class="pb-3">نتیجه</th>
                        <th class="pb-3">دلیل مسدودی</th>
                        <th class="pb-3">زمان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40 font-mono">
                    @forelse($recentLogins as $login)
                        <tr>
                            <td class="py-3 font-bold text-slate-700 dark:text-slate-200">{{ $login->email ?: '—' }}</td>
                            <td class="py-3 text-indigo-500 font-bold">{{ $login->ip_address }}</td>
                            <td class="py-3 text-slate-400">{{ $login->country ?: 'UNKNOWN' }}</td>
                            <td class="py-3 font-sans">
                                @if($login->success)
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-500 font-bold">ورود موفق</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-rose-500/10 text-rose-500 font-bold">ناموفق / مسدود</span>
                                @endif
                            </td>
                            <td class="py-3 text-slate-500 font-sans text-[11px]">{{ $login->blocked_reason ?: '—' }}</td>
                            <td class="py-3 text-slate-400 font-sans text-[11px]">{{ $login->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 font-sans">هنوز لاگ ورودی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentLogins->hasPages())
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    نمایش {{ $recentLogins->firstItem() }} تا {{ $recentLogins->lastItem() }} از {{ $recentLogins->total() }} مورد
                </div>
                <div>
                    {{ $recentLogins->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 5: SECURITY EVENTS -->
    <div x-show="activeTab === 'events'" class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/60 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">رویدادهای امنیتی ثبت‌شده</h3>
                <p class="text-slate-400 text-xs mt-0.5">هشدارها و رخدادهای شناسایی‌شده در کل هسته سرور</p>
            </div>
            <form action="{{ route('security.clear-events') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('آیا مطمئن هستید؟');" class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                    پاکسازی همه رویدادها
                </button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse($recentEvents as $event)
                <div class="p-4 rounded-2xl border flex items-start justify-between gap-4 {{ $event->severity === 'critical' ? 'bg-rose-500/5 border-rose-500/20' : ($event->severity === 'warning' ? 'bg-amber-500/5 border-amber-500/20' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700/40') }}">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold font-mono px-2 py-0.5 rounded uppercase {{ $event->severity === 'critical' ? 'bg-rose-500/20 text-rose-400' : ($event->severity === 'warning' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-400') }}">
                                {{ $event->severity }}
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $event->title }}</h4>
                        </div>
                        @if($event->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $event->description }}</p>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ $event->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-sm">
                    هیچ رویداد امنیتی خاصی ثبت نشده است.
                </div>
            @endforelse
        </div>

        @if($recentEvents->hasPages())
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    نمایش {{ $recentEvents->firstItem() }} تا {{ $recentEvents->lastItem() }} از {{ $recentEvents->total() }} مورد
                </div>
                <div>
                    {{ $recentEvents->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
