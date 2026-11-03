<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Galadya Photoprint') }} - @yield('title', 'Studio Management')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-800" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    <div class="min-h-full flex flex-col">
        <!-- Main Application Layout -->
        <div class="flex-1 flex overflow-hidden">
            
            <!-- Collapsible Left Sidebar -->
            <aside 
                :class="sidebarOpen ? 'w-64' : 'w-20'" 
                class="hidden md:flex flex-col bg-slate-900 text-slate-200 transition-all duration-300 ease-in-out border-r border-slate-800 z-30 select-none shadow-xl shrink-0"
            >
                <!-- Brand Header -->
                <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800 bg-slate-950">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-bold text-lg shadow-md shrink-0">
                            G
                        </div>
                        <div x-show="sidebarOpen" x-transition.opacity.duration.200ms class="truncate">
                            <span class="font-bold text-base tracking-wider text-white">GALADYA</span>
                            <span class="text-[10px] block text-amber-400 font-medium tracking-widest -mt-1">PHOTOPRINT</span>
                        </div>
                    </a>
                    <button 
                        @click="sidebarOpen = !sidebarOpen; setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50)" 
                        type="button" 
                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        :title="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
                    >
                        <i :data-lucide="sidebarOpen ? 'panel-left-close' : 'panel-left-open'" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <!-- 1. Dashboard -->
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="layout-dashboard" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Dashboard</span>
                    </a>

                    <!-- 2. Import & Print Workflow -->
                    <a 
                        href="{{ route('workflow.start') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('workflow.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="printer" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity class="flex-1">Import & Print</span>
                        <span x-show="sidebarOpen" class="text-[10px] px-1.5 py-0.5 rounded bg-amber-400 text-slate-950 font-bold uppercase tracking-wider">Fast</span>
                    </a>

                    <!-- 3. Client Database -->
                    <a 
                        href="{{ route('clients.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('clients.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="users" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Client Database</span>
                    </a>

                    <!-- 4. Packages & Add-ons -->
                    <a 
                        href="{{ route('packages.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('packages.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="sparkles" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Packages & Add-ons</span>
                    </a>

                    <!-- 5. Transaction History -->
                    <a 
                        href="{{ route('transactions.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('transactions.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="receipt" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Transactions</span>
                    </a>

                    <!-- 6. User & Role Management -->
                    @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->canAccessModule('users_roles')))
                    <a 
                        href="{{ route('admin.users.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="shield-check" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Users & Roles</span>
                    </a>
                    @endif

                    <!-- 7. Settings -->
                    <a 
                        href="{{ route('settings.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('settings.*') ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <i data-lucide="settings" class="w-5 h-5 shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition.opacity>Settings</span>
                    </a>
                </nav>

                <!-- Sidebar Footer: Operator Info -->
                <div class="p-3 border-t border-slate-800 bg-slate-950/60">
                    <div class="flex items-center gap-3">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full ring-2 ring-amber-500/40 object-cover shrink-0">
                        <div x-show="sidebarOpen" x-transition.opacity class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-amber-400 font-medium truncate">{{ auth()->user()->role?->name ?? 'Staff' }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-slate-100 overflow-y-auto">
                <!-- Top Global Header -->
                <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20 shadow-xs">
                    <!-- Left: Mobile Menu Trigger + Philippine Standard Time Dynamic Clock -->
                    <div class="flex items-center gap-4">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>

                        <!-- PST Clock Component -->
                        <div 
                            x-data="{
                                pstTime: '',
                                pstDate: '',
                                updateClock() {
                                    const now = new Date();
                                    // Format in Philippine Standard Time (UTC+8)
                                    const timeOpts = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
                                    const dateOpts = { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric', weekday: 'short' };
                                    this.pstTime = new Intl.DateTimeFormat('en-US', timeOpts).format(now);
                                    this.pstDate = new Intl.DateTimeFormat('en-US', dateOpts).format(now);
                                }
                            }" 
                            x-init="updateClock(); setInterval(() => updateClock(), 1000)"
                            class="flex items-center gap-3 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl text-xs"
                        >
                            <div class="flex items-center gap-1.5 text-amber-600 font-semibold">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="tracking-wide">PST</span>
                            </div>
                            <span class="text-slate-400">|</span>
                            <span class="font-bold text-slate-800 tracking-wider font-mono text-sm" x-text="pstTime">--:--:--</span>
                            <span class="hidden sm:inline text-slate-500 font-medium" x-text="pstDate">--- --, ----</span>
                        </div>
                    </div>

                    <!-- Right Header Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Quick Start Workflow Action Button -->
                        <a 
                            href="{{ route('workflow.start') }}" 
                            class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-amber-400 hover:text-amber-300 font-semibold text-xs px-3.5 py-2 rounded-xl transition shadow-xs border border-slate-700"
                        >
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">New Shoot Session</span>
                        </a>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-1.5 text-slate-600 hover:text-rose-600 hover:bg-rose-50 text-xs px-3 py-2 rounded-xl transition font-medium"
                                title="Sign Out"
                            >
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span class="hidden md:inline">Sign Out</span>
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Flash Alert Messages -->
                <div class="px-4 sm:px-6 pt-4">
                    @if (session('success'))
                        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3 shadow-xs" role="alert">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                            <span class="text-sm font-medium">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center gap-3 shadow-xs" role="alert">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                            <span class="text-sm font-medium">{{ session('error') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl shadow-xs" role="alert">
                            <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                                <span>Please check the errors below:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs space-y-0.5 text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Page Main View Body -->
                <main class="flex-1 px-4 sm:px-6 pb-12">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Re-initialize Lucide Icons on dynamic updates -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.initLucideIcons) window.initLucideIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>

