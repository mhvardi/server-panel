@extends('layouts.app')

@section('title', 'پارک دامنه')

@section('content')
<div class="space-y-6" dir="rtl"
     x-data="{
         parkedType: '{{ old('parked_type', 'arvan') }}',
         arvanDomains: {{ json_encode($arvanDomains) }},
         hasArvan: {{ $arvanConnection['status'] ? 'true' : 'false' }},
     }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">پارک دامنه دوم</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">یک دامنه دیگر را روی یکی از دامنه‌های متصل پارک کنید تا هر دو به یک سرویس برسند.</p>
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

    @if($connectedDomains->isEmpty())
        <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
            <p class="text-amber-800 dark:text-amber-300 font-medium">هیچ دامنه متصلی وجود ندارد.</p>
            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">ابتدا یک دامنه را از طریق صفحه <a href="{{ route('domain-center.connect') }}" class="underline">اتصال دامنه</a> وصل کنید.</p>
        </div>
    @else
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">

        <form action="{{ route('domain-center.parked.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Step 1: Select parent domain --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    ۱. دامنه اصلی (پارک روی کدام دامنه؟) <span class="text-red-500">*</span>
                </label>
                <select name="parent_domain_id" required
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">انتخاب دامنه اصلی...</option>
                    @foreach($connectedDomains as $cd)
                        <option value="{{ $cd->id }}" {{ old('parent_domain_id') == $cd->id ? 'selected' : '' }}>
                            {{ $cd->domain }} @if($cd->service)({{ $cd->service->name }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Step 2: Parked domain type --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
                    ۲. نوع دامنه دوم
                </label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="parked_type" value="arvan" x-model="parkedType" class="sr-only">
                        <div :class="parkedType === 'arvan' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-500' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500'"
                             class="border-2 rounded-xl p-4 transition-colors">
                            <p class="font-bold text-slate-800 dark:text-white text-sm">آروان</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">رکورد DNS خودکار اضافه می‌شود</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="parked_type" value="external" x-model="parkedType" class="sr-only">
                        <div :class="parkedType === 'external' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-500' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500'"
                             class="border-2 rounded-xl p-4 transition-colors">
                            <p class="font-bold text-slate-800 dark:text-white text-sm">خارجی / دستی</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">DNS دامنه را خودتان مدیریت می‌کنید</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Step 3a: ArvanCloud option --}}
            <div x-show="parkedType === 'arvan'" x-transition class="space-y-4">
                @if(!$arvanConnection['status'])
                    <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                        ابرآروان متصل نیست. برای استفاده از این گزینه، ARVAN_API_KEY را در .env تنظیم کنید.
                    </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">دامنه اصلی آروان</label>
                        <select name="arvan_domain"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">انتخاب کنید...</option>
                            @foreach($arvanDomains as $arvanDomain)
                                <option value="{{ $arvanDomain['name'] }}" {{ old('arvan_domain') === $arvanDomain['name'] ? 'selected' : '' }}>
                                    {{ $arvanDomain['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">زیردامنه (اختیاری)</label>
                        <input type="text" name="subdomain" value="{{ old('subdomain') }}"
                               placeholder="shop"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                </div>
                @endif
            </div>

            {{-- Step 3b: External/Manual option --}}
            <div x-show="parkedType === 'external'" x-transition class="space-y-4">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 text-sky-700 dark:text-sky-300 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>رکورد A دامنه را روی IP سرور <strong class="font-mono">{{ $serverIp ?? 'نامشخص' }}</strong> تنظیم کنید.</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">آدرس دامنه دوم</label>
                    <input type="text" name="parked_domain" value="{{ old('parked_domain') }}"
                           placeholder="shop.example.com"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            {{-- Submit --}}
            <div class="pt-2 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <a href="{{ route('domain-center.domains') }}"
                   class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    بازگشت به لیست دامنه‌ها
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25">
                    پارک دامنه دوم
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
