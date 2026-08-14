@extends('layouts.app')

@section('title', 'مدیریت سرویس‌ها')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <!-- Card Header -->
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">سرویس‌ها</h1>
            <a href="{{ route('services.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>افزودن سرویس جدید</span>
            </a>
        </div>

        <!-- Card Body -->
        <div class="p-4 sm:p-6">
            @if (session('success'))
                <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Search and Filter Section --}}
            <form method="GET" action="{{ route('services.index') }}" class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="sr-only">جستجو</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="جستجو بر اساس نام یا دامنه..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label for="type-filter" class="sr-only">فیلتر بر اساس نوع</label>
                    <select name="type" id="type-filter" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" onchange="this.form.submit()">
                        <option value="">همه انواع</option>
                        <option value="subdomain" {{ request('type') == 'subdomain' ? 'selected' : '' }}>ساب‌دامین</option>
                        <option value="subfolder" {{ request('type') == 'subfolder' ? 'selected' : '' }}>ساب‌فولدر</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 focus:outline-none">
                        اعمال فیلتر
                    </button>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($services as $service)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col">
                        <div class="p-5 flex-1">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-2 max-w-[70%]">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" title="{{ $service->name }}">{{ $service->name }}</h3>
                                    @if(isset($service->is_online))
                                        @if($service->is_online)
                                            <span class="flex w-2.5 h-2.5 bg-green-500 rounded-full flex-shrink-0" title="آنلاین (بروزرسانی: {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'نامشخص' }})"></span>
                                        @else
                                            <span class="flex w-2.5 h-2.5 bg-red-500 rounded-full flex-shrink-0" title="آفلاین (بروزرسانی: {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'نامشخص' }})"></span>
                                        @endif
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 whitespace-nowrap">
                                    {{ $service->type === 'subdomain' ? 'ساب‌دامین' : 'ساب‌فولدر' }}
                                </span>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">دامنه / آدرس</span>
                                    @if($service->type == 'subfolder')
                                        <a href="http://{{ env('APP_MAIN_DOMAIN', 'localhost') }}/{{ $service->domain }}" target="_blank" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 truncate w-full" dir="ltr">
                                            {{ env('APP_MAIN_DOMAIN', 'localhost') }}/{{ $service->domain }}
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    @else
                                        <a href="http://{{ $service->domain }}" target="_blank" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 truncate w-full" dir="ltr">
                                            {{ $service->domain }}
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    @endif
                                </div>
                                
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">مسیر سیستم</span>
                                    <p class="text-xs font-mono text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-2 rounded truncate border border-gray-100 dark:border-gray-600" dir="ltr" title="{{ $service->path }}">
                                        {{ $service->path }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-100 dark:bg-gray-800/80 p-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <a href="{{ route('services.show', $service->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                مدیریت
                            </a>
                            
                            <div class="flex items-center gap-3">
                                <a href="{{ route('services.edit', $service->id) }}" class="text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400 transition-colors" title="ویرایش">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                </a>
                                <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('آیا مطمئن هستید؟ این عملیات تمامی فایل‌های مربوط به این سرویس را حذف خواهد کرد!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors" title="حذف">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-gray-50 dark:bg-gray-700/30 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">سرویسی یافت نشد</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">هیچ سرویسی با این مشخصات وجود ندارد.</p>
                        <div class="mt-6">
                            <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                افزودن اولین سرویس
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination Section --}}
            <div class="mt-8">
                {{ $services->links() }}
            </div>
        </div>
    </div>
@endsection
