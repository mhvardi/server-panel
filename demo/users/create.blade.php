{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.user')
@php($title = 'کاربر جدید')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="font-semibold text-gray-900 dark:text-gray-100">ایجاد کاربر</h1>
            <a href="{{ route('admin.users.index') }}"
               class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                بازگشت
            </a>
        </div>

        @php
            // چرا: اطمینان از عدم null
            $safeSelectedRole = old('role', $selectedRole ?? '');
            // چرا: اگر کنترلر گروه‌بندی نکرده بود، اینجا گروه‌بندی کن
            $grouped = $customFieldsByRole instanceof \Illuminate\Support\Collection
                ? ($customFieldsByRole->first() instanceof \App\Models\CustomUserField
                    ? $customFieldsByRole->groupBy('role_name')
                    : $customFieldsByRole)
                : collect();
            $inputTypes = ['text','number','email','date','datetime-local','time','month','week','url','tel','password','color','range','hidden'];
        @endphp

        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">نام</label>
                <input name="name" value="{{ old('name') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">ایمیل</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">شماره موبایل</label>
                <input name="mobile" value="{{ old('mobile') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       placeholder="0912xxxxxxx">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">رمز عبور</label>
                <input type="password" name="password"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">تأیید رمز عبور</label>
                <input type="password" name="password_confirmation"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div x-data="{ role: '{{ $safeSelectedRole }}' }">
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">نقش</label>
                <select name="role"
                        class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                        required
                        x-on:change="role = $event.target.value">
                    <option value="" disabled {{ $safeSelectedRole ? '' : 'selected' }}>انتخاب نقش...</option>

                    {{-- نمایش نام فارسی نقش؛ مقدار = آیدی لاتین --}}
                    @foreach($roles as $k => $v)
                        @php
                            $optionValue = is_object($v) ? ($v->name ?? $k) : $k;                           // name (slug)
                            $optionLabel = is_object($v) ? ($v->display_name ?? $v->name ?? $k) : $v;       // display_name
                        @endphp
                        <option value="{{ $optionValue }}" {{ $safeSelectedRole === $optionValue ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>

                <div class="mt-2">
                    @foreach($grouped as $roleName => $fields)
                        <div x-show="role === '{{ $roleName }}'">
                            @foreach($fields as $field)
                                @php
                                    $type = strtolower($field->field_type ?? 'text');
                                    $valueOld = old('custom.' . $field->field_name);
                                @endphp
                                <div class="mt-4">
                                    <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">{{ $field->label }}</label>

                                    @if($type === 'textarea')
                                        <textarea name="custom[{{ $field->field_name }}]" rows="4"
                                                  class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ $valueOld }}</textarea>

                                    @elseif($type === 'file')
                                        <input type="file" name="custom[{{ $field->field_name }}]"
                                               class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">

                                    @elseif(in_array($type, $inputTypes, true))
                                        <input type="{{ $type }}" name="custom[{{ $field->field_name }}]" value="{{ $valueOld }}"
                                               class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">

                                    @else
                                        <input type="text" name="custom[{{ $field->field_name }}]" value="{{ $valueOld }}"
                                               class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-2">
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">ایجاد کاربر</button>
            </div>
        </form>
    </div>
@endsection
