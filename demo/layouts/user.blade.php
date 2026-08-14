{{-- layouts/user.blade.php --}}

@props([
    'title' => config('app.name', 'Laravel'),
])

<!doctype html>
<html lang="fa" dir="rtl" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>{{ $title }}</title>

    <script>
        (() => {
            const pref = localStorage.getItem('theme') || 'system';
            const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = pref === 'dark' || (pref === 'system' && sysDark);
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-size: 14px;}
        jdp-container { z-index: 9999 !important; }
        [x-cloak] { display: none !important; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100">
<div
    x-data="{
        items: [],
        notify(e) {
            const id = Date.now() + Math.random();
            const detail = e.detail || e; // Support both event detail and direct object
            this.items.push({
                id,
                type: detail.type || 'info',
                text: detail.text || detail.message || '',
            });
            // auto hide
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.items = this.items.filter(i => i.id !== id);
        }
    }"
    x-on:notify.window="notify($event)"
    x-init="
        @if(session()->has('success'))
            notify({ type: 'success', text: '{{ session('success') }}' });
        @endif
        @if(session()->has('error'))
            notify({ type: 'error', text: '{{ session('error') }}' });
        @endif
        @if(session()->has('warning'))
            notify({ type: 'warning', text: '{{ session('warning') }}' });
        @endif
        @if(session()->has('info'))
            notify({ type: 'info', text: '{{ session('info') }}' });
        @endif

        @if($errors->any())
            @foreach($errors->all() as $error)
                notify({ type: 'error', text: '{{ $error }}' });
            @endforeach
        @endif
    "
    class="fixed right-3 top-3 z-50 w-80 max-w-[90vw] space-y-2" style="z-index: 9999"
>
    <template x-for="item in items" :key="item.id">
        <div
            x-show="true"
            x-transition
            class="rounded-2xl px-4 py-3 text-sm shadow-lg border backdrop-blur bg-white/90 dark:bg-gray-900/90"
            :class="{
                'border-emerald-200 text-emerald-800 dark:border-emerald-500/60 dark:text-emerald-200': item.type === 'success',
                'border-red-200 text-red-800 dark:border-red-500/60 dark:text-red-200': item.type === 'error',
                'border-blue-200 text-blue-800 dark:border-blue-500/60 dark:text-blue-200': item.type === 'info',
                'border-amber-200 text-amber-800 dark:border-amber-500/60 dark:text-amber-200': item.type === 'warning',
            }"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="leading-relaxed" x-text="item.text"></p>
                <button
                    type="button"
                    class="text-xs opacity-60 hover:opacity-100"
                    @click="remove(item.id)"
                >
                    ✕
                </button>
            </div>
        </div>
    </template>
</div>

<div x-data="dashboardLayout()" x-init="init()" class="min-h-dvh flex">

    {{-- Desktop Sidebar --}}
    <aside :class="sidebarCollapsed ? 'w-20' : 'w-72'" class="transition-all duration-200 ease-in-out bg-white/90 dark:bg-gray-800/90 border-l lg:border-l-0 lg:border-r border-gray-200/70 dark:border-gray-700/60 min-h-dvh sticky top-0 z-30 hidden lg:flex flex-col">
        @include('user.partials.sidebar')
    </aside>

    {{-- Mobile Sidebar --}}
    <div x-show="mobileOpen" x-cloak class="lg:hidden" x-ref="dialog" aria-modal="true">
        {{-- Overlay --}}
        <div x-show="mobileOpen" x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/80 z-40"></div>

        {{-- Sidebar Content --}}
        <div class="fixed inset-0 flex z-50">
            <div x-show="mobileOpen" @click.outside="mobileOpen = false"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="relative ml-auto flex h-full w-full max-w-xs flex-col overflow-y-auto bg-white dark:bg-gray-800 pb-12 shadow-xl">

                {{-- Close button for mobile sidebar --}}
                <div class="absolute top-0 left-0 -ml-12 pt-2">
                    <button type="button" class="flex h-10 w-10 items-center justify-center p-2 text-gray-400" @click="mobileOpen = false">
                        <span class="sr-only">بستن منو</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- By setting sidebarCollapsed to false, we ensure the mobile menu is always expanded --}}
                <div x-data="{ sidebarCollapsed: false }" class="flex flex-col h-full">
                    @include('user.partials.sidebar')
                </div>
            </div>
        </div>
    </div>

    {{-- Main column --}}
    <div class="flex-1 min-w-0 flex flex-col">
        {{-- Topbar --}}
        @include('user.partials.topbar')

        {{-- Content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @if (View::hasSection('content'))
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>
    </div>
</div>


{{-- Alpine helpers --}}
<script>
    function dashboardLayout() {
        return {
            mobileOpen: false,
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            openMenus: JSON.parse(localStorage.getItem('openMenus') || '{}'),
            theme: localStorage.getItem('theme') || 'system',
            themeIcon: 'system',

            init() {
                this.applyTheme();
                this.$watch('theme', () => this.applyTheme());
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (this.theme === 'system') this.applyTheme();
                });
            },

            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
            },

            isMenuOpen(key) {
                if (this.sidebarCollapsed) return false;
                return this.openMenus[key] || false;
            },
            toggleMenu(key) {
                if (this.sidebarCollapsed) return;
                this.openMenus[key] = !this.openMenus[key];
                localStorage.setItem('openMenus', JSON.stringify(this.openMenus));
            },

            themeTitle() {
                return this.theme === 'dark' ? 'حالت تاریک'
                    : this.theme === 'light' ? 'حالت روشن'
                        : 'همسان با سیستم';
            },
            updateThemeIcon() {
                this.themeIcon = this.theme === 'dark' ? 'dark'
                    : this.theme === 'light' ? 'light'
                        : 'system';
            },
            applyTheme() {
                const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = this.theme === 'dark' || (this.theme === 'system' && sysDark);
                document.documentElement.classList.toggle('dark', isDark);
                this.updateThemeIcon();
            },
            cycleTheme() {
                this.theme = this.theme === 'system' ? 'dark' : this.theme === 'dark' ? 'light' : 'system';
                localStorage.setItem('theme', this.theme);
                this.applyTheme();
            },
        }
    }
</script>
@livewireScripts
@livewireScriptConfig
@stack('scripts')
</body>
</html>
