@extends('layouts.app')

@section('title', 'مدیریت سرویس: ' . $service->name)

@section('content')
    <div class="max-w-7xl mx-auto" x-data="{
    activeTab: 'overview',
    showCreateFileModal: false,
    showUploadFileModal: false
}">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">مدیریت سرویس: {{ $service->name }}</h1>
            <div class="flex gap-2">
                <a href="{{ route('services.analyze', $service->id) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>آنالیزور منابع</span>
                </a>
                <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-lg shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    <span>بازگشت</span>
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
                <pre class="mb-0 font-sans whitespace-pre-wrap">{{ session('success') }}</pre>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                <ul class="mb-0 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tab Navigation --}}
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex flex-wrap" aria-label="Tabs">
                    <button @click="activeTab = 'overview'" :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'overview' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">پیشخوان</button>
                    <button @click="activeTab = 'deploy'" :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'deploy', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'deploy' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">نصب / بروزرسانی</button>
                    <button @click="activeTab = 'files'" :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'files', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'files' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">مدیریت فایل‌ها</button>
                    <button @click="activeTab = 'commands'" :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'commands', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'commands' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">دستورات</button>
                    @if($service->type === 'subdomain')
                        <button @click="activeTab = 'logs'; fetchLogs()" :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'logs', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'logs' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">لاگ‌های Nginx</button>
                    @endif
                    <button @click="activeTab = 'danger'" :class="{ 'border-red-500 text-red-600 dark:text-red-400': activeTab === 'danger', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'danger' }" class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm focus:outline-none">منطقه خطر</button>
                </nav>
            </div>
        </div>

        {{-- Tab Content --}}
        <div class="space-y-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">اقدامات سریع</h3>
                        </div>
                        <div class="p-4 sm:p-6 flex flex-wrap gap-3">
                            @if($hasGit)
                                <form action="{{ route('services.git', $service->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="pull">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                        <span>گیت پول</span>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="migrate">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-indigo-600 text-sm font-medium rounded-md text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m-1.5 1.5h8.25A3.375 3.375 0 0012 9.75h0a3.375 3.375 0 00-3.375 3.375H2.25M12 12.75h8.25" /></svg>
                                    <span>اجرای Migration</span>
                                </button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="optimize">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                                    <span>بهینه‌سازی</span>
                                </button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="cache:clear">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75L14.25 12m0 0l2.25 2.25M14.25 12L12 9.75m2.25 2.25L12 14.25m-2.25 2.25L7.5 12m0 0l-2.25-2.25M7.5 12L9.75 14.25m-2.25-2.25L9.75 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>پاکسازی Cache</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1 flex flex-col gap-6">
                    <!-- Service Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">جزئیات سرویس</h3>
                        </div>
                        <div class="p-4 sm:p-6 text-sm text-gray-700 dark:text-gray-300 space-y-4">
                            @php
                                $clientDomains = $service->domainMappings ?? collect();
                            @endphp

                            <!-- Custom Client Domain Section -->
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800" x-data="{ showAddDomain: false }">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                        دامنه اختصاصی مشتری (مبدا):
                                    </span>
                                    <button type="button" @click="showAddDomain = !showAddDomain" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 inline-flex items-center gap-1">
                                        <span x-text="showAddDomain ? 'بستن' : '+ افزودن / تغییر دامنه'"></span>
                                    </button>
                                </div>

                                @if($clientDomains->isNotEmpty())
                                    <div class="space-y-1.5 mb-2">
                                        @foreach($clientDomains as $mapping)
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 px-2.5 py-1.5 rounded-lg border border-emerald-100 dark:border-emerald-900/60">
                                                <a href="https://{{ $mapping->source_domain }}" target="_blank" class="text-xs font-bold text-emerald-600 hover:underline dark:text-emerald-400 break-all inline-flex items-center gap-1" dir="ltr">
                                                    https://{{ $mapping->source_domain }}
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                </a>
                                                <form action="{{ route('services.custom-domain.destroy', [$service->id, $mapping->id]) }}" method="POST" onsubmit="return confirm('آیا از حذف این دامنه اختصاصی مطمئن هستید؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs p-1" title="حذف دامنه اختصاصی">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">هنوز دامنه اختصاصی برای این سرویس ثبت نشده است.</p>
                                @endif

                                <!-- Add/Edit Domain Form -->
                                <div x-show="showAddDomain" x-cloak class="mt-2 pt-2 border-t border-emerald-200 dark:border-emerald-800">
                                    <form action="{{ route('services.custom-domain.store', $service->id) }}" method="POST">
                                        @csrf
                                        <div class="mb-2">
                                            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">نام دامنه مشتری (بدون http/https):</label>
                                            <input type="text" name="custom_domain" placeholder="مثال: panel.shafa.doctor" required dir="ltr" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-left">
                                        </div>
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" name="issue_ssl" id="issue_ssl_domain" value="1" checked class="h-3.5 w-3.5 text-emerald-600 rounded">
                                            <label for="issue_ssl_domain" class="mr-1.5 text-[11px] text-gray-700 dark:text-gray-300">صدور خودکار گواهینامه SSL برای این دامنه</label>
                                        </div>
                                        <button type="submit" class="w-full py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm">
                                            ذخیره و ثبت دامنه
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p><strong>{{ $clientDomains->isNotEmpty() ? 'دامنه پایه (مقصد):' : 'دامنه سرویس:' }}</strong>
                                <a href="http://{{ $service->type == 'subfolder' ? env('APP_MAIN_DOMAIN', request()->getHost()) . '/' . $service->domain : $service->domain }}"
                                   target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400 break-all" dir="ltr">
                                    {{ $service->type == 'subfolder' ? env('APP_MAIN_DOMAIN', request()->getHost()) . '/' . $service->domain : $service->domain }}
                                </a>
                            </p>
                            <p><strong>نوع:</strong>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100">
                                {{ $service->type == 'subfolder' ? 'ساب‌فولدر' : 'ساب‌دامین' }}
                            </span>
                            </p>
                            <p><strong>مسیر:</strong> <code class="bg-gray-100 dark:bg-gray-700 p-1 rounded text-xs text-gray-800 dark:text-gray-200 break-all" dir="ltr">{{ $service->path }}</code></p>
                            <p><strong>تاریخ ایجاد:</strong> {{ $service->created_at->format('Y-m-d H:i') }} ({{ $service->created_at->diffForHumans() }})</p>
                        </div>
                    </div>
                    <!-- Disk Usage Widget -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">مصرف حافظه دیسک</h3>
                        </div>
                        <div class="p-4 sm:p-6 text-sm text-gray-700 dark:text-gray-300">
                            @php
                                $diskUsageInMB = $diskUsageInMB ?? 0;
                                $diskUsageFormatted = $diskUsageInMB > 1024 ? number_format($diskUsageInMB / 1024, 2) . ' گیگابایت' : number_format($diskUsageInMB, 2) . ' مگابایت';
                            @endphp
                            <div class="flex items-center justify-center text-center">
                                <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ $diskUsageFormatted }}</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($service->type === 'subdomain')
                        <!-- SSL Status Widget -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md" x-data="{ selectedTargetDomain: '{{ $sslStatus['checked_domain'] ?? $service->getPrimaryDomain() }}' }">
                            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">وضعیت SSL (گواهینامه)</h3>
                            </div>
                            <div class="p-4 sm:p-6 space-y-4">
                                <div class="pb-3 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">دامنه مورد سنجش SSL:</span>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 break-all" dir="ltr">{{ $sslStatus['checked_domain'] ?? $service->domain }}</span>
                                        @if(!empty($sslStatus['checked_domain']) && $sslStatus['checked_domain'] !== $service->domain)
                                            <span class="text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-1.5 py-0.5 rounded font-semibold">دامنه مشتری</span>
                                        @else
                                            <span class="text-[10px] bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">دامنه سرویس</span>
                                        @endif
                                    </div>
                                </div>

                                @if($sslStatus['status'] === 'valid')
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 rtl:mr-3 rtl:ml-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">فعال و معتبر</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">انقضا در {{ $sslStatus['days'] }} روز ({{ $sslStatus['expires_at'] }})</p>
                                            @if(!empty($sslStatus['issuer']))
                                                <p class="text-[11px] text-gray-400 mt-0.5">صادرکننده: {{ $sslStatus['issuer'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="pt-2 flex flex-col gap-2">
                                        <form action="{{ route('services.ssl', $service->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="target_domain" :value="selectedTargetDomain">
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                تمدید / صدور مجدد SSL
                                            </button>
                                        </form>

                                        <form action="{{ route('services.ssl.revoke', $service->id) }}" method="POST" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید گواهینامه SSL این دامنه را لغو و حذف کنید؟')">
                                            @csrf
                                            <input type="hidden" name="target_domain" :value="selectedTargetDomain">
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-1.5 border border-red-200 dark:border-red-900/60 text-xs font-medium rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 transition">
                                                لغو و حذف گواهینامه SSL
                                            </button>
                                        </form>
                                    </div>
                                @elseif($sslStatus['status'] === 'expired')
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 rtl:mr-3 rtl:ml-0">
                                            <p class="text-sm font-medium text-red-600 dark:text-red-400">منقضی شده</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">تاریخ انقضا: {{ $sslStatus['expires_at'] ?? 'گذشته' }}</p>
                                        </div>
                                    </div>
                                    <form action="{{ route('services.ssl', $service->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="target_domain" :value="selectedTargetDomain">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                            تمدید فوری SSL با Certbot
                                        </button>
                                    </form>
                                @elseif($sslStatus['status'] === 'missing')
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 rtl:mr-3 rtl:ml-0">
                                            <p class="text-sm font-medium text-yellow-700 dark:text-yellow-400">گواهینامه یافت نشد</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">روی پورت 443 فعال نیست</p>
                                        </div>
                                    </div>
                                    <form action="{{ route('services.ssl', $service->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="target_domain" :value="selectedTargetDomain">
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                            نصب SSL با Certbot
                                        </button>
                                    </form>
                                @endif

                                <!-- Auto-renewal status card -->
                                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[11px] font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                                            اتوماسیون تمدید خودکار:
                                        </span>
                                        <span class="text-[10px] text-green-600 dark:text-green-400 font-semibold">فعال (روزانه ساعت ۰۳:۳۰)</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-relaxed mb-2">گواهینامه‌های نزدیک به تاریخ انقضا (کمتر از ۳۰ روز) توسط کرون جاب پنل به طور خودکار تمدید می‌شوند.</p>
                                    <form action="{{ route('services.ssl.auto-renew', $service->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-center text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline">
                                            اجرای دستی بررسی و تمدید همه گواهینامه‌ها ↻
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Deploy Tab -->
            <div x-show="activeTab === 'deploy'" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Git Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6">
                        @if(!$hasGit)
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">نصب پروژه با گیت (Clone)</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">پوشه سرویس در حال حاضر خالی است. برای نصب اولیه، لینک مخزن را وارد کنید.</p>
                            <form action="{{ route('services.git', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="clone">
                                <div class="mb-4">
                                    <label for="repo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">آدرس مخزن گیت</label>
                                    <input type="text" class="mt-1 block w-full text-left rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600" id="repo_url" name="repo_url" placeholder="https://github.com/username/repo.git" dir="ltr" required>
                                </div>
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" name="run_migrations" id="run_migrations" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                    <label for="run_migrations" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">اجرای خودکار Migration پس از نصب</label>
                                </div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    <span>نصب (Clone)</span>
                                </button>
                            </form>
                        @else
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">به‌روزرسانی با گیت (Pull)</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">پروژه روی سرور نصب شده است. برای دریافت جدیدترین تغییرات کلیک کنید.</p>
                            <form action="{{ route('services.git', $service->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="pull">
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" name="run_migrations" id="run_migrations_pull" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                    <label for="run_migrations_pull" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">اجرای خودکار Migration پس از آپدیت</label>
                                </div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                    <span>به‌روزرسانی (Pull)</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <!-- Initial Upload Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">نصب پروژه با فایل ZIP</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">این گزینه برای نصب اولیه پروژه است و تمام محتوای فعلی پوشه سرویس را بازنویسی می‌کند.</p>
                        <form action="{{ route('services.upload', $service->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">فایل نصبی ZIP</label>
                                <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="file" name="file" accept=".zip" required>
                            </div>
                            <div class="flex items-center mb-4">
                                <input type="checkbox" name="run_migrations" id="run_migrations_upload" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="run_migrations_upload" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">اجرای Migration</label>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                <span>آپلود و نصب</span>
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Manual Update Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">به‌روزرسانی دستی (ZIP)</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">این گزینه برای بروزرسانی پروژه موجود است و از فایل‌های حساس مانند <code>.env</code> محافظت می‌کند.</p>
                        <form action="{{ route('services.manual-update', $service->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label for="update_zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">فایل به‌روزرسانی ZIP</label>
                                <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="update_zip" name="update_zip" accept=".zip" required>
                            </div>
                            <div class="flex items-center mb-4">
                                <input type="checkbox" name="run_migrations" id="run_migrations_manual_update" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="run_migrations_manual_update" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">اجرای Migration</label>
                            </div>
                            <div class="flex items-center mb-4">
                                <input type="checkbox" name="overwrite_composer" id="overwrite_composer" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="overwrite_composer" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">بازنویسی composer.json</label>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3V1.5M15 12H9" /></svg>
                                <span>آپلود و به‌روزرسانی</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Files Tab -->
            <div x-show="activeTab === 'files'" x-cloak class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200">مرورگر فایل</h4>
                            <div class="flex gap-2">
                                <button @click="showCreateFileModal = true" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    <span>جدید</span>
                                </button>
                                <button @click="showUploadFileModal = true" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                    <span>آپلود</span>
                                </button>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-2" style="max-height: 500px; overflow-y: auto;">
                            <ul class="divide-y divide-gray-200 dark:divide-gray-600">
                                @forelse($structure['root_files'] ?? [] as $file)
                                    <li>
                                        <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md file-item" data-file="{{ $file }}">
                                            <svg class="inline w-4 h-4 mr-1 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h-1.5a2.25 2.25 0 01-2.25-2.25V10.5m19.5 0a2.25 2.25 0 00-2.25-2.25h-15a2.25 2.25 0 00-2.25 2.25v2.25m19.5 0v2.25A2.25 2.25 0 0118.75 18h-7.5m-10.5-6h11.25m-11.25 0L6 7.5m11.25 4.5L18 7.5" /></svg>
                                            {{ $file }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="p-2 text-xs text-gray-500 dark:text-gray-400">فایلی یافت نشد.</li>
                                @endforelse
                                <li class="py-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mt-2">منابع</li>
                                @forelse($structure['resources'] ?? [] as $file)
                                    <li>
                                        <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md file-item" data-file="resources/{{ $file }}">
                                            <svg class="inline w-4 h-4 mr-1 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                                            {{ $file }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="p-2 text-xs text-gray-500 dark:text-gray-400">فایلی یافت نشد.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div id="editor-container" class="bg-gray-50 dark:bg-gray-700 rounded-md p-4" style="display: none;">
                            <div class="flex items-center justify-between mb-4">
                                <h4 id="current-filename" class="text-lg font-semibold text-gray-800 dark:text-gray-200">فایلی انتخاب نشده</h4>
                                <button id="save-file-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3V1.5M15 12H9" /></svg>
                                    <span>ذخیره تغییرات</span>
                                </button>
                            </div>
                            <textarea id="file-editor" class="block w-full p-3 text-sm text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-mono" rows="20"></textarea>
                        </div>
                        <div id="editor-placeholder" class="text-center text-gray-500 dark:text-gray-400 py-10">
                            <svg class="mx-auto w-16 h-16 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3V1.5M15 12H9" /></svg>
                            <p>فایلی را برای ویرایش انتخاب کنید</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commands Tab -->
            <div x-show="activeTab === 'commands'" x-cloak class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="p-4 mb-6 text-sm text-yellow-800 bg-yellow-100 rounded-lg dark:bg-yellow-900/50 dark:text-yellow-300" role="alert">
                    <h4 class="font-bold">دستورات دستی</h4>
                    <p>در صورت نیاز از این دستورات استفاده کنید. بیشتر وظایف راه‌اندازی لاراول پس از کلون/آپلود به صورت خودکار انجام می‌شوند.</p>
                </div>
                <div class="space-y-6">
                    <!-- System Commands -->
                    <div class="p-4 border rounded-lg dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">دستورات سیستمی</h4>
                        <div class="flex flex-wrap gap-3">
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="auto_setup">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L18.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18.75 10.5l.491-1.785a1.125 1.125 0 011.942-1.004l1.785.491-.491-1.785a1.125 1.125 0 011.004-1.942l1.785-.491-1.785-.491a1.125 1.125 0 01-1.942-1.004z" /></svg>
                                    <span>اجرای راه‌اندازی خودکار</span>
                                </button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="fix_permissions">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>رفع مجوزها</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <!-- Artisan Commands -->
                    <div class="p-4 border rounded-lg dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">دستورات Artisan لاراول</h4>
                        <div class="flex flex-wrap gap-3">
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="key_generate">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-green-600 text-sm font-medium rounded-md text-green-600 hover:bg-green-50 dark:border-green-400 dark:text-green-400 dark:hover:bg-green-900/20"><span>تولید کلید</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="view:clear">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><span>پاکسازی View</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="config:clear">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><span>پاکسازی Config</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="cache:clear">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><span>پاکسازی Cache</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="optimize">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-indigo-600 text-sm font-medium rounded-md text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20"><span>بهینه‌سازی</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="migrate">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-indigo-600 text-sm font-medium rounded-md text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20" onclick="return confirm('این عملیات Migration های در انتظار را اجرا خواهد کرد. ادامه می‌دهید؟')"><span>به‌روزرسانی پایگاه داده</span></button>
                            </form>
                        </div>
                    </div>
                    <!-- Composer & NPM Commands -->
                    <div class="p-4 border rounded-lg dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Composer & NPM</h4>
                        <div class="flex flex-wrap gap-3">
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="composer_install">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-800 text-sm font-medium rounded-md text-gray-800 hover:bg-gray-50 dark:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700"><span>Composer Install</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="composer_update">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-800 text-sm font-medium rounded-md text-gray-800 hover:bg-gray-50 dark:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700"><span>Composer Update</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="npm_install">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-yellow-600 text-sm font-medium rounded-md text-yellow-600 hover:bg-yellow-50 dark:border-yellow-400 dark:text-yellow-400 dark:hover:bg-yellow-900/20"><span>NPM Install</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="npm_build">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-yellow-600 text-sm font-medium rounded-md text-yellow-600 hover:bg-yellow-50 dark:border-yellow-400 dark:text-yellow-400 dark:hover:bg-yellow-900/20"><span>NPM Build</span></button>
                            </form>
                            <form action="{{ route('services.command', $service->id) }}" method="POST">
                                @csrf <input type="hidden" name="command" value="npm_clean">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-600 text-sm font-medium rounded-md text-red-600 hover:bg-red-50 dark:border-red-400 dark:text-red-400 dark:hover:bg-red-900/20" onclick="return confirm('آیا مطمئن هستید؟ این عملیات پوشه node_modules را حذف خواهد کرد.')"><span>NPM Clean</span></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if($service->type === 'subdomain')
            <!-- Nginx Logs Tab -->
            <div x-show="activeTab === 'logs'" x-cloak class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6" x-data="{ logs: { access: 'در حال بارگذاری...', error: 'در حال بارگذاری...', ssl_access: 'در حال بارگذاری...', ssl_error: 'در حال بارگذاری...' }, currentLog: 'access' }">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200">لاگ‌های Nginx</h4>
                    <div class="flex gap-2">
                        <select x-model="currentLog" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="access">Access Log (HTTP)</option>
                            <option value="error">Error Log (HTTP)</option>
                            <option value="ssl_access">Access Log (HTTPS)</option>
                            <option value="ssl_error">Error Log (HTTPS)</option>
                        </select>
                        <button @click="fetchLogs()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            تازه‌سازی
                        </button>
                    </div>
                </div>
                <div class="bg-gray-900 rounded-lg p-4 h-[500px] overflow-y-auto w-full">
                    <pre class="text-green-400 font-mono text-xs whitespace-pre-wrap ltr text-left" dir="ltr" x-text="logs[currentLog]"></pre>
                </div>
            </div>
            @endif

            <!-- Danger Zone Tab -->
            <div x-show="activeTab === 'danger'" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                    <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                        <h4 class="text-lg font-semibold mb-2">بازنشانی فایل‌های سرویس</h4>
                        <p class="mb-2">این عملیات <strong>تمامی فایل‌ها و دایرکتوری‌ها</strong> را در پوشه سرویس (<code>{{ $service->path }}</code>) حذف خواهد کرد، از جمله <code>.env</code>، <code>.git</code> و فایل‌های آپلود شده.</p>
                        <p>رکورد سرویس در پایگاه داده و پیکربندی دامنه/ساب‌فولدر دست نخورده باقی خواهد ماند.</p>
                        <hr class="my-3 border-red-200 dark:border-red-700">
                        <form action="{{ route('services.reset', $service->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none"
                                    onclick="return confirm('آیا کاملاً مطمئن هستید که می‌خواهید تمامی فایل‌های این سرویس را حذف کنید؟ این عملیات قابل بازگشت نیست.')">
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                <span>بازنشانی فایل‌های سرویس</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Create File Modal -->
        <div x-show="showCreateFileModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showCreateFileModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateFileModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="showCreateFileModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('services.files.create', $service->id) }}" method="POST">
                        @csrf
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200">ایجاد فایل جدید</h3>
                            <div class="mt-4">
                                <label for="filename" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام فایل</label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600" id="filename" name="filename" required>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">ایجاد</button>
                            <button type="button" @click="showCreateFileModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">لغو</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Upload File Modal -->
        <div x-show="showUploadFileModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showUploadFileModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showUploadFileModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="showUploadFileModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('services.files.upload', $service->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200">آپلود فایل</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="upload_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">انتخاب فایل</label>
                                    <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="upload_file" name="upload_file" required>
                                </div>
                                <div>
                                    <label for="upload_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300">مسیر آپلود (اختیاری)</label>
                                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600" id="upload_path" name="upload_path" placeholder="مثال: public/images">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">آپلود</button>
                            <button type="button" @click="showUploadFileModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">لغو</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ... (existing script for files)
            });

            @if($service->type === 'subdomain')
            function fetchLogs() {
                const logsObj = document.querySelector('[x-data]').__x.$data;
                logsObj.logs = { access: 'در حال بارگذاری...', error: 'در حال بارگذاری...', ssl_access: 'در حال بارگذاری...', ssl_error: 'در حال بارگذاری...' };
                fetch('{{ route('services.logs', $service->id) }}')
                    .then(response => response.json())
                    .then(data => {
                        logsObj.logs = data;
                    })
                    .catch(error => {
                        console.error('Error fetching logs:', error);
                        logsObj.logs = { access: 'خطا در بارگذاری', error: 'خطا در بارگذاری', ssl_access: 'خطا در بارگذاری', ssl_error: 'خطا در بارگذاری' };
                    });
            }
            @endif
        </script>
    </div>
@endsection