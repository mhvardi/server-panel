@extends('layouts.app')

@section('title', 'ویرایش وظیفه')

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-1 sm:mb-0">ویرایش وظیفه (Cron Job)</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">تغییر زمان‌بندی، دستور یا وضعیت اجرا.</p>
            </div>
            <a href="{{ route('cronjobs.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700 mt-4 sm:mt-0">
                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>بازگشت به لیست</span>
            </a>
        </div>

        <!-- Errors Alert -->
        @if ($errors->any())
            <div class="mb-6 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                <div class="flex items-center mb-2 font-bold">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    لطفاً خطاهای زیر را برطرف کنید:
                </div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main Edit Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    جزئیات وظیفه
                </h3>
                <span class="px-3 py-1 text-xs font-mono bg-gray-100 text-gray-800 rounded-md dark:bg-gray-700 dark:text-gray-200" dir="ltr">ID: {{ $job['id'] }}</span>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('cronjobs.update', $job['id']) }}">
                    @csrf
                    @method('PUT')

                    @include('cronjobs._form', ['job' => $job, 'config' => $config])

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border-r-4 border-red-500">
            <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        منطقه خطر
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">با حذف این وظیفه، هیچ راهی برای بازگردانی آن وجود نخواهد داشت. لطفاً مطمئن شوید.</p>
                </div>
                <form action="{{ route('cronjobs.destroy', $job['id']) }}" method="POST" onsubmit="return confirm('آیا از حذف این وظیفه اطمینان کامل دارید؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg shadow-sm hover:bg-red-50 hover:text-red-700 dark:bg-transparent dark:border-red-500/50 dark:hover:bg-red-500/10 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        حذف وظیفه
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection