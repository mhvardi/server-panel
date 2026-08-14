@extends('layouts.app')

@section('title', 'احراز هویت دو مرحله‌ای')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">احراز هویت دو مرحله‌ای (2FA)</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/50 dark:text-green-300" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300" role="alert">
            <ul class="mb-0 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
        @if (Auth::user()->two_factor_enabled)
            {{-- 2FA is ENABLED --}}
            <div class="p-4 sm:p-6">
                <div class="flex items-center p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-100 dark:bg-gray-700 dark:text-green-400" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 ml-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div>
                        <span class="font-medium">وضعیت:</span> احراز هویت دو مرحله‌ای برای حساب شما <strong>فعال</strong> است.
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    حساب شما با یک لایه امنیتی اضافه محافظت می‌شود. برای غیرفعال کردن، روی دکمه زیر کلیک کنید.
                </p>
                <form action="{{ route('2fa.disable') }}" method="POST" onsubmit="return confirm('آیا از غیرفعال کردن احراز هویت دو مرحله‌ای اطمینان دارید؟ این کار امنیت حساب شما را کاهش می‌دهد.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none">
                        غیرفعال کردن 2FA
                    </button>
                </form>
            </div>
        @else
            {{-- 2FA is DISABLED --}}
            <div class="p-4 sm:p-6">
                <div class="flex items-center p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-100 dark:bg-gray-700 dark:text-yellow-300" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 ml-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div>
                        <span class="font-medium">وضعیت:</span> احراز هویت دو مرحله‌ای <strong>غیرفعال</strong> است.
                    </div>
                </div>

                @if(Auth::user()->two_factor_secret)
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        شما قبلاً 2FA را تنظیم کرده‌اید. برای فعال‌سازی مجدد، کافیست کد جدیدی از اپلیکیشن احراز هویت خود وارد کنید.
                    </p>
                @else
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        برای فعال‌سازی، کد QR زیر را با اپلیکیشن احراز هویت خود (مانند Google Authenticator) اسکن کنید یا کلید مخفی را به صورت دستی وارد نمایید.
                    </p>
                    <div class="flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                        <div class="w-48 h-48 bg-white p-2 rounded-lg shadow-inner">
                            {!! $qrCodeImage !!}
                        </div>
                        <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">کلید مخفی:</p>
                        <code class="mt-1 text-lg font-mono tracking-wider bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">{{ $secret }}</code>
                    </div>
                @endif

                <hr class="my-6 border-gray-200 dark:border-gray-700">

                <form action="{{ route('2fa.enable') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">کد تایید</label>
                        <input type="text" id="code" name="code" required autocomplete="off"
                               class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                               placeholder="123456" inputmode="numeric" pattern="[0-9]*">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">کد ۶ رقمی را از اپلیکیشن احراز هویت خود وارد کنید.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                        فعال کردن 2FA
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
