@extends('layouts.app')

@section('title', 'نقشه‌برداری دامنه')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">نقشه‌برداری خودکار دامنه</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">ایجاد خودکار رکورد CNAME در Arvan Cloud و پیکربندی سرویس شما.</p>
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

        <!-- Arvan Cloud Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">وضعیت Arvan Cloud</h3>
            </div>
            <div class="p-4 sm:p-6">
                @if ($arvanConnection['status'])
                    <div class="p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300 flex items-center">
                        <svg class="inline w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        اتصال به Arvan Cloud با موفقیت برقرار شد. {{ count($arvanDomains) }} دامنه یافت شد.
                    </div>
                @else
                    <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300">
                        <div class="flex items-center">
                            <svg class="inline w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            {{ $arvanConnection['message'] ?? 'امکان اتصال به Arvan Cloud وجود ندارد.' }}
                        </div>
                        <small class="block mt-2">لطفاً اطمینان حاصل کنید که <code>ARVAN_API_KEY</code> در فایل <code>.env</code> شما به درستی تنظیم شده باشد.</small>
                    </div>
                @endif
            </div>
        </div>

        <!-- Create New Mapping -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">ایجاد نقشه‌برداری خودکار جدید</h3>
            </div>
            <div class="p-4 sm:p-6">
                <form action="{{ route('domain-mappings.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div class="w-full">
                            <label for="arvan_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">۱. انتخاب دامنه Arvan</label>
                            <select name="arvan_domain" id="arvan_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                                @foreach ($arvanDomains as $domain)
                                    <option value="{{ $domain['name'] }}">{{ $domain['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <label for="service_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">۲. انتخاب سرویس داخلی</label>
                            <select name="service_id" id="service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->domain }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full">
                            <label for="subdomain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">۳. ساب‌دامین جدید (اختیاری)</label>
                            <!-- ویژگی required حذف شد و pattern و placeholder تغییر کرد -->
                            <input type="text" name="subdomain" id="subdomain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="برای دامنه اصلی خالی بگذارید..." pattern="[a-zA-Z0-9-]*">
                        </div>
                        <div class="w-full">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50" {{ !$arvanConnection['status'] ? 'disabled' : '' }}>
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                <span>اتصال خودکار</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Mappings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">نقشه‌برداری‌های موجود</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مبدا (دامنه مشتری)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">مقصد (سرویس شما)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">سرویس متصل</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($mappings as $mapping)
                        <tr class="text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="http://{{ $mapping->source_domain }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $mapping->source_domain }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="http://{{ $mapping->destination_domain }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $mapping->destination_domain }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $mapping->service->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('domain-mappings.reprovision', $mapping) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50" {{ !$arvanConnection['status'] ? 'disabled' : '' }}>
                                            اعمال پیکربندی
                                        </button>
                                    </form>
                                    <form action="{{ route('domain-mappings.destroy', $mapping) }}" method="POST" onsubmit="return confirm('آیا مطمئن هستید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50" {{ !$arvanConnection['status'] ? 'disabled' : '' }}>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                هیچ نقشه‌برداری دامنه یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection