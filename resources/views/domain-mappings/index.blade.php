@extends('layouts.app')

@section('title', 'مدیریت دامنه‌ها')

@section('content')
    <div class="space-y-6" x-data="{ tab: 'arvan' }">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">نقشه‌برداری و اتصال دامنه</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">مدیریت دامنه‌های متصل به سرویس‌های CRM (ابرآروان، مستقیم و پارک دامنه).</p>
        </div>

        @if (session('success'))
            <div class="p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if (session('info'))
            <div class="p-4 text-sm text-blue-800 bg-blue-100 rounded-lg dark:bg-blue-900/50 dark:text-blue-300" role="alert">
                {{ session('info') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 rtl:space-x-reverse" aria-label="Tabs">
                <button @click="tab = 'arvan'" :class="{'border-indigo-500 text-indigo-600 dark:text-indigo-400': tab === 'arvan', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': tab !== 'arvan'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    اتصال از طریق ابرآروان
                </button>
                <button @click="tab = 'parked'" :class="{'border-indigo-500 text-indigo-600 dark:text-indigo-400': tab === 'parked', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': tab !== 'parked'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    پارک دامنه دوم
                </button>
                <button @click="tab = 'direct'" :class="{'border-indigo-500 text-indigo-600 dark:text-indigo-400': tab === 'direct', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': tab !== 'direct'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    اتصال مستقیم (بدون آروان)
                </button>
            </nav>
        </div>

        <!-- Tab 1: Arvan Cloud -->
        <div x-show="tab === 'arvan'" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 transition-all">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">اتصال خودکار از طریق API ابرآروان</h3>
            
            @if ($arvanConnection['status'])
                <div class="mb-6 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300 flex items-center">
                    <svg class="inline w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    متصل به Arvan Cloud ({{ count($arvanDomains) }} دامنه یافت شد).
                </div>
                
                <form action="{{ route('domain-mappings.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">۱. دامنه Arvan</label>
                            <select name="arvan_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                                @foreach ($arvanDomains as $domain)
                                    <option value="{{ $domain['name'] }}">{{ $domain['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">۲. ساب‌دامین جدید (اختیاری)</label>
                            <input type="text" name="subdomain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="برای دامنه اصلی خالی بگذارید" pattern="[a-zA-Z0-9-]*">
                        </div>
                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">۳. سرویس هدف (مقصد)</label>
                            <select name="service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->domain }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span>اتصال خودکار</span>
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300">
                    امکان اتصال به Arvan Cloud وجود ندارد. لطفاً API KEY را بررسی کنید.
                </div>
            @endif
        </div>

        <!-- Tab 2: Parked Domain -->
        <div x-show="tab === 'parked'" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 transition-all" style="display: none;" x-data="{ parkedType: 'external' }">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">پارک یک دامنه جدید روی دامنه موجود</h3>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">با این کار، دامنه دوم دقیقاً همان محتوای CRM دامنه اول را نمایش خواهد داد.</p>
            
            <form action="{{ route('domain-mappings.store-parked') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">۱. دامنه اصلی (موجود در سیستم)</label>
                        <select name="parent_mapping_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                            @foreach ($mappings->where('is_primary', true) as $m)
                                <option value="{{ $m->id }}">{{ $m->source_domain }} (سرویس: {{ $m->service->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">۲. دامنه دوم (پارک شونده) کجا مدیریت می‌شود؟</label>
                        <div class="flex items-center space-x-6 rtl:space-x-reverse">
                            <label class="inline-flex items-center">
                                <input type="radio" name="parked_type" value="external" x-model="parkedType" class="text-indigo-600 form-radio focus:ring-indigo-500" required>
                                <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">مدیریت دستی (سایر شرکت‌ها / تنظیم دستی DNS)</span>
                            </label>
                            @if ($arvanConnection['status'])
                            <label class="inline-flex items-center">
                                <input type="radio" name="parked_type" value="arvan" x-model="parkedType" class="text-indigo-600 form-radio focus:ring-indigo-500" required>
                                <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">ابرآروان (تولید خودکار CNAME)</span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <!-- External Domain Input -->
                    <div x-show="parkedType === 'external'">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded text-sm text-blue-800 dark:text-blue-300 mb-4 border border-blue-200 dark:border-blue-800">
                            <strong>توجه:</strong> شما باید در پنل دامنه خود، یک رکورد <code>A</code> به IP سرور <code>{{ $serverIp ?? 'مشخص نیست' }}</code> یا یک رکورد <code>CNAME</code> به دامنه اصلی متصل کنید.
                        </div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام دامنه دوم (به صورت کامل)</label>
                        <input type="text" name="parked_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="مثال: panel.second-domain.com" :required="parkedType === 'external'" :disabled="parkedType !== 'external'">
                    </div>

                    <!-- Arvan Domain Input -->
                    @if ($arvanConnection['status'])
                    <div x-show="parkedType === 'arvan'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">دامنه Arvan</label>
                            <select name="arvan_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" :required="parkedType === 'arvan'">
                                @foreach ($arvanDomains as $domain)
                                    <option value="{{ $domain['name'] }}">{{ $domain['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ساب‌دامین جدید (اختیاری)</label>
                            <input type="text" name="parked_subdomain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="برای دامنه اصلی خالی بگذارید">
                            <!-- Hidden input to supply parked_domain for arvan type via controller logic -->
                            <input type="hidden" name="parked_domain" value="arvan_placeholder" :disabled="parkedType !== 'arvan'">
                        </div>
                    </div>
                    @endif

                    <button type="submit" class="mt-4 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                        پارک دامنه و تنظیم Nginx
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab 3: Direct NS -->
        <div x-show="tab === 'direct'" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 transition-all" style="display: none;">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">اتصال مستقیم (بدون آروان)</h3>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                در این روش شما ابتدا باید IP سرور ما را در پنل دامنه مشتری تنظیم کنید. سیستم ما فقط تنطیمات وب‌سرور (Nginx) را برای آن انجام می‌دهد.
            </p>
            
            <div class="mb-6 p-4 border border-yellow-300 bg-yellow-50 dark:bg-yellow-900/30 rounded text-sm text-yellow-800 dark:text-yellow-300">
                <strong>راهنما:</strong> مشتری شما باید یک رکورد <code>A</code> به آدرس IP 
                <code class="font-bold bg-white dark:bg-gray-800 px-1 py-0.5 rounded">{{ $serverIp ?? 'IP سرور را پیدا نکردیم' }}</code>
                در تنظیمات دامنه خود ایجاد کند. پس از انجام این کار، دامنه را در زیر وارد کنید.
            </div>

            <form action="{{ route('domain-mappings.store-direct') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام دامنه مشتری (کامل)</label>
                        <input type="text" name="direct_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="مثال: crm.client-domain.com" required>
                    </div>
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">سرویس هدف (مقصد)</label>
                        <select name="service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->domain }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:col-span-2">
                        <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                            ثبت دامنه و اعمال تنظیمات وب‌سرور
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Existing Mappings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mt-8">
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">دامنه‌های متصل شده</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">دامنه متصل (مبدا)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نوع اتصال</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مقصد (سرویس)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($mappings as $mapping)
                        <tr class="text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="http://{{ $mapping->source_domain }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold">{{ $mapping->source_domain }}</a>
                                @if (!$mapping->is_primary)
                                    <br><span class="text-xs text-gray-500">پارک شده روی: {{ $mapping->parentMapping->source_domain ?? 'نامشخص' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($mapping->mapping_type === 'arvan')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">ابرآروان</span>
                                @elseif ($mapping->mapping_type === 'parked')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">دامنه پارک‌شده</span>
                                    @if ($mapping->arvan_domain)
                                        <span class="text-xs text-gray-500 block mt-1">(مدیریت با آروان)</span>
                                    @else
                                        <span class="text-xs text-gray-500 block mt-1">(مدیریت دستی)</span>
                                    @endif
                                @elseif ($mapping->mapping_type === 'direct')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">مستقیم (بدون CDN)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $mapping->service->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('domain-mappings.reprovision', $mapping) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            پیکربندی مجدد
                                        </button>
                                    </form>
                                    <form action="{{ route('domain-mappings.destroy', $mapping) }}" method="POST" onsubmit="return confirm('آیا از حذف این اتصال مطمئن هستید؟ با این کار رکورد DNS مربوطه نیز حذف می‌شود (در صورت دسترسی).')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                هیچ دامنه‌ای متصل نشده است.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection