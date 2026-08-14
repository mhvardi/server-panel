@extends('layouts.app')

@section('title', 'افزودن سرویس جدید')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <!-- Card Header -->
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">افزودن سرویس جدید</h1>
            </div>

            <!-- Card Body -->
            <div class="p-4 sm:p-6">
                @if ($errors->any())
                    <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                        <ul class="mb-0 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('services.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام سرویس</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('name') border-red-500 @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نوع</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('type') border-red-500 @enderror" id="type" name="type" required>
                            <option value="subdomain">ساب‌دامین (Subdomain)</option>
                            <option value="subfolder">ساب‌فولدر (Subfolder)</option>
                        </select>
                        @error('type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام ساب‌دامین / ساب‌فولدر</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('domain') border-red-500 @enderror" id="domain" name="domain" value="{{ old('domain') }}" required placeholder="مثال: crm یا blog">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400" id="domain-addon">.{{ env('APP_MAIN_DOMAIN', request()->getHost()) }}</span>
                        </div>
                        @error('domain')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">فقط نام را وارد کنید. دامنه کامل به صورت خودکار ایجاد خواهد شد.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            ایجاد سرویس
                        </button>
                        <a href="{{ route('services.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            انصراف
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const domainAddon = document.getElementById('domain-addon');
            const mainDomain = "{{ env('APP_MAIN_DOMAIN', request()->getHost()) }}";

            function updateAddon() {
                if (typeSelect.value === 'subdomain') {
                    domainAddon.textContent = '.' + mainDomain;
                } else {
                    domainAddon.textContent = 'http://' + mainDomain + '/...';
                }
            }

            typeSelect.addEventListener('change', updateAddon);
            updateAddon(); // Initial call
        });
    </script>
@endsection
