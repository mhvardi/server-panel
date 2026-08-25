@extends('layouts.app')

@section('title', 'پایگاه‌داده: ' . $details['name'])

@section('content')
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ grantModalOpen: {{ $errors->any() ? 'true' : 'false' }} }">

        {{-- Header & Breadcrumb --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 space-x-reverse text-xs text-slate-400">
                        <li class="inline-flex items-center">
                            <a href="{{ route('databases.index') }}" class="hover:text-indigo-600 dark:hover:text-white transition">
                                پایگاه‌های داده
                            </a>
                        </li>
                        <li aria-current="page" class="flex items-center">
                            <svg class="w-3 h-3 mx-1 transform rotate-180 opacity-60" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="font-bold text-slate-600 dark:text-slate-200">{{ $details['name'] }}</span>
                        </li>
                    </ol>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-mono">{{ $details['name'] }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">مشاهده مشخصات، جداول و مدیریت دسترسی‌های پایگاه‌داده</p>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ $pmaUrl ?? \App\Http\Controllers\DatabaseController::getPhpMyAdminUrl($details['name']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 rounded-2xl text-xs font-bold transition border border-amber-200/60 dark:border-amber-800/40">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>ورود به phpMyAdmin</span>
                </a>
                <button @click="grantModalOpen = true" type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>اعطای دسترسی جدید</span>
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Right Column: Database Info & Users -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Database Info Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/60 p-5 space-y-4">
                    <h3 class="text-sm font-black text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-700">اطلاعات پایگاه‌داده</h3>
                    
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs space-y-2">
                        <li class="pt-2 flex justify-between items-center">
                            <span class="text-slate-400">نام دیتابیس:</span>
                            <strong class="text-slate-800 dark:text-white font-mono">{{ $details['name'] }}</strong>
                        </li>
                        <li class="pt-2 flex justify-between items-center">
                            <span class="text-slate-400">حجم کل:</span>
                            <span class="px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                {{ number_format($details['size'], 2) }} MB
                            </span>
                        </li>
                        <li class="pt-2 flex justify-between items-center">
                            <span class="text-slate-400">تعداد جداول:</span>
                            <span class="px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                {{ $details['table_count'] }} جدول
                            </span>
                        </li>
                        <li class="pt-2 flex justify-between items-center">
                            <span class="text-slate-400">انکودینگ پیش‌فرض:</span>
                            <span class="font-mono text-slate-600 dark:text-slate-300">{{ $details['charset'] }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Users with Access Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/60 p-5 space-y-4">
                    <h3 class="text-sm font-black text-slate-800 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-700">کاربران دارای دسترسی</h3>
                    
                    <div>
                        @if(count($details['users']) > 0)
                            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs">
                                @foreach($details['users'] as $user)
                                    @php
                                        $userParts = explode('@', trim($user, "'"));
                                        $username = $userParts[0] ?? '';
                                        $host = $userParts[1] ?? 'localhost';
                                    @endphp
                                    <li class="py-3 flex justify-between items-center">
                                        <span class="font-mono text-slate-800 dark:text-slate-200 dir-ltr text-right">
                                            {{ $username }}@{{ $host }}
                                        </span>
                                        <form action="{{ route('databases.privileges.revoke') }}" method="POST" class="inline" onsubmit="return confirm('آیا از لغو دسترسی این کاربر اطمینان دارید؟');">
                                            @csrf
                                            <input type="hidden" name="username" value="{{ $username }}">
                                            <input type="hidden" name="host" value="{{ $host }}">
                                            <input type="hidden" name="database" value="{{ $details['name'] }}">
                                            <button type="submit" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 p-1.5 rounded-xl transition" title="لغو دسترسی">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="py-6 text-center text-xs text-slate-400">
                                هیچ کاربری به این پایگاه‌داده دسترسی ندارد.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Left Column: Tables List -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/60 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">
                            لیست جداول ({{ $details['table_count'] }})
                        </h3>
                    </div>

                    @if(count($details['tables']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700/60 text-xs">
                                <thead class="bg-slate-50/75 dark:bg-slate-900/40 text-slate-400 text-[11px] font-black uppercase">
                                    <tr>
                                        <th class="px-5 py-3.5 text-right">نام جدول</th>
                                        <th class="px-5 py-3.5 text-right">ردیف‌ها</th>
                                        <th class="px-5 py-3.5 text-right">حجم</th>
                                        <th class="px-5 py-3.5 text-right">موتور (Engine)</th>
                                        <th class="px-5 py-3.5 text-right">تطبیق (Collation)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                                    @foreach($details['tables'] as $table)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                                            <td class="px-5 py-3.5 font-mono font-bold text-slate-800 dark:text-slate-200">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    <span>{{ $table['name'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span class="px-2 py-0.5 font-mono text-[11px] font-bold rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                                    {{ number_format($table['rows']) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 font-mono text-slate-600 dark:text-slate-300">
                                                {{ number_format($table['size_mb'], 2) }} MB
                                            </td>
                                            <td class="px-5 py-3.5 font-mono text-slate-500 dark:text-slate-400">
                                                {{ $table['engine'] }}
                                            </td>
                                            <td class="px-5 py-3.5 font-mono text-slate-500 dark:text-slate-400">
                                                {{ $table['collation'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            <p class="mt-3 text-xs text-slate-400">این پایگاه‌داده هنوز هیچ جدولی ندارد.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Grant Privileges Modal (Alpine.js) -->
        <div x-show="grantModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 p-6">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="grantModalOpen = false"></div>
                
                <div class="relative bg-white dark:bg-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 dark:border-slate-700 text-right space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">اعطای دسترسی به کاربر</h3>
                        <button type="button" @click="grantModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form action="{{ route('databases.privileges.grant') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="from_show" value="1">
                        <input type="hidden" name="database" value="{{ $details['name'] }}">

                        <div>
                            <label for="gp_username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">نام کاربری</label>
                            <input type="text" id="gp_username" name="username" required pattern="[a-zA-Z0-9_]+" placeholder="db_user" list="userList"
                                   class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">

                            <datalist id="userList">
                                @php
                                    try {
                                        $usersList = app(\App\Services\DatabaseService::class)->listUsers();
                                    } catch (\Exception $e) {
                                        $usersList = [];
                                    }
                                @endphp
                                @foreach($usersList as $u)
                                    <option value="{{ $u['username'] }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label for="gp_host" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">هاست (Host)</label>
                            <select id="gp_host" name="host" class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                                <option value="localhost" selected>localhost</option>
                                <option value="%">% (Any Host)</option>
                                <option value="127.0.0.1">127.0.0.1</option>
                            </select>
                        </div>

                        <div>
                            <label for="gp_privileges" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">سطح دسترسی</label>
                            <select id="gp_privileges" name="privileges" class="block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-xs p-3 font-mono dir-ltr text-right">
                                <option value="ALL PRIVILEGES" selected>ALL PRIVILEGES</option>
                                <option value="SELECT, INSERT, UPDATE, DELETE">SELECT, INSERT, UPDATE, DELETE</option>
                                <option value="SELECT, INSERT, UPDATE">SELECT, INSERT, UPDATE</option>
                                <option value="SELECT">SELECT (فقط خواندن)</option>
                            </select>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-3">
                            <button type="button" @click="grantModalOpen = false" class="px-4 py-2 rounded-2xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition">انصراف</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition">ثبت دسترسی</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection