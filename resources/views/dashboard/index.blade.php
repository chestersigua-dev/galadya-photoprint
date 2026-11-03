@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<div class="space-y-6 pt-2">
    <!-- Top Greeting and PST Business Date Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    Business Terminal Active
                </span>
                <span class="text-xs text-slate-500 font-medium">Philippine Standard Time</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Galadya Studio Dashboard</h1>
            <p class="text-xs text-slate-500">Live self-shoot sessions, today's earnings, and photo print workflow stats.</p>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('workflow.start') }}" 
                class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md shadow-amber-500/20 text-xs transition transform active:scale-95"
            >
                <i data-lucide="play-circle" class="w-4 h-4"></i>
                <span>Launch Import & Print Wizard</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Today's Gross Revenue -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-slate-900 tracking-tight">₱{{ number_format($todayGrossRevenue, 2) }}</p>
                <p class="text-[11px] text-emerald-600 font-medium flex items-center gap-1 mt-1">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                    <span>Real-time POS calculation</span>
                </p>
            </div>
        </div>

        <!-- 2. Sessions Completed Today -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sessions Today</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-110 transition">
                    <i data-lucide="camera" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $sessionsCompletedToday }}</p>
                <p class="text-[11px] text-slate-500 font-medium flex items-center gap-1 mt-1">
                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-sky-500"></i>
                    <span>Completed & printed</span>
                </p>
            </div>
        </div>

        <!-- 3. Active Clients Month-to-Date -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Clients MTD</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $activeClientsMTD }}</p>
                <p class="text-[11px] text-emerald-600 font-medium flex items-center gap-1 mt-1">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Clients visited this month</span>
                </p>
            </div>
        </div>

        <!-- 4. Most Popular Package -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Popular Package</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition">
                    <i data-lucide="flame" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-base font-bold text-slate-900 truncate" title="{{ $popularPackage?->title ?? 'None' }}">
                    {{ $popularPackage?->title ?? 'Solo Self Shoot' }}
                </p>
                <p class="text-[11px] text-slate-500 font-medium flex items-center gap-1 mt-1">
                    <span class="text-amber-600 font-semibold">{{ $popularPackageCount }} sessions</span>
                    <span>booked all-time</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Middle Grid: Revenue Trend & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 7-Day Revenue Trend -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">7-Day Revenue Overview</h2>
                    <p class="text-xs text-slate-500">Gross studio income in Philippine Peso (₱)</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Past 7 Days</span>
            </div>

            <!-- Simple CSS-based bar visualization -->
            <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-44 pt-6 px-2">
                @php
                    $maxRev = max(collect($weeklyTrend)->pluck('revenue')->max(), 1000);
                @endphp
                @foreach ($weeklyTrend as $item)
                    @php
                        $heightPercent = max(6, (int) round(($item['revenue'] / $maxRev) * 100));
                    @endphp
                    <div class="flex flex-col items-center gap-2 h-full justify-end group">
                        <span class="text-[10px] font-semibold text-slate-600 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            ₱{{ number_format($item['revenue'], 0) }}
                        </span>
                        <div class="w-full bg-slate-100 rounded-t-xl overflow-hidden h-32 flex items-end">
                            <div 
                                class="w-full bg-gradient-to-t from-amber-500 to-amber-300 rounded-t-xl transition-all duration-500 group-hover:from-amber-400 group-hover:to-amber-200"
                                style="height: {{ $heightPercent }}%;"
                            ></div>
                        </div>
                        <span class="text-xs font-medium text-slate-600">{{ $item['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Studio Quick Hub -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-950 text-white p-6 rounded-2xl shadow-lg border border-slate-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">Workflow Terminal</span>
                    <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Ready for Next Customer?</h3>
                <p class="text-xs text-slate-300 leading-relaxed mb-6">
                    Launch the step-by-step ingestion wizard to connect camera RAW files, select 10 photostrip prints, choose a solo 4R favorite, and print instantly.
                </p>

                <div class="space-y-2.5">
                    <a 
                        href="{{ route('workflow.start') }}" 
                        class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold py-2.5 rounded-xl text-xs transition"
                    >
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <span>Start Ingestion Wizard</span>
                    </a>
                    <a 
                        href="{{ route('clients.index') }}" 
                        class="w-full flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium py-2 rounded-xl text-xs transition"
                    >
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Search Client Records</span>
                    </a>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800 mt-6 flex items-center justify-between text-[11px] text-slate-400">
                <span>Print Specs: 4R @ 300 DPI</span>
                <span class="text-amber-400 font-semibold">1200 x 1800 px</span>
            </div>
        </div>
    </div>

    <!-- Recent 10 Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Recent 10 Transactions</h2>
                <p class="text-xs text-slate-500">Live list of latest studio sessions, payments, and generated prints.</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                <span>View Full History</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500 font-semibold">
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
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse ($recentTransactions as $tx)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-6 font-mono font-semibold text-slate-900">
                                {{ $tx->transaction_code }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="font-medium text-slate-900 block">{{ $tx->customer->full_name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $tx->customer->contact_number }}</span>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium text-[11px]">
                                    {{ $tx->package->title }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 font-bold text-slate-900">
                                {{ $tx->formatted_grand_total }}
                            </td>
                            <td class="py-3.5 px-6 uppercase font-semibold text-[11px] text-slate-600">
                                {{ $tx->payment_method }}
                            </td>
                            <td class="py-3.5 px-6">
                                @if($tx->payment_status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
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
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 hover:text-amber-600 bg-slate-100 hover:bg-amber-50 px-2.5 py-1 rounded-lg transition"
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

