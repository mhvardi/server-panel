@extends('layouts.app')

@section('title', 'ایجاد پایگاه‌داده جدید')

@section('content')
    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb & Title --}}
        <div class="mb-6">
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3 space-x-reverse">
                    <li class="inline-flex items-center">
                        <a href="{{ route('databases.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                            پایگاه‌های داده
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 transform rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="margin-right-2 text-sm font-medium text-gray-500 dark:text-gray-400">ایجاد جدید</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ایجاد پایگاه‌داده جدید مجزا</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Form Section --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200">مشخصات ساخت دیتابیس</h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        <form action="{{ route('databases.store') }}" method="POST" class="space-y-5">
                            @csrf
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام دیتابیس <span class="text-red-500">*</span></label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm p-2 border.0 @error('name') border-red-500 ring-1 ring-red-500 @enderror"
                                       id="name" name="name" required pattern="[a-zA-Z0-9_]+" placeholder="my_database" value="{{ old('name') }}">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">فقط حروف انگلیسی، اعداد و خط زیرین مجاز است (حداکثر ۶۴ کاراکتر)</p>
                                @error('name')
                                <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="charset" class="block text-sm font-medium text-gray-700 dark:text-gray-300">مجموعه کاراکتر (Character Set)</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm p-2 border" id="charset" name="charset">
                                    <option value="utf8mb4" {{ old('charset', 'utf8mb4') === 'utf8mb4' ? 'selected' : '' }}>utf8mb4 (پیشنهادی - پشتیبانی کامل از اموجی و زبان فارسی)</option>
                                    <option value="utf8" {{ old('charset') === 'utf8' ? 'selected' : '' }}>utf8 (استاندارد UTF-8)</option>
                                    <option value="latin1" {{ old('charset') === 'latin1' ? 'selected' : '' }}>latin1 (اروپای غربی)</option>
                                </select>
                            </div>

                            <div>
                                <label for="collation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">تطبیق ساختار (Collation)</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm p-2 border" id="collation" name="collation">
                                    <option value="utf8mb4_unicode_ci" {{ old('collation', 'utf8mb4_unicode_ci') === 'utf8mb4_unicode_ci' ? 'selected' : '' }}>utf8mb4_unicode_ci (پیشنهادی - مرتب‌سازی دقیق زبانی)</option>
                                    <option value="utf8mb4_general_ci" {{ old('collation') === 'utf8mb4_general_ci' ? 'selected' : '' }}>utf8mb4_general_ci (سریع‌تر اما دقت مرتب‌سازی کمتر)</option>
                                    <option value="utf8_general_ci" {{ old('collation') === 'utf8_general_ci' ? 'selected' : '' }}>utf8_general_ci (برای ساختار utf8 استاندارد)</option>
                                </select>
                            </div>

                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <a href="{{ route('databases.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                                    انصراف
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    ایجاد پایگاه داده
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Help Sidebar --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-5 space-y-4">
                    <h4 class="text-md font-semibold text-indigo-600 dark:text-indigo-400 border-b border-gray-100 dark:border-gray-700 pb-2">راهنمای پیکربندی</h4>

                    <div>
                        <h5 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase mb-1">Character Sets</h5>
                        <ul class="list-disc list-inside text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li><strong>utf8mb4:</strong> پشتیبانی کامل از تمام کاراکترهای یونی‌کد و اموجی‌ها؛ برای سیستم‌های فارسی کاملاً الزامی است.</li>
                            <li><strong>utf8:</strong> نسخه قدیمی‌تر با محدودیت‌های جزئی زبانی.</li>
                        </ul>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-700">

                    <div>
                        <h5 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase mb-1">Collations</h5>
                        <ul class="list-disc list-inside text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li><strong>unicode_ci:</strong> اولویت‌بندی بسیار دقیق کلمات و حروف هم‌خانواده زبانی.</li>
                            <li><strong>general_ci:</strong> عملکرد سریع‌تر با چشم‌پوشی از برخی قوانین پیچیده مرتب‌سازی.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection