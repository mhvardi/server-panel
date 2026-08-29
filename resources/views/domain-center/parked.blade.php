@extends('layouts.app')

@section('title', 'پارک دامین')

@section('content')
<div class="space-y-6" x-data="{ parkedType: 'external' }">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">پارک دامین</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            دامنه دوم را روی یک سرویس موجود پارک کنید. دامنه دوم دقیقاً همان محتوای سرویس اول را نمایش می‌دهد.
        </p>
    </div>

    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- How it works info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <h4 class="font-bold text-blue-800 dark:text-blue-300 text-sm mb-2">نحوه کارکرد</h4>
        <ol class="list-decimal list-inside text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li>دامنه اصلی (که به سرویس متصل است) را انتخاب کنید</li>
            <li>دامنه دوم را که می‌خواهید پارک شود وارد کنید</li>
            <li>سیستم Nginx را طوری تنظیم می‌کند که هر دو دامنه همان سرویس را نمایش دهند</li>
            <li>SSL برای دامنه دوم نیز به صورت مجزا صادر می‌شود</li>
        </ol>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form action="{{ route('domain-center.parked.store') }}" method="POST">
            @csrf
            <div class="space-y-6">

                <!-- Step 1: Select primary domain -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                        ۱. دامنه اصلی (متصل به سرویس)
                    </label>
                    @if($connectedDomains->isEmpty())
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-300">
                            هنوز هیچ دامنه‌ای به سرویس متصل نشده است.
                            <a href="{{ route('domain-center.connect') }}" class="font-bold underline">ابتدا یک دامنه متصل کنید</a>.
                        </div>
                    @else
                        <select name="parent_domain_id" required
                                class="w-full md:w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            @foreach($connectedDomains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }} (سرویس: {{ $d->service->name ?? '—' }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Step 2: DNS provider of second domain -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        ۲. دامنه دوم کجا مدیریت می‌شود؟
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="parked_type" value="external" x-model="parkedType"
                                   class="text-indigo-600 form-radio focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>خارجی / NS ما</strong>
                                <span class="text-gray-400 text-xs block">مشتری A record یا NS ما را تنظیم کرده</span>
                            </span>
                        </label>
                        @if($arvanConnection['status'])
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="parked_type" value="arvan" x-model="parkedType"
                                   class="text-indigo-600 form-radio focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>ابرآروان</strong>
                                <span class="text-gray-400 text-xs block">دامنه دوم در پنل آروان ماست</span>
                            </span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Step 3a: External domain input -->
                <div x-show="parkedType === 'external'">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">۳. نام کامل دامنه دوم</label>
                    <div class="mb-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl text-xs text-yellow-700 dark:text-yellow-400">
                        مطمئن شوید مشتری قبلاً رکورد A به IP
                        <code class="font-bold">{{ $serverIp ?? '—' }}</code>
                        یا NS به <code class="font-bold">ns1.vardicrm.ir</code> تنظیم کرده است.
                    </div>
                    <input type="text" name="parked_domain"
                           placeholder="مثال: crm.second-site.com"
                           :required="parkedType === 'external'"
                           :disabled="parkedType !== 'external'"
                           class="w-full md:w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>

                <!-- Step 3b: Arvan domain input -->
                @if($arvanConnection['status'])
                <div x-show="parkedType === 'arvan'" class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display:none">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">۳. دامنه Arvan</label>
                        <select name="arvan_domain"
                                :required="parkedType === 'arvan'"
                                :disabled="parkedType !== 'arvan'"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            @foreach($arvanDomains as $d)
                                <option value="{{ $d['name'] }}">{{ $d['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">ساب‌دامین (اختیاری)</label>
                        <input type="text" name="parked_subdomain" pattern="[a-zA-Z0-9-]*"
                               placeholder="خالی = دامنه اصلی"
                               :disabled="parkedType !== 'arvan'"
                               class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    </div>
                </div>
                @endif

                <div>
                    <button type="submit" @if($connectedDomains->isEmpty()) disabled @endif
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-600/25 disabled:opacity-50 disabled:cursor-not-allowed">
                        پارک دامنه و تنظیم Nginx
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
