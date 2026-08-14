@extends('layouts.app')

@section('title', 'مدیریت پایگاه‌داده')

@section('content')
    <div class="max-w-7xl mx-auto" x-data="{ activeTab: 'databases' }">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2 sm:mb-0">مدیریت پایگاه‌داده</h1>
            <a href="{{ route('databases.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                ایجاد پایگاه داده مجزا
            </a>
        </div>

        {{-- Global Validation Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                <h4 class="font-bold mb-1">خطا در اعتبارسنجی داده‌ها:</h4>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach ($errors->all() as $errorItem)
                        <li>{{ $errorItem }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error') || isset($error))
            <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
                <h4 class="font-bold">خطا!</h4>
                <p>{{ session('error') ?: $error }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Databases List --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">لیست پایگاه‌های داده</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">نام</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">حجم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">جداول</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">انکودینگ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">عملیات</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($databases as $db)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">{{ $db['name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ number_format($db['size'], 2) }} MB</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100">{{ $db['tables'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">{{ $db['charset'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <a href="{{ route('databases.show', $db['name']) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 ml-3">مشاهده</a>
                                        <form action="{{ route('databases.destroy', $db['name']) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از حذف این پایگاه داده اطمینان دارید؟ این عمل غیرقابل بازگشت است.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        هیچ پایگاه داده‌ای یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Users List --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">لیست کاربران پایگاه‌داده</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">نام کاربری</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">هاست</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">عملیات</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">{{ $user['username'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">{{ $user['host'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <form action="{{ route('databases.user.delete') }}" method="POST" onsubmit="return confirm('آیا از حذف این کاربر اطمینان دارید؟');" class="inline">
                                            @csrf
                                            <input type="hidden" name="username" value="{{ $user['username'] }}">
                                            <input type="hidden" name="host" value="{{ $user['host'] }}">
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        هیچ کاربری یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar with Actions --}}
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Create Wizard -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-indigo-100 dark:border-indigo-900/30">
                    <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 bg-indigo-50/50 dark:bg-indigo-950/20">
                        <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-400">ایجاد سریع پایگاه‌داده و کاربر</h3>
                        <p class="text-xs text-gray-500 mt-1">همزمان دیتابیس و کاربر ساخته شده و به یکدیگر متصل می‌شوند.</p>
                    </div>
                    <form action="{{ route('databases.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="create_user" value="1">

                        <div class="p-4 sm:p-6 space-y-4">
                            <div>
                                <label for="db_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام پایگاه‌داده</label>
                                <input type="text" name="name" id="db_name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm p-2 border" required>
                                @error('name')
                                <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="db_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام کاربری متصل</label>
                                <input type="text" name="username" id="db_user" value="{{ old('username') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white text-sm p-2 border" required>
                                @error('username')
                                <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Auto-Generate Password Component for Quick Create -->
                            <div x-data="{ show: false, password: '{{ old('password') }}' }">
                                <label for="db_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">رمز عبور کاربر</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <button type="button" @click="password = generateStrongPassword(); show = true" class="inline-flex items-center px-3 py-2 border border-gray-300 border-l-0 rounded-r-md bg-gray-50 text-gray-700 text-sm hover:bg-gray-100 dark:bg-gray-600 dark:border-gray-600 dark:text-gray-200 transition-colors">
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        تولید
                                    </button>
                                    <div class="relative flex-grow focus-within:z-10">
                                        <input :type="show ? 'text' : 'password'" name="password" id="db_password" x-model="password" class="block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2 border pl-10 dir-ltr dark:text-white" required>
                                        <!-- Eye Icon Toggle -->
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center cursor-pointer" @click="show = !show">
                                            <svg x-show="!show" class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="show" style="display: none;" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.5-2.5m1.5-1.5A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.39 2.06m-1.61 1.44c-.75.75-1.65 1.34-2.65 1.7M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">تولید خودکار برای رفع محدودیت‌های policy دیتابیس پیشنهاد می‌شود.</p>
                                @error('password')
                                <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 text-left border-t border-gray-200 dark:border-gray-700">
                            <button type="submit" class="inline-flex items-center px-5 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                ایجاد دیتابیس و اتصال کاربر
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Admin Password -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">تغییر رمز عبور مدیر (crm_admin)</h3>
                    </div>
                    <form action="{{ route('databases.user.password') }}" method="POST">
                        @csrf
                        <input type="hidden" name="username" value="crm_admin">
                        <input type="hidden" name="host" value="localhost">

                        <div class="p-4 sm:p-6 space-y-4" x-data="{ show: false, password: '', confirm: '' }">
                            <div class="flex justify-between items-end mb-1">
                                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">رمز عبور جدید</label>
                                <button type="button" @click="let p = generateStrongPassword(); password = p; confirm = p; show = true" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    تولید رمز قوی
                                </button>
                            </div>

                            <div class="relative focus-within:z-10 mt-1">
                                <input :type="show ? 'text' : 'password'" name="password" id="new_password" x-model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2 border pl-10 dir-ltr text-gray-900 dark:text-white" required>
                                <!-- Eye Icon -->
                                <div class="absolute inset-y-0 left-0 pl-3 pt-1 flex items-center cursor-pointer" @click="show = !show">
                                    <svg x-show="!show" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" style="display: none;" class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.5-2.5m1.5-1.5A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.39 2.06m-1.61 1.44c-.75.75-1.65 1.34-2.65 1.7M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" /></svg>
                                </div>
                            </div>

                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">تکرار رمز عبور</label>
                                <input :type="show ? 'text' : 'password'" name="password_confirmation" id="new_password_confirmation" x-model="confirm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2 border dir-ltr text-gray-900 dark:text-white" required>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 text-left border-t border-gray-200 dark:border-gray-700">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700">
                                تغییر رمز
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Generator Logic -->
    <script>
        function generateStrongPassword() {
            const u = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const l = "abcdefghijklmnopqrstuvwxyz";
            const n = "0123456789";
            const s = "!@#$%^&*()_+=-";
            const all = u + l + n + s;

            // تضمین وجود حداقل یک نمونه از هرکدام
            let p = [
                u[Math.floor(Math.random() * u.length)],
                l[Math.floor(Math.random() * l.length)],
                n[Math.floor(Math.random() * n.length)],
                s[Math.floor(Math.random() * s.length)]
            ];

            // تکمیل طول رمز به 16 کاراکتر
            for (let i = 0; i < 12; i++) {
                p.push(all[Math.floor(Math.random() * all.length)]);
            }

            // برهم زدن تصادفی کاراکترها (Shuffling)
            for (let i = p.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [p[i], p[j]] = [p[j], p[i]];
            }

            return p.join('');
        }
    </script>
@endsection