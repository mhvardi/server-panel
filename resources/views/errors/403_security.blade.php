<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دسترسی غیرمجاز (403)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>body { font-family: Vazirmatn, sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-slate-800/80 backdrop-blur border border-red-500/30 rounded-3xl p-8 text-center shadow-2xl shadow-red-500/10">
        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 class="text-2xl font-black text-white mb-2">۴۰۳ - دسترسی محدود شده است</h1>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
            {{ $message ?? 'ورود به این پنل به دلیل تنظیمات امنیتی فقط برای کاربران داخل کشور ایران و آی‌پی‌های مجاز امکان‌پذیر می‌باشد.' }}
        </p>

        <div class="bg-slate-900/60 rounded-2xl p-4 text-xs space-y-2 border border-slate-700/50 text-right mb-6">
            <div class="flex justify-between items-center text-slate-400">
                <span>آدرس IP شما:</span>
                <span class="font-mono text-amber-400 font-bold">{{ $ip ?? request()->ip() }}</span>
            </div>
            <div class="flex justify-between items-center text-slate-400">
                <span>موقعیت شناسایی‌شده:</span>
                <span class="font-bold text-red-400">{{ $country ?? 'UNKNOWN' }}</span>
            </div>
        </div>

        <p class="text-[11px] text-slate-500">اگر ادمین سرور هستید و از VPN استفاده می‌کنید، ابتدا آن را خاموش کرده و مجدداً تلاش کنید.</p>
    </div>
</body>
</html>
