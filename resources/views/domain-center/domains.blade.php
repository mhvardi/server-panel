@extends('layouts.app')

@section('title', 'دامنه‌های اختصاصی')

@section('content')
<div class="space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">دامنه‌های اختصاصی</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">تمامی دامنه‌های ثبت‌شده در مرکز دامنه</p>
        </div>
        <a href="{{ route('domain-center.connect') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            اتصال دامنه جدید
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">{{ session('error') }}</div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        @if($domains->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/>
                </svg>
                <p class="text-slate-500 dark:text-slate-400 font-medium">هیچ دامنه‌ای یافت نشد</p>
                <a href="{{ route('domain-center.connect') }}" class="mt-3 inline-block text-indigo-600 dark:text-indigo-400 text-sm hover:underline">اتصال اولین دامنه</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">دامنه</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">وضعیت</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">نوع DNS</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">سرویس</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">وضعیت SSL</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($domains as $domain)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors" x-data>
                                {{-- Domain name --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $domain->getFullUrl() }}" target="_blank"
                                           class="font-mono text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                            {{ $domain->domain }}
                                        </a>
                                        @if($domain->isParkedOn())
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">
                                                پارک روی {{ $domain->parkedOnDomain?->domain }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Status badge --}}
                                <td class="px-5 py-4">
                                    @if($domain->isConnected())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> متصل
                                        </span>
                                    @elseif($domain->isParkedDefault())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span> بدون سرویس
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-violet-500 inline-block"></span> پارک شده
                                        </span>
                                    @endif
                                </td>

                                {{-- DNS provider --}}
                                <td class="px-5 py-4">
                                    @if($domain->dns_provider === 'arvan')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">ابرآروان</span>
                                    @elseif($domain->dns_provider === 'self_ns')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">NS خودمان</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">خارجی</span>
                                    @endif
                                </td>

                                {{-- Service --}}
                                <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                    {{ $domain->service?->name ?? '—' }}
                                </td>

                                {{-- SSL status --}}
                                <td class="px-5 py-4">
                                    @if($domain->ssl_status === 'active')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            🔒 فعال
                                        </span>
                                    @elseif($domain->ssl_status === 'expired')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                            ⚠ منقضی
                                        </span>
                                    @elseif($domain->ssl_status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            ⏳ در انتظار
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-xs">ندارد</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        {{-- Assign service (if parked_default) --}}
                                        @if($domain->isParkedDefault())
                                            <button
                                                @click="$dispatch('open-assign-modal-{{ $domain->id }}')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                                                </svg>
                                                اختصاص سرویس
                                            </button>
                                        @endif

                                        {{-- Delete --}}
                                        <form action="{{ route('domain-center.destroy', $domain) }}" method="POST"
                                              onsubmit="return confirm('آیا از حذف دامنه {{ $domain->domain }} مطمئنید؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Assign Service Modal (Alpine inline) --}}
                            @if($domain->isParkedDefault())
                            <div x-data="{ open: false }"
                                 x-on:open-assign-modal-{{ $domain->id }}.window="open = true"
                                 x-show="open" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                                <div @click.outside="open = false"
                                     class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-md mx-4" dir="rtl">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">اختصاص سرویس به {{ $domain->domain }}</h3>
                                    <form action="{{ route('domain-center.assign', $domain) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">سرویس</label>
                                            <select name="service_id" required
                                                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                @foreach(\App\Models\Service::orderBy('name')->get() as $svc)
                                                    <option value="{{ $svc->id }}">{{ $svc->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex gap-3 justify-end">
                                            <button type="button" @click="open = false"
                                                    class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                                انصراف
                                            </button>
                                            <button type="submit"
                                                    class="px-5 py-2 rounded-xl text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                                اختصاص
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
