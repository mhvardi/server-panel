@extends('layouts.app')

@section('title', 'مشاهده گزارشات')

@section('content')
    <div class="max-w-full mx-auto" x-data="logViewer('{{ $activeKey }}')">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">مشاهده گزارشات (Logs)</h1>
            <div class="flex items-center gap-4 mt-4 sm:mt-0">
                {{-- Log Selector Dropdown --}}
                <div class="relative">
                    <select x-model="activeKey" @change="changeLog" class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($logs as $key => $item)
                            <option value="{{ $key }}">{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <button @click="loadLog" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.667 0l3.181-3.183m-4.991-2.691V5.25a3.375 3.375 0 00-3.375-3.375H8.25a3.375 3.375 0 00-3.375 3.375v5.25m13.5 0v-5.25a3.375 3.375 0 00-3.375-3.375H8.25a3.375 3.375 0 00-3.375 3.375v5.25" />
                    </svg>
                    <span class="mr-2">بارگذاری مجدد</span>
                </button>
                <form method="POST" :action="`/admin/logs/${activeKey}/clear`" onsubmit="return confirm('آیا از پاک کردن این فایل گزارش اطمینان دارید؟');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        <span class="mr-2">پاک کردن گزارش</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-gray-900 dark:bg-black/50 rounded-lg shadow-md font-mono text-xs text-white flex flex-col" style="height: 80vh;">
            <div class="p-4 border-b border-gray-700 dark:border-gray-600 flex justify-between items-center flex-shrink-0">
                <h3 class="font-semibold text-gray-300">محتوای فایل: <span class="text-yellow-400" x-text="activeLabel"></span></h3>
                <span class="text-gray-500" x-text="lastUpdated"></span>
            </div>
            <pre class="p-4 flex-grow overflow-auto custom-scrollbar" x-ref="logContainer" x-text="logContent"></pre>
        </div>
    </div>

    <script>
        function logViewer(initialKey) {
            return {
                logs: @json($logs),
                activeKey: initialKey,
                logContent: 'در حال بارگذاری...',
                loading: false,
                lastUpdated: '',
                interval: null,
                get activeLabel() {
                    return this.logs[this.activeKey]?.label || this.activeKey;
                },
                init() {
                    this.loadLog();
                    this.interval = setInterval(() => this.loadLog(), 10000);
                    window.addEventListener('beforeunload', () => clearInterval(this.interval));
                },
                changeLog() {
                    const url = new URL(window.location);
                    url.searchParams.set('key', this.activeKey);
                    window.history.pushState({}, '', url);

                    document.querySelector('form[method="POST"]').action = `/admin/logs/${this.activeKey}/clear`;

                    this.loadLog();
                },
                async loadLog() {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const url = `/admin/logs/${this.activeKey}`;
                        const response = await fetch(url);
                        const data = await response.json();
                        if (data.ok) {
                            this.logContent = data.content || 'فایل گزارش خالی است.';
                        } else {
                            this.logContent = `خطا در بارگذاری گزارش:\n${data.message}`;
                        }
                        this.lastUpdated = `آخرین بروزرسانی: ${new Date().toLocaleTimeString()}`;

                        this.$nextTick(() => {
                            this.$refs.logContainer.scrollTop = this.$refs.logContainer.scrollHeight;
                        });

                    } catch (error) {
                        this.logContent = 'خطای شبکه در هنگام بارگذاری گزارش.';
                        console.error('Log loading error:', error);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
@endsection
