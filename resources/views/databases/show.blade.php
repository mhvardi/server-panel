@extends('layouts.app')

@section('title', 'پایگاه‌داده: ' . $details['name'])

@section('content')
    <!-- مقداردهی Alpine.js برای کنترل باز و بسته شدن مدال. اگر خطای فرم وجود داشت، مدال باز می‌ماند -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="{ grantModalOpen: {{ $errors->any() ? 'true' : 'false' }} }">

        {{-- Header & Breadcrumb --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 space-y-4 md:space-y-0">
            <div>
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
                                <span class="margin-right-2 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $details['name'] }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 ml-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    {{ $details['name'] }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="grantModalOpen = true" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    اعطای دسترسی جدید
                </button>
                <form action="{{ route('databases.destroy', $details['name']) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از حذف این پایگاه داده اطمینان دارید؟ این عمل غیرقابل بازگشت است.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        حذف پایگاه‌داده
                    </button>
                </form>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300 flex items-center" role="alert">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300 flex items-center" role="alert">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Right Column: Database Info & Users -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Database Info Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-md font-semibold text-indigo-600 dark:text-indigo-400">اطلاعات پایگاه‌داده</h3>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li class="p-4 flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400 flex items-center">
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            نام
                        </span>
                            <strong class="text-gray-900 dark:text-white">{{ $details['name'] }}</strong>
                        </li>
                        <li class="p-4 flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400 flex items-center">
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                            حجم
                        </span>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ number_format($details['size'], 2) }} MB</span>
                        </li>
                        <li class="p-4 flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400 flex items-center">
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            تعداد جداول
                        </span>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ $details['table_count'] }}</span>
                        </li>
                        <li class="p-4 flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400 flex items-center">
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                            انکودینگ
                        </span>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $details['charset'] }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Users with Access Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-md font-semibold text-green-600 dark:text-green-400">کاربران دارای دسترسی</h3>
                    </div>
                    <div class="p-0">
                        @if(count($details['users']) > 0)
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($details['users'] as $user)
                                    @php
                                        $userParts = explode('@', trim($user, "'"));
                                        $username = $userParts[0] ?? '';
                                        $host = $userParts[1] ?? 'localhost';
                                    @endphp
                                    <li class="p-4 flex justify-between items-center text-sm">
                                    <span class="font-medium text-gray-900 dark:text-gray-200 flex items-center dir-ltr">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $username }}@{{ $host }}
                                    </span>
                                        <form action="{{ route('databases.privileges.revoke') }}" method="POST" class="inline" onsubmit="return confirm('آیا از لغو دسترسی این کاربر اطمینان دارید؟');">
                                            @csrf
                                            <input type="hidden" name="username" value="{{ $username }}">
                                            <input type="hidden" name="host" value="{{ $host }}">
                                            <input type="hidden" name="database" value="{{ $details['name'] }}">
                                            <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-1.5 rounded" title="لغو دسترسی">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                هیچ کاربری به این پایگاه‌داده دسترسی ندارد.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Left Column: Tables List -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200">
                            لیست جداول ({{ $details['table_count'] }})
                        </h3>
                    </div>

                    @if(count($details['tables']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">نام جدول</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ردیف‌ها</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">حجم</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">موتور (Engine)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">تطبیق (Collation)</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($details['tables'] as $table)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200 flex items-center">
                                            <svg class="w-4 h-4 ml-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            <span class="dir-ltr inline-block">{{ $table['name'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ number_format($table['rows']) }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 dir-ltr text-right">
                                            {{ number_format($table['size_mb'], 2) }} MB
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $table['engine'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $table['collation'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">این پایگاه‌داده هنوز هیچ جدولی ندارد.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Grant Privileges Modal (Alpine.js) -->
        <div x-show="grantModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Modal Backdrop -->
                <div x-show="grantModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                     @click="grantModalOpen = false"
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div x-show="grantModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">

                    <form action="{{ route('databases.privileges.grant') }}" method="POST">
                        @csrf
                        <!-- بسیار مهم: این فیلد مخفی برای ریدایرکت مجدد به همین صفحه در کنترلر استفاده می‌شود -->
                        <input type="hidden" name="from_show" value="1">
                        <input type="hidden" name="database" value="{{ $details['name'] }}">

                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-right w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700" id="modal-title">
                                        اعطای دسترسی به کاربر
                                    </h3>
                                    <div class="mt-4 space-y-4">

                                        {{-- نمایش ارورهای فرم در مدال --}}
                                        @if($errors->any())
                                            <div class="p-3 bg-red-100 text-red-700 rounded text-sm mb-4">
                                                لطفاً خطاهای فرم را برطرف کنید.
                                            </div>
                                        @endif

                                        <div>
                                            <label for="gp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">نام کاربری</label>
                                            <input type="text" id="gp_username" name="username" required pattern="[a-zA-Z0-9_]+" placeholder="db_user" list="userList"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white sm:text-sm p-2 border @error('username') border-red-500 @enderror">

                                            <datalist id="userList">
                                                @php
                                                    try {
                                                        $users = app(\App\Services\DatabaseService::class)->listUsers();
                                                    } catch (\Exception $e) {
                                                        $users = [];
                                                    }
                                                @endphp
                                                @foreach($users as $user)
                                                    <option value="{{ $user['username'] }}">
                                                @endforeach
                                            </datalist>
                                            <p class="mt-1 text-xs text-gray-500">برای دیدن لیست کاربران دکمه پایین یا حروف را تایپ کنید</p>
                                            @error('username')
                                            <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="gp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">هاست (Host)</label>
                                            <select id="gp_host" name="host" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white sm:text-sm p-2 border dir-ltr">
                                                <option value="localhost" selected>localhost</option>
                                                <option value="%">% (Any Host)</option>
                                                <option value="127.0.0.1">127.0.0.1</option>
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">دقت کنید هاست باید دقیقاً با هاست تنظیم شده هنگام ساخت کاربر برابر باشد.</p>
                                        </div>

                                        <div>
                                            <label for="gp_privileges" class="block text-sm font-medium text-gray-700 dark:text-gray-300">سطح دسترسی</label>
                                            <select id="gp_privileges" name="privileges" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:text-white sm:text-sm p-2 border dir-ltr">
                                                <option value="ALL PRIVILEGES" selected>ALL PRIVILEGES</option>
                                                <option value="SELECT, INSERT, UPDATE, DELETE">SELECT, INSERT, UPDATE, DELETE</option>
                                                <option value="SELECT, INSERT, UPDATE">SELECT, INSERT, UPDATE</option>
                                                <option value="SELECT">SELECT (فقط خواندن)</option>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                ثبت دسترسی
                            </button>
                            <button type="button" @click="grantModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                انصراف
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection