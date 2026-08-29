@extends('layouts.app')

@section('title', 'اتصال دامنه')

@section('content')
<div class="space-y-6" dir="rtl" x-data="{ tab: 'arvan' }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">اتصال دامنه جدید</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">دامنه خود را از طریق ابرآروان یا اتصال مستقیم به سرور وصل کنید.</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Tab Bar --}}
    <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-fit">
        <button @click="tab = 'arvan'"
                :class="tab === 'arvan' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                class="px-5 py-2.5 rounded-lg text-sm font-bold transition-all">
            ☁ ابرآروان
        </button>
        <button @click="tab = 'direct'"
                :class="tab === 'direct' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                class="px-5 py-2.5 rounded-lg text-sm font-bold transition-all">
            🔗 اتصال مستقیم
        </button>
    </div>

    {{-- Tab 1: ArvanCloud --}}
    <div x-show="tab === 'arvan'" x-transition>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">اتصال از طریق ابرآروان</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">رکورد DNS به‌صورت خودکار از طریق API آروان افزوده می‌شود.</p>

            @if($arvanConnection['status'])
                <div class="flex items-center gap-2 mb-6 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    متصل به Arvan Cloud &mdash; {{ count($arvanDomains) }} دامنه یافت شد
                </div>
            @else
                <div class="flex items-center gap-2 mb-6 p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    عدم اتصال به Arvan Cloud: {{ $arvanConnection['message'] ?? 'خطای ناشناخته' }}
                </div>
            @endif

            @if($arvanConnection['status'])
            <form action="{{ route('domain-center.connect.arvan') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">دامنه اصلی آروان <span class="text-red-500">*</span></label>
                        <select name="arvan_domain" required
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">انتخاب کنید...</option>
                            @foreach($arvanDomains as $arvanDomain)
                                <option value="{{ $arvanDomain['name'] }}" {{ old('arvan_domain') === $arvanDomain['name'] ? 'selected' : '' }}>
                                    {{ $arvanDomain['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">مثال: client.com</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">زیردامنه (اختیاری)</label>
                        <input type="text" name="subdomain" value="{{ old('subdomain') }}"
                               placeholder="crm"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        <p class="mt-1 text-xs text-slate-400">خالی بگذارید برای دامنه اصلی (@)</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">سرویس مقصد <span class="text-red-500">*</span></label>
                    <select name="service_id" required
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">انتخاب کنید...</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" {{ old('service_id') == $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25">
                        اتصال از طریق آروان
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Tab 2: Direct --}}
    <div x-show="tab === 'direct'" x-transition
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
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">اتصال مستقیم (بدون آروان)</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">برای دامنه‌هایی که DNS آن‌ها را خودتان کنترل می‌کنید.</p>

            <div class="flex items-center gap-3 mb-6 p-3 rounded-xl bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 text-sky-700 dark:text-sky-300 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>IP سرور: <strong class="font-mono">{{ $serverIp ?? 'نامشخص' }}</strong> &mdash; رکورد A دامنه باید به این IP اشاره کند.</span>
            </div>

            <form action="{{ route('domain-center.connect.direct') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">آدرس دامنه <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="direct_domain"
                               x-model="domain"
                               value="{{ old('direct_domain') }}"
                               placeholder="example.com"
                               class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" required>
                        <button type="button" @click="checkDns()" :disabled="checking"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-60">
                            <svg x-show="checking" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-show="!checking">بررسی DNS</span>
                            <span x-show="checking">در حال بررسی...</span>
                        </button>
                    </div>
                </div>

                <div x-show="result" x-transition class="rounded-xl p-4 border text-sm space-y-2"
                     :class="result && result.points_to_us ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-amber-50 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800 text-amber-800 dark:text-amber-300'">
                    <template x-if="result && !result.error">
                        <div class="space-y-1.5">
                            <p x-text="result.points_to_us ? 'رکورد A به IP سرور اشاره می‌کند.' : 'رکورد A به IP سرور اشاره نمی‌کند.'"></p>
                            <p>وضعیت NS: <span x-text="result.ns_points_to_us ? 'NS های ما استفاده می‌شود' : 'NS خارجی'"></span></p>
                            <p class="text-xs opacity-70" x-show="result.ns && result.ns.length > 0">
                                NS ها: <span x-text="result.ns ? result.ns.join(', ') : ''"></span>
                            </p>
                        </div>
                    </template>
                    <template x-if="result && result.error">
                        <p x-text="result.error"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">سرویس مقصد <span class="text-red-500">*</span></label>
                    <select name="service_id" required
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">انتخاب کنید...</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" {{ old('service_id') == $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25">
                        اتصال دامنه مستقیم
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
