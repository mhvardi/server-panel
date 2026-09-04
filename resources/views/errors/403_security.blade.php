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
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 select-none">
    <div class="max-w-sm w-full bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center shadow-xl">
        <h1 class="text-xl font-black text-rose-500 mb-3">403 دسترسی محدود</h1>
        <div class="text-xs text-slate-400 font-mono dir-ltr">
            آدرس IP: <span class="text-slate-200 font-bold">{{ $ip ?? request()->ip() }}</span>
        </div>
    </div>
</body>
</html>
