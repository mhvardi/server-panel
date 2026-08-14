@extends('layouts.user')
@php($title = 'مدیریت نقش‌ها')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="font-semibold text-gray-900 dark:text-gray-100">نقش‌ها</h1>
            @can('roles.create')
                <a href="{{ route('admin.roles.create') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                    + نقش جدید
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="p-3 text-right">نام نقش</th>
                    <th class="p-3 text-right">تعداد کاربران</th>
                    <th class="p-3 text-right">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($roles as $role)
                    <tr class="border-t border-gray-100 dark:border-gray-700/50">
                        <td class="p-3 text-gray-900 dark:text-gray-100">{{ $role->display_name ?? $role->name }}</td>
                        <!-- نمایش نام فارسی نقش -->
                        <td class="p-3 text-gray-700 dark:text-gray-200">{{ $roleUserCounts[$role->name] ?? 0 }}</td>
                        <td class="p-3">
                            @can('roles.update')
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="text-indigo-600 dark:text-indigo-300 hover:underline">ویرایش</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endcan

                            @can('roles.delete')
                                @if(!in_array($role->name, ['super-admin','Super Admin']))
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline"
                                          onsubmit="return confirm('حذف این نقش؟')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 ml-2 hover:underline">حذف</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-6 text-center text-gray-500 dark:text-gray-400" colspan="3">نقشی تعریف نشده.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
