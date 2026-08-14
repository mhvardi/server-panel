@extends('layouts.user')
@php
    $title = 'ایجاد نقش';
    $widgetGroups = collect($widgets ?? [])->groupBy('group');
    $oldWidgets = old('widgets', []);
@endphp
@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5">
            @csrf

            {{-- نام فارسی نقش (اجباری) --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">نام فارسی نقش</label>
                <input name="display_name" value="{{ old('display_name') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       required>
                @error('display_name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- آیدی لاتین (slug) - اختیاری؛ اگر خالی بماند اتومات ساخته می‌شود --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">آیدی لاتین (slug) -
                    اختیاری</label>
                <input name="name" value="{{ old('name') }}"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"
                       placeholder="مثلاً: super-admin">
                <p class="text-xs text-gray-500 mt-1">اگر خالی بماند از نام فارسی به‌صورت خودکار ساخته می‌شود.</p>
                @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- ویجت‌های داشبورد --}}

            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    ویجت‌های داشبورد
                </label>

                @forelse($widgetGroups as $groupLabel => $groupWidgets)
                    <div class="border rounded-xl border-gray-200 dark:border-gray-700 p-3">
                        <div class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-200">
                            {{ $groupLabel }}
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($groupWidgets as $widget)
                                @php
                                    $checked = is_array($oldWidgets) && array_key_exists($widget['key'], $oldWidgets);
                                @endphp
                                <label
                                        class="flex items-start gap-2 p-2 border rounded-lg text-sm bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">
                                    <input type="checkbox"
                                           name="widgets[{{ $widget['key'] }}]"
                                           class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            @checked($checked)>
                                    <div class="space-y-0.5">
                                        <div class="font-medium text-xs">
                                            {{ $widget['label'] ?? $widget['key'] }}
                                        </div>
                                        @if(!empty($widget['permission']))
                                            <div class="text-[10px] text-gray-400">
                                                نیازمند مجوز: <span class="font-mono">{{ $widget['permission'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($widget['description'] ?? null))
                                            <div class="text-[11px] text-gray-500">
                                                {{ $widget['description'] }}
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">
                        ویجتی ثبت نشده است.
                    </p>
                @endforelse
            </div>

            <div x-data="permissionForm({
                selected: @js(old('permissions', [])),
                groups: @js($permissionGroups)
             })"
                 class="space-y-4">

                <label class="block text-sm font-medium text-gray-800 dark:text-gray-200">مجوزها</label>

                {{-- جستجو سراسری --}}
                <input type="text" x-model="query"
                       placeholder="جستجو میان مجوزها (نام فارسی یا کلید)…"
                       class="w-full border rounded-lg p-2.5 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">

                {{-- گروه‌ها --}}
                <template x-for="(group, key) in groups" :key="key">
                    <div class="border rounded-lg border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center gap-3 px-3 py-2 bg-gray-50 dark:bg-gray-900/40">
                            <button type="button" class="font-medium text-gray-800 dark:text-gray-200"
                                    x-on:click="toggle(key)">
                                <span x-text="group.title"></span>
                                <span class="text-xs text-gray-500 ml-2"
                                      x-text="'(' + visibleCount(key) + '/' + group.items.length + ')'"></span>
                            </button>

                            <div class="ml-auto flex items-center gap-2">
                                <button type="button"
                                        class="text-xs px-2 py-1 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200"
                                        x-on:click="selectAll(key)">
                                    انتخاب همه
                                </button>
                                <button type="button"
                                        class="text-xs px-2 py-1 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200"
                                        x-on:click="unselectAll(key)">
                                    حذف همه
                                </button>
                            </div>
                        </div>

                        <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-2" x-show="open[key]">
                            <template x-for="perm in filtered(key)" :key="perm.name">
                                <label
                                        class="flex items-center gap-2 p-2 border rounded-lg text-sm bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">
                                    <input type="checkbox" name="permissions[]" :value="perm.name"
                                           :checked="selected.includes(perm.name)"
                                           @change="toggleOne(perm.name, $event.target.checked)">
                                    <span x-text="perm.label"></span>
                                    <span class="text-[10px] text-gray-400 ltr:ml-auto rtl:mr-auto"
                                          x-text="perm.name"></span>
                                </label>
                            </template>

                            {{-- اگر بعد از فیلتر چیزی نماند --}}
                            <p class="col-span-full text-xs text-gray-500" x-show="visibleCount(key) === 0">
                                گزینه‌ای مطابق جستجو پیدا نشد.
                            </p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- اسکریپت Alpine برای فرم مجوزها --}}
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('permissionForm', ({selected = [], groups = {}}) => ({
                        query: '',
                        selected,
                        groups,
                        open: Object.fromEntries(Object.keys(groups).map(k => [k, true])),

                        // فیلتر آیتم‌های یک گروه با توجه به query
                        filtered(key) {
                            const q = this.query.trim().toLowerCase();
                            const items = this.groups[key].items;
                            if (!q) return items;
                            return items.filter(i => (i.label + ' ' + i.name).toLowerCase().includes(q));
                        },
                        visibleCount(key) {
                            return this.filtered(key).length;
                        },

                        toggle(key) {
                            this.open[key] = !this.open[key];
                        },

                        toggleOne(name, checked) {
                            if (checked && !this.selected.includes(name)) this.selected.push(name);
                            if (!checked) this.selected = this.selected.filter(n => n !== name);
                        },
                        selectAll(key) {
                            const names = this.filtered(key).map(i => i.name);
                            this.selected = Array.from(new Set([...this.selected, ...names]));
                            // سینک چک‌باکس‌ها:
                            this.$nextTick(() => names.forEach(n => {
                                const el = this.$root.querySelector(`input[type=checkbox][value="${CSS.escape(n)}"]`);
                                if (el) el.checked = true;
                            }));
                        },
                        unselectAll(key) {
                            const names = this.filtered(key).map(i => i.name);
                            this.selected = this.selected.filter(n => !names.includes(n));
                            this.$nextTick(() => names.forEach(n => {
                                const el = this.$root.querySelector(`input[type=checkbox][value="${CSS.escape(n)}"]`);
                                if (el) el.checked = false;
                            }));
                        },
                    }))
                })
            </script>

            {{-- دکمه‌ها --}}
            <div class="pt-2">
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">ایجاد</button>
                <a href="{{ route('admin.roles.index') }}"
                   class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200">
                    بازگشت
                </a>
            </div>
        </form>
    </div>
@endsection
