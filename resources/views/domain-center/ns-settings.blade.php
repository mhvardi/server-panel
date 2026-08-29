@extends('layouts.app')

@section('title', 'تنظیمات Name Servers')

@section('content')
<div class="space-y-6" x-data="{ domain: '', checking: false, result: null }">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">تنظیمات Name Servers</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            اطلاعات لازم برای اتصال دامنه‌ها از طریق سرور DNS ما
        </p>
    </div>

    <!-- NS Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                Name Servers سرور ما
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-xl px-4 py-3">
                    <code class="font-bold text-indigo-600 dark:text-indigo-400">ns1.vardicrm.ir</code>
                    <button onclick="navigator.clipboard.writeText('ns1.vardicrm.ir')"
                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        کپی
                    </button>
                </div>
                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-xl px-4 py-3">
                    <code class="font-bold text-indigo-600 dark:text-indigo-400">ns2.vardicrm.ir</code>
                    <button onclick="navigator.clipboard.writeText('ns2.vardicrm.ir')"
                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        کپی
                    </button>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                برای استفاده از NS سرور ما، مشتری باید NS دامنه‌اش را در رجیستری به مقادیر بالا تغییر دهد.
                تغییرات DNS ممکن است تا ۴۸ ساعت زمان ببرد.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.276A11.952 11.952 0 0112 2.944a11.952 11.952 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                IP سرور (رکورد A)
            </h3>
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-xl px-4 py-3">
                <code class="font-bold text-2xl text-emerald-600 dark:text-emerald-400">{{ $serverIp ?? 'نامشخص' }}</code>
                @if($serverIp)
                <button onclick="navigator.clipboard.writeText('{{ $serverIp }}')"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    کپی
                </button>
                @endif
            </div>
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                اگر مشتری نمی‌خواهد NS را تغییر دهد، می‌تواند فقط یک رکورد A با این IP در پنل دامنه خود بسازد.
            </p>
        </div>
    </div>

    <!-- DNS Checker -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            بررسی وضعیت DNS دامنه
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            بررسی کنید که آیا یک دامنه به NS یا IP سرور ما اشاره می‌کند.
        </p>
        <div class="flex gap-3 items-start flex-wrap">
            <input type="text" x-model="domain"
                   placeholder="domain.com یا sub.domain.com"
                   class="flex-1 min-w-48 rounded-xl border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            <button @click="
                        if(!domain) return;
                        checking=true; result=null;
                        fetch('{{ route('domain-center.check-dns') }}', {
                            method: 'POST',
                            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            body: JSON.stringify({domain})
                        }).then(r=>r.json()).then(d=>{result=d; checking=false}).catch(()=>{checking=false})
                    "
                    :class="checking ? 'opacity-70 cursor-wait' : ''"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-colors">
                <span x-show="!checking">بررسی DNS</span>
                <span x-show="checking">در حال بررسی...</span>
            </button>
        </div>

        <!-- Result -->
        <div x-show="result" class="mt-4 p-4 rounded-xl border space-y-3"
             :class="result && result.points_to_us ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800' : 'bg-amber-50 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800'">
            <div class="flex items-center gap-2">
                <span x-show="result && result.points_to_us" class="text-emerald-700 dark:text-emerald-300 font-bold text-sm">
                    ✓ دامنه به سرور ما اشاره می‌کند
                </span>
                <span x-show="result && !result.points_to_us" class="text-amber-700 dark:text-amber-300 font-bold text-sm">
                    ⚠ دامنه هنوز به سرور ما اشاره نمی‌کند
                </span>
            </div>
            <div class="text-xs space-y-1">
                <template x-if="result && result.a_records && result.a_records.length">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">رکوردهای A:</span>
                        <span x-text="result.a_records.join(', ')" class="font-mono text-gray-700 dark:text-gray-300 mr-1"></span>
                    </div>
                </template>
                <template x-if="result && result.ns_records && result.ns_records.length">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Name Servers:</span>
                        <span x-text="result.ns_records.join(', ')" class="font-mono text-gray-700 dark:text-gray-300 mr-1"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Setup Guide -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm mb-4">راهنمای مرحله به مرحله اتصال دامنه</h3>
        <ol class="space-y-4">
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-black flex items-center justify-center">۱</span>
                <div>
                    <p class="font-bold text-gray-700 dark:text-gray-300 text-sm">تغییر NS یا ساخت A record</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">مشتری باید در رجیستری دامنه‌اش NS را به <code>ns1.vardicrm.ir</code> یا رکورد A را به IP سرور ما تغییر دهد.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-black flex items-center justify-center">۲</span>
                <div>
                    <p class="font-bold text-gray-700 dark:text-gray-300 text-sm">انتظار برای propagation</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">تغییرات DNS می‌توانند تا ۴۸ ساعت طول بکشند. برای A record معمولاً چند دقیقه کافی است.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-black flex items-center justify-center">۳</span>
                <div>
                    <p class="font-bold text-gray-700 dark:text-gray-300 text-sm">بررسی و ثبت در پنل</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">پس از اطمینان از اشاره DNS، از صفحه <a href="{{ route('domain-center.connect') }}" class="text-indigo-600 dark:text-indigo-400 underline">اتصال دامنه</a> دامنه را به سرویس موردنظر متصل کنید.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-600 text-white text-sm font-black flex items-center justify-center">✓</span>
                <div>
                    <p class="font-bold text-gray-700 dark:text-gray-300 text-sm">SSL خودکار صادر می‌شود</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">سیستم به صورت خودکار گواهینامه Let's Encrypt را برای دامنه صادر و تمدید می‌کند.</p>
                </div>
            </li>
        </ol>
    </div>
</div>
@endsection
