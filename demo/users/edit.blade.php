@extends('layouts.user')

@section('content')
    @php
        // نقش انتخابی امن
        $selectedRole = old('role', isset($selectedRole) ? $selectedRole : (optional(optional($user)->roles->first())->name ??
        ''));

        // همیشه یک کالکشن داشته باشیم؛ اگر رابطه لود/تعریف نشده بود هم خطا نده
        // نکته: برای بهترین نتیجه، در مدل User رابطه customValues را تعریف کن و در کنترلر edit با loadMissing لود کن.
        $customValues = collect(optional($user)->customValues ?? [])->keyBy('field_name');
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="font-semibold text-gray-900 dark:text-gray-100">
                {{ ($user && $user->exists) ? 'ویرایش کاربر' : 'ایجاد کاربر' }}
            </h1>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    بازگشت
                </a>

                @if($user && $user->exists)
                    @can('users.delete')
                        <form method="POST" action="{{ route('admin.users.destroy',$user) }}" class="inline"
                              onsubmit="return confirm('حذف این کاربر؟')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-2 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700">حذف</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        <form method="POST"
              action="{{ ($user && $user->exists) ? route('admin.users.update', $user) : route('admin.users.store') }}"
              enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            @if($user && $user->exists)
                @method('PUT')
            @endif

            {{-- فیلدهای پایه --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">نام</label>
                <input name="name" value="{{ old('name', $user->name ?? '') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">ایمیل</label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">شماره موبایل</label>
                <input name="mobile" value="{{ old('mobile', $user->mobile ?? '') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       placeholder="0912xxxxxxx">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">
                    رمز عبور {{ ($user && $user->exists) ? '(برای تغییر پر کنید)' : '' }}
                </label>
                <input type="password" name="password"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       @if(!($user && $user->exists)) required @endif>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">تأیید رمز عبور</label>
                <input type="password" name="password_confirmation"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       @if(!($user && $user->exists)) required @endif>
            </div>

            {{-- نقش و فیلدهای سفارشی --}}
            <div x-data="{ role: '{{ $selectedRole }}' }">
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">نقش</label>
                <select name="role"
                        class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                        required x-on:change="role = $event.target.value">
                    <option value="" disabled {{ $selectedRole ? '' : 'selected' }}>انتخاب نقش...</option>

                    {{-- نمایش نام فارسی نقش؛ مقدار = آیدی لاتین --}}
                    @foreach($roles as $k => $v)
                        @php
                            $optionValue = is_object($v) ? ($v->name ?? $k) : $k; // name (slug)
                            $optionLabel = is_object($v) ? ($v->display_name ?? $v->name ?? $k) : $v; // display_name
                        @endphp
                        <option value="{{ $optionValue }}" {{ $selectedRole === $optionValue ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>

                <div class="mt-2">
                    @isset($customFieldsByRole)
                        @foreach($customFieldsByRole as $roleName => $fields)
                            <div x-show="role === '{{ $roleName }}'">
                                @foreach($fields as $field)
                                    @php
                                        $type = strtolower($field->field_type ?? 'text');

                                        // مقدار ذخیره‌شده فعلی (ایمن)
                                        $cv = $customValues->get($field->field_name);
                                        $existing = $cv ? $cv->value : '';

                                        // مقدار old در اولویت
                                        $value = old('custom.' . $field->field_name, $existing);

                                        // برای checkbox چندانتخابی
                                        $checkedVals = [];
                                        if ($type === 'checkbox') {
                                        $oldArr = old('custom.' . $field->field_name);
                                        if (is_array($oldArr)) {
                                        $checkedVals = $oldArr;
                                        } else {
                                        $checkedVals = is_string($existing) ? (json_decode($existing, true) ?: []) : (is_array($existing) ?
                                        $existing : []);
                                        }
                                        }

                                        $opts = $field->meta['options'] ?? null; // اگر meta cast=array شده باشد
                                    @endphp

                                    <div class="mt-4">
                                        <label
                                            class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">{{ $field->label }}</label>

                                        @if($type === 'textarea')
                                            <textarea name="custom[{{ $field->field_name }}]" rows="4"
                                                      class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ $value }}</textarea>

                                        @elseif($type === 'file')
                                            <input type="file" name="custom[{{ $field->field_name }}]"
                                                   class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                                            {{-- اختیاری: اگر $existing مسیر فایل است، لینک/پریویو نشان بده --}}

                                        @elseif($type === 'select' && is_array($opts))
                                            <select name="custom[{{ $field->field_name }}]"
                                                    class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                                                @foreach($opts as $opt)
                                                    <option value="{{ $opt }}" {{ (string)$value === (string)$opt ? 'selected' : '' }}>
                                                        {{ $opt }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        @elseif($type === 'radio' && is_array($opts))
                                            <div class="space-y-2">
                                                @foreach($opts as $opt)
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="radio" name="custom[{{ $field->field_name }}]" value="{{ $opt }}"
                                                            {{ (string)$value === (string)$opt ? 'checked' : '' }}>
                                                        <span>{{ $opt }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @elseif($type === 'checkbox' && is_array($opts))
                                            <div class="space-y-2">
                                                @foreach($opts as $opt)
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="custom[{{ $field->field_name }}][]" value="{{ $opt }}"
                                                            {{ in_array($opt, $checkedVals, true) ? 'checked' : '' }}>
                                                        <span>{{ $opt }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @else
                                            <input type="{{ $type }}" name="custom[{{ $field->field_name }}]" value="{{ $value }}"
                                                   class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endisset
                </div>
            </div>

            <div class="pt-2">
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    {{ ($user && $user->exists) ? 'ذخیره تغییرات' : 'ایجاد کاربر' }}
                </button>
            </div>
        </form>
    </div>
@endsection
