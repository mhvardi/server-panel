@extends('layouts.user')
@php($title = 'مدیریت کاربران')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="font-semibold text-gray-900 dark:text-gray-100">کاربران</h1>

            {{-- نمایش دکمه ایجاد برای کاربرانی که مجوز users.create دارند --}}
            @can('users.create')
                @if (Route::has('admin.users.create'))
                    <a href="{{ route('admin.users.create') }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                        + کاربر جدید
                    </a>
                @endif
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="p-3 text-right">#</th>
                    <th class="p-3 text-right">نام</th>
                    <th class="p-3 text-right">ایمیل</th>
                    <th class="p-3 text-right">موبایل</th>
                    <th class="p-3 text-right">نقش‌ها</th>
                    <th class="p-3 text-right">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr class="border-t border-gray-100 dark:border-gray-700/50">
                        <td class="p-3 text-gray-700 dark:text-gray-200">{{ $user->id }}</td>
                        <td class="p-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                        <td class="p-3 text-gray-700 dark:text-gray-200">{{ $user->email }}</td>
                        <td class="p-3 text-gray-700 dark:text-gray-200">{{ $user->mobile }}</td>
                        <td class="p-3">
                            @forelse($user->roles as $r)
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 text-gray-700 dark:text-gray-200 ml-1">
                            {{ $r->display_name ?? $r->name }}
                        </span>
                            @empty
                                <span class="text-xs text-gray-500">-</span>
                            @endforelse
                        </td>
                        <td class="p-3">
                            @can('users.update')
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="text-indigo-600 dark:text-indigo-300 hover:underline">ویرایش</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-6 text-center text-gray-500 dark:text-gray-400" colspan="6">کاربری یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    </div>
@endsection
