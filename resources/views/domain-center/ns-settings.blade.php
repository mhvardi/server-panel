@extends('layouts.app')

@section('title', 'تنظیمات Name Servers')

@section('content')
<div class="space-y-6" dir="rtl"
     x-data="{
         domain: '',
         checking: false,
         result: null,
         async checkDns() {
             if (!this.domain.trim()) return;
             this.checking = true;
             this.result = null;
             try {
                 const res = await fetch('{{ route('domain-center.check-dns') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json',
                     },
                     body: JSON.stringify({ domain: this.domain })
                 });
                 this.result = await res.json();
             } catch(e) {
                 this.result = { error: 'خطا در بررسی DNS' };
             }
             this.checking = false;
         }
     }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">تنظیمات Name Servers</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">برای استفاده از NS سرور ما، باید رکوردهای NS دامنه شما را به آدرس‌های زیر تغییر دهید.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- NS Addresses Card --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
                آدرس‌های Name Server
            </h2>
            <div class="space-y-3">
                @foreach($nsRecords as $ns)
                    <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 rounded-xl px-4 py-3">
                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-medium">{{ $ns }}</span>
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ $ns }}').then(() => { this.textContent='کپی شد!'; setTimeout(() => this.textContent='کپی', 1500); })"
                                class="text-xs text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors px-2 py-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600">
                            کپی
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between bg-slate-50 dark:bg-slate-700/50 rounded-xl px-4 py-3">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">IP سرور</p>
                    <p class="font-mono text-slate-800 dark:text-white font-medium">{{ $serverIp ?? 'نامشخص' }}</p>
                </div>
                @if($serverIp)
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $serverIp }}').then(() => { this.textContent='کپی شد!'; setTimeout(() => this.textContent='کپی', 1500); })"
                        class="text-xs text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors px-2 py-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600">
                    کپی
                </button>
                @endif
            </div>
        </div>

        {{-- Step by Step Guide --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                راهنمای مرحله‌به‌مرحله
            </h2>
            <ol class="space-y-4">
                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black shrink-0 mt-0.5">1</span>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">وارد پنل ثبت‌کننده دامنه خود شوید</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">مانند NIC.ir، GoDaddy، Cloudflare و غیره</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black shrink-0 mt-0.5">2</span>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">بخش Name Servers یا DNS Management را بیابید</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">معمولاً در تنظیمات دامنه</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black shrink-0 mt-0.5">3</span>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">NS های فعلی را حذف و NS های ما را اضافه کنید</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($nsRecords as $ns)
                                <span class="text-xs font-mono bg-indigo-100 dark:bg-indigo-900/40 px-2 py-1 rounded-lg text-indigo-600 dark:text-indigo-400">{{ $ns }}</span>
                            @endforeach
                        </div>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black shrink-0 mt-0.5">4</span>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">منتظر انتشار DNS بمانید</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">معمولاً بین ۱ تا ۴۸ ساعت طول می‌کشد</p>
                    </div>
                </li>
            </ol>
        </div>

        {{-- Parked Page Info --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                صفحه پیش‌فرض پارک
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                دامنه‌هایی که NS آن‌ها به سرور ما اشاره می‌کند ولی هنوز به سرویسی تخصیص داده نشده‌اند،
                یک صفحه پیش‌فرض «پارک شده» نمایش می‌دهند تا زمانی که به سرویس وصل شوند.
            </p>
            <div class="mt-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-xs text-amber-800 dark:text-amber-300">
                مسیر صفحه پارک: <code class="font-mono">/var/www/parked-domain/index.html</code>
            </div>
        </div>

        {{-- DNS Test Tool --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                تست وضعیت DNS دامنه
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">بررسی کنید دامنه‌ای به NS / IP سرور ما اشاره می‌کند یا نه.</p>

            <div class="flex gap-2 mb-4">
                <input type="text" x-model="domain"
                       placeholder="example.com"
                       class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                       @keyup.enter="checkDns()">
                <button type="button" @click="checkDns()" :disabled="checking"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-60">
                    <svg x-show="checking" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-show="!checking">بررسی</span>
                    <span x-show="checking">...</span>
                </button>
            </div>

            <div x-show="result" x-transition class="rounded-xl p-4 border text-sm space-y-2"
                 :class="result && result.points_to_us ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-slate-50 border-slate-200 dark:bg-slate-700/50 dark:border-slate-600 text-slate-800 dark:text-slate-200'">
                <template x-if="result && !result.error">
                    <div class="space-y-2">
                        <p x-text="result.points_to_us ? 'رکورد A: به IP سرور اشاره می‌کند' : 'رکورد A: به IP سرور اشاره نمی‌کند'"></p>
                        <p x-text="result.ns_points_to_us ? 'NS: از Name Server های ما استفاده می‌کند' : 'NS: خارجی'"></p>
                        <p class="text-xs opacity-70" x-show="result.ns && result.ns.length > 0">
                            NS ها: <span x-text="result.ns ? result.ns.join(' | ') : ''"></span>
                        </p>
                        <p class="text-xs opacity-70">
                            IP سرور: <span class="font-mono" x-text="result.server_ip"></span>
                        </p>
                    </div>
                </template>
                <template x-if="result && result.error">
                    <p x-text="result.error" class="text-red-600 dark:text-red-400"></p>
                </template>
            </div>
        </div>

    </div>
</div>
@endsection
