{{-- admin/partials/flash.blade.php --}}
<div class="space-y-4 mb-8">
    {{-- نمایش خطاهای اعتبارسنجی فرم --}}
    @if ($errors->any())
        <div class="p-5 rounded-[2rem] border border-red-200/50 dark:border-red-500/20 bg-white/80 dark:bg-red-500/5 backdrop-blur-xl shadow-2xl shadow-red-500/10 animate-in slide-in-from-top-4 duration-500 text-right">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-500/20 rounded-2xl text-red-600 dark:text-red-400 shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-red-800 dark:text-red-400 mb-1">خطای اعتبارسنجی</h4>
                    <ul class="list-disc list-inside text-xs text-red-700 dark:text-red-400/80 space-y-1 font-bold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- نمایش پیام موفقیت --}}
    @if (session('success'))
        <div class="p-5 rounded-[2rem] border border-emerald-200/50 dark:border-emerald-500/20 bg-white/80 dark:bg-emerald-500/5 backdrop-blur-xl shadow-2xl shadow-emerald-500/10 animate-in slide-in-from-top-4 duration-500 text-right">
            <div class="flex items-center gap-4 text-emerald-800 dark:text-emerald-400">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-500/20 rounded-2xl shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-sm font-black tracking-tight">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- نمایش خطاهای عملیاتی (مهم برای آپدیت گیت) --}}
    @if (session('error'))
        <div class="p-5 rounded-[2rem] border border-rose-200/50 dark:border-rose-500/20 bg-white/80 dark:bg-rose-500/5 backdrop-blur-xl shadow-2xl shadow-rose-500/10 animate-in slide-in-from-top-4 duration-500 text-right">
            <div class="flex items-start gap-4 text-rose-800 dark:text-rose-400">
                <div class="p-3 bg-rose-100 dark:bg-rose-500/20 rounded-2xl shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-black mb-1">خطا در اجرای عملیات</h4>
                    <p class="text-xs font-bold opacity-80 leading-relaxed">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
