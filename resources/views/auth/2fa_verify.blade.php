<!doctype html>
<html lang="fa" dir="rtl" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تایید هویت دو مرحله‌ای (2FA) | پنل مدیریت سرور</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: Vazirmatn, sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden selection:bg-indigo-500 selection:text-white">

    <div class="absolute top-1/4 -right-20 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">

        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-500 p-0.5 shadow-2xl shadow-indigo-500/30">
                <div class="w-full h-full bg-slate-900 rounded-[22px] flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">احراز هویت دو مرحله‌ای</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">
                کد ۶ رقمی اپلیکیشن تایید هویت (Google Authenticator) خود را وارد کنید
            </p>
        </div>

        <div class="bg-slate-900/90 border border-slate-800/80 backdrop-blur-xl rounded-3xl p-7 sm:p-8 shadow-2xl shadow-black/60">

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs leading-relaxed space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('2fa.verify.post') }}" method="POST" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <label for="code" class="block text-xs font-bold text-slate-300 text-center">
                        کد تایید ۶ رقمی
                    </label>
                    <input type="text" id="code" name="code" required autofocus autocomplete="off" maxlength="6"
                           class="w-full py-4 text-center rounded-2xl bg-slate-950/80 border border-slate-700/80 text-indigo-400 text-2xl tracking-[0.5em] font-mono font-black focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all dir-ltr"
                           placeholder="••••••">
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold text-sm shadow-xl shadow-indigo-600/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                    <span>تایید و ورود به پنل</span>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-800 text-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-slate-500 hover:text-rose-400 transition-colors">
                        انصراف و خروج از حساب
                    </button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>
