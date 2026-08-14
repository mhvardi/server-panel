@extends('layouts.app')

@section('title', 'تنظیمات')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">تنظیمات</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
            <pre class="mb-0 font-sans whitespace-pre-wrap">{{ session('success') }}</pre>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
            <pre class="mb-0 font-sans whitespace-pre-wrap">{{ session('error') }}</pre>
        </div>
    @endif

    {{-- Update via Git --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">به‌روزرسانی پنل از طریق گیت</h3>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                آدرس مخزن گیت را وارد کرده و بر روی دکمه به‌روزرسانی کلیک کنید.
            </p>
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="repo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">آدرس مخزن گیت</label>
                    <input type="text" id="repo_url" name="repo_url"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:ring-offset-gray-800"
                           placeholder="https://github.com/username/repo.git"
                           value="{{ old('repo_url', 'https://github.com/mjavadh/server-panel.git') }}">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none"
                        onclick="return confirm('آیا مطمئن هستید که می‌خواهید پنل را از طریق گیت به‌روزرسانی کنید؟');">
                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                    <span>به‌روزرسانی از گیت</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Update via ZIP Upload --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">به‌روزرسانی دستی پنل (آپلود ZIP)</h3>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                فایل ZIP حاوی به‌روزرسانی را آپلود کنید. سیستم به صورت هوشمند فایل‌ها را جایگزین می‌کند و از فایل‌های حساس مانند <code>.env</code> محافظت می‌کند.
            </p>
            <form action="{{ route('settings.manual-update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="update_zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">فایل به‌روزرسانی (ZIP)</label>
                    <input type="file" id="update_zip" name="update_zip" accept=".zip" required
                           class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none"
                        onclick="return confirm('آیا مطمئن هستید که می‌خواهید پنل را از طریق فایل ZIP به‌روزرسانی کنید؟');">
                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    <span>آپلود و به‌روزرسانی</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
