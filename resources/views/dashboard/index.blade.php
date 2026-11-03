@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<div class="space-y-6 pt-2">
    <!-- Top Greeting and PST Business Date Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white p-1.5 flex items-center justify-center shadow-md shadow-purple-100 border border-purple-200 shrink-0">
                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya" class="h-full w-full object-contain">
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                        Studio Terminal Active
                    </span>
                    <span class="text-xs text-purple-600 font-semibold">Philippine Standard Time</span>
                </div>
                <h1 class="text-2xl font-black text-purple-950 mt-1">Galadya Studio Dashboard</h1>
                <p class="text-xs text-slate-500">Live self-shoot sessions, today's earnings, and automated 4R printing overview.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('workflow.start') }}" 
                class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-700 via-pink-600 to-yellow-400 hover:from-purple-600 hover:to-yellow-300 text-white font-extrabold px-5 py-2.5 rounded-xl shadow-md shadow-purple-500/25 text-xs transition transform active:scale-95 tracking-wide uppercase"
            >
                <i data-lucide="play-circle" class="w-4 h-4 text-yellow-200"></i>
                <span>Launch Ingestion & Print Wizard</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Metric Cards (Purple, Lavender, Pink, Yellow) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Today's Gross Revenue (Yellow Accent) -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs relative overflow-hidden group hover:border-yellow-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today's Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-110 transition border border-yellow-200">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-purple-950 tracking-tight">₱{{ number_format($todayGrossRevenue, 2) }}</p>
                <p class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1 mt-1">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                    <span>Real-time POS calculation</span>
                </p>
            </div>
        </div>

        <!-- 2. Sessions Completed Today (Pink Accent) -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs relative overflow-hidden group hover:border-pink-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sessions Today</span>
                <div class="w-10 h-10 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center group-hover:scale-110 transition border border-pink-200">
                    <i data-lucide="camera" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-purple-950 tracking-tight">{{ $sessionsCompletedToday }}</p>
                <p class="text-[11px] text-purple-600 font-semibold flex items-center gap-1 mt-1">
                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-pink-500"></i>
                    <span>Completed & printed</span>
                </p>
            </div>
        </div>

        <!-- 3. Active Clients Month-to-Date (Lavender Accent) -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs relative overflow-hidden group hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Clients MTD</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition border border-purple-200">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-purple-950 tracking-tight">{{ $activeClientsMTD }}</p>
                <p class="text-[11px] text-purple-600 font-semibold flex items-center gap-1 mt-1">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Studio clients this month</span>
                </p>
            </div>
        </div>

        <!-- 4. Most Popular Package (Purple Accent) -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs relative overflow-hidden group hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Popular Package</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center group-hover:scale-110 transition border border-purple-200">
                    <i data-lucide="flame" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-base font-black text-purple-950 truncate" title="{{ $popularPackage?->title ?? 'None' }}">
                    {{ $popularPackage?->title ?? 'Solo Self Shoot' }}
                </p>
                <p class="text-[11px] text-purple-600 font-semibold flex items-center gap-1 mt-1">
                    <span class="text-pink-600 font-bold">{{ $popularPackageCount }} sessions</span>
                    <span>booked all-time</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Middle Grid: Revenue Trend & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 7-Day Revenue Trend -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-black text-purple-950">7-Day Revenue Overview</h2>
                    <p class="text-xs text-slate-500">Gross studio income in Philippine Peso (₱)</p>
                </div>
                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-3 py-1 rounded-full border border-purple-100">Past 7 Days</span>
            </div>

            <!-- Visual Bar Graph -->
            <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-44 pt-6 px-2">
                @php
                    $maxRev = max(collect($weeklyTrend)->pluck('revenue')->max(), 1000);
                @endphp
                @foreach ($weeklyTrend as $item)
                    @php
                        $heightPercent = max(6, (int) round(($item['revenue'] / $maxRev) * 100));
                    @endphp
                    <div class="flex flex-col items-center gap-2 h-full justify-end group">
                        <span class="text-[10px] font-bold text-purple-900 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            ₱{{ number_format($item['revenue'], 0) }}
                        </span>
                        <div class="w-full bg-purple-50 rounded-t-xl overflow-hidden h-32 flex items-end">
                            <div 
                                class="w-full bg-gradient-to-t from-purple-700 via-purple-500 to-pink-400 rounded-t-xl transition-all duration-500 group-hover:from-purple-600 group-hover:to-yellow-300"
                                style="height: {{ $heightPercent }}%;"
                            ></div>
                        </div>
                        <span class="text-xs font-bold text-purple-950">{{ $item['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Studio Quick Hub (Deep Purple + Lavender Glow) -->
        <div class="bg-gradient-to-br from-[#1c053a] via-[#2d0856] to-[#140228] text-white p-6 rounded-2xl shadow-xl border border-purple-800/60 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-40 h-40 bg-pink-500/15 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-extrabold text-yellow-300 uppercase tracking-widest">Workflow Terminal</span>
                    <i data-lucide="zap" class="w-4 h-4 text-yellow-300"></i>
                </div>
                <h3 class="text-lg font-black text-white mb-2">Ready for Next Customer?</h3>
                <p class="text-xs text-purple-200/90 leading-relaxed mb-6">
                    Ingest camera RAW files, arrange 10 photostrip prints (5 left, 5 right), pick a solo 4R favorite, and print immediately.
                </p>

                <div class="space-y-2.5">
                    <a 
                        href="{{ route('workflow.start') }}" 
                        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black py-2.5 rounded-xl text-xs transition shadow-md shadow-purple-950 tracking-wider uppercase"
                    >
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <span>Start Ingestion Wizard</span>
                    </a>
                    <a 
                        href="{{ route('clients.index') }}" 
                        class="w-full flex items-center justify-center gap-2 bg-purple-900/60 hover:bg-purple-800 text-purple-100 font-bold py-2 rounded-xl text-xs transition border border-purple-700/50"
                    >
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Search Client Records</span>
                    </a>
                </div>
            </div>

            <div class="pt-6 border-t border-purple-800/70 mt-6 flex items-center justify-between text-[11px] text-purple-300 relative z-10">
                <span>Print Specs: 4R @ 300 DPI</span>
                <span class="text-yellow-300 font-bold">1200 x 1800 px</span>
            </div>
        </div>
    </div>

    <!-- Recent 10 Transactions Table -->
    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-purple-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-purple-950">Recent 10 Transactions</h2>
                <p class="text-xs text-slate-500">Live list of latest studio sessions, payments, and generated prints.</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-purple-700 hover:text-pink-600 flex items-center gap-1 transition">
                <span>View Full History</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/60 border-b border-purple-100 text-[11px] uppercase tracking-wider text-purple-900/70 font-bold">
                        <th class="py-3 px-6">Tx Code</th>
                        <th class="py-3 px-6">Customer</th>
                        <th class="py-3 px-6">Package</th>
                        <th class="py-3 px-6">Grand Total</th>
                        <th class="py-3 px-6">Payment</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6">Date & Time</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50 text-xs text-slate-700">
                    @forelse ($recentTransactions as $tx)
                        <tr class="hover:bg-purple-50/30 transition">
                            <td class="py-3.5 px-6 font-mono font-bold text-purple-950">
                                {{ $tx->transaction_code }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-purple-950 block">{{ $tx->customer->full_name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $tx->customer->contact_number }}</span>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-0.5 rounded-lg bg-purple-100 text-purple-900 font-bold text-[11px]">
                                    {{ $tx->package->title }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 font-black text-purple-950">
                                {{ $tx->formatted_grand_total }}
                            </td>
                            <td class="py-3.5 px-6 uppercase font-bold text-[11px] text-purple-800">
                                {{ $tx->payment_method }}
                            </td>
                            <td class="py-3.5 px-6">
                                @if($tx->payment_status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-50 text-yellow-800 border border-yellow-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-slate-500 text-[11px]">
                                {{ $tx->formatted_date }}
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <a 
                                    href="{{ route('transactions.receipt', $tx) }}" 
                                    target="_blank" 
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:text-white bg-purple-50 hover:bg-purple-700 px-3 py-1 rounded-lg transition"
                                >
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Receipt</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                No transactions recorded yet. Launch a shoot workflow to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

