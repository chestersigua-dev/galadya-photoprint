@extends('layouts.app')

@section('title', 'Sales Blockchain Ledger & Access Audit Trail')

@section('content')
<div class="space-y-6" x-data="blockchainExplorer()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-purple-200/80">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-100 text-purple-800 border border-purple-200 uppercase tracking-wider">
                    Immutable Sales Chain
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-pink-100 text-pink-800 border border-pink-200">
                    SHA-256 Chained
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                <span class="bg-gradient-to-r from-purple-700 via-pink-600 to-yellow-500 bg-clip-text text-transparent">
                    Sales Blockchain Ledger & Access Audit
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Cryptographically linked, tamper-evident, append-only financial ledger and sales access log.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            <button 
                type="button" 
                @click="verifyLedger()" 
                :disabled="isVerifying"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500 hover:opacity-95 shadow-md shadow-purple-900/20 transition disabled:opacity-50 cursor-pointer"
            >
                <i data-lucide="shield-check" class="w-4 h-4" :class="{ 'animate-spin': isVerifying }"></i>
                <span x-text="isVerifying ? 'Verifying Hashes...' : 'Verify Ledger Integrity'"></span>
            </button>

            <form action="{{ route('admin.audit.sync') }}" method="POST" class="inline">
                @csrf
                <button 
                    type="submit" 
                    title="Anchor any unrecorded historical transactions"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-bold text-xs text-purple-800 bg-purple-100 hover:bg-purple-200 border border-purple-200 transition cursor-pointer"
                >
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-purple-600"></i>
                    <span>Sync Historical Sales</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Live Ledger Integrity Status Banner -->
    <div 
        class="p-4 rounded-2xl border transition-all duration-300"
        :class="verificationStatus.isValid ? 'bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-emerald-300/80 text-emerald-950 shadow-xs' : 'bg-rose-50 border-rose-300 text-rose-950 shadow-md'"
    >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div 
                    class="p-2.5 rounded-xl shrink-0"
                    :class="verificationStatus.isValid ? 'bg-emerald-600 text-white shadow-xs' : 'bg-rose-600 text-white animate-pulse'"
                >
                    <i :data-lucide="verificationStatus.isValid ? 'shield-check' : 'shield-alert'" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span 
                            class="font-black text-sm tracking-wide uppercase"
                            x-text="verificationStatus.isValid ? 'Ledger Status: 100% Cryptographically Sound & Tamper-Free' : 'SECURITY ALERT: LEDGER TAMPERING DETECTED'"
                        ></span>
                        <span 
                            class="text-[10px] font-mono px-2 py-0.5 rounded-full font-bold"
                            :class="verificationStatus.isValid ? 'bg-emerald-200/80 text-emerald-800' : 'bg-rose-200 text-rose-900'"
                            x-text="(verificationStatus.verifiedBlocks || {{ $stats['verification']['verified_blocks'] ?? 0 }}) + ' / ' + (verificationStatus.totalBlocks || {{ $stats['verification']['total_blocks'] ?? 0 }}) + ' Blocks Verified'"
                        ></span>
                    </div>
                    <p 
                        class="text-xs mt-0.5"
                        :class="verificationStatus.isValid ? 'text-emerald-800/90' : 'text-rose-900 font-semibold'"
                        x-text="verificationStatus.message"
                    ></p>
                </div>
            </div>

            <div class="text-right shrink-0">
                <span class="text-[10px] uppercase font-bold tracking-wider block opacity-75">Last Audit Run</span>
                <span class="text-xs font-mono font-semibold" x-text="verificationStatus.verifiedAt || '{{ $stats['verification']['verified_at'] ?? now('Asia/Manila')->toIso8601String() }}'"></span>
            </div>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Blocks -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Blocks Mined</span>
                <div class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <i data-lucide="blocks" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-black text-slate-900">{{ $stats['total_blocks'] }}</span>
                <span class="text-xs font-semibold text-purple-600">chained</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
                {{ $stats['sales_blocks_count'] }} sales • {{ $stats['access_blocks_count'] }} access audits
            </p>
        </div>

        <!-- Card 2: Audited Revenue -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Audited Revenue</span>
                <div class="p-2 rounded-xl bg-pink-50 text-pink-600">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-purple-700">₱{{ number_format($stats['total_audited_revenue'], 2) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
                Cryptographically anchored on ledger
            </p>
        </div>

        <!-- Card 3: Latest Block Hash -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Latest Hash</span>
                <div class="p-2 rounded-xl bg-yellow-50 text-yellow-600">
                    <i data-lucide="hash" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="flex items-center gap-1.5 font-mono text-xs font-bold text-slate-800 bg-purple-50/60 px-2 py-1 rounded-lg border border-purple-200/60">
                    <span class="truncate">{{ $stats['latest_block']?->short_hash ?? 'N/A' }}</span>
                    @if($stats['latest_block'])
                        <button 
                            type="button" 
                            @click="copyText('{{ $stats['latest_block']->hash }}', 'Hash copied!')"
                            class="text-purple-600 hover:text-purple-800"
                            title="Copy full 64-char hash"
                        >
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    @endif
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
                Head Block #{{ $stats['latest_block']?->block_index ?? 1 }}
            </p>
        </div>

        <!-- Card 4: Cryptographic Engine -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs hover:border-purple-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Audit Security</span>
                <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-lg font-black text-slate-900">SHA-256 Digest</span>
            </div>
            <p class="text-[11px] text-emerald-700 font-semibold mt-1 flex items-center gap-1">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                Tamper-evident hash chaining
            </p>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs">
        <form action="{{ route('admin.audit.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Keyword -->
            <div class="relative lg:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search by code, customer, hash, operator, IP..." 
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-purple-200 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition"
                >
            </div>

            <!-- Event Type Filter -->
            <div>
                <select 
                    name="event_type" 
                    class="w-full px-3 py-2 text-xs rounded-xl border border-purple-200 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition"
                >
                    <option value="">All Event Types</option>
                    <option value="sale.committed" {{ $eventType === 'sale.committed' ? 'selected' : '' }}>Sale Committed</option>
                    <option value="sale.accessed" {{ $eventType === 'sale.accessed' ? 'selected' : '' }}>Sale Accessed</option>
                    <option value="sale.receipt_printed" {{ $eventType === 'sale.receipt_printed' ? 'selected' : '' }}>Receipt Printed</option>
                    <option value="system.genesis" {{ $eventType === 'system.genesis' ? 'selected' : '' }}>Genesis Block</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <input 
                    type="date" 
                    name="date_from" 
                    value="{{ $dateFrom }}" 
                    class="w-full px-3 py-2 text-xs rounded-xl border border-purple-200 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition"
                >
            </div>

            <!-- Submit & Reset -->
            <div class="flex items-center gap-2">
                <button 
                    type="submit" 
                    class="flex-1 py-2 px-3 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition"
                >
                    Filter
                </button>
                @if($search || $eventType || $dateFrom || $dateTo)
                    <a 
                        href="{{ route('admin.audit.index') }}" 
                        class="p-2 text-slate-400 hover:text-slate-600 border border-slate-200 rounded-xl transition"
                        title="Clear filters"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Blockchain Explorer Table -->
    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-purple-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="boxes" class="w-4 h-4 text-purple-600"></i>
                <span>Cryptographic Ledger Blocks</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 font-mono font-semibold">
                    Page {{ $blocks->currentPage() }} of {{ $blocks->lastPage() }}
                </span>
            </h2>
            <span class="text-xs text-slate-400">Total: {{ $blocks->total() }} blocks</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-purple-50/50 text-[11px] uppercase tracking-wider text-purple-950 font-bold border-b border-purple-100">
                    <tr>
                        <th class="py-3 px-4">Block #</th>
                        <th class="py-3 px-4">Event Type</th>
                        <th class="py-3 px-4">Timestamp (PST)</th>
                        <th class="py-3 px-4">Transaction / Scope</th>
                        <th class="py-3 px-4">Grand Total</th>
                        <th class="py-3 px-4">Operator / IP</th>
                        <th class="py-3 px-4 font-mono">Current Block Hash</th>
                        <th class="py-3 px-4 font-mono">Previous Hash</th>
                        <th class="py-3 px-4 text-right">Inspect</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-100/60 font-medium">
                    @forelse ($blocks as $block)
                        <tr class="hover:bg-purple-50/30 transition">
                            <!-- Block Index -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 font-mono font-black text-purple-950 text-xs bg-purple-100/80 px-2.5 py-1 rounded-lg border border-purple-200">
                                    <i data-lucide="box" class="w-3.5 h-3.5 text-purple-600"></i>
                                    #{{ str_pad((string) $block->block_index, 5, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>

                            <!-- Event Type -->
                            <td class="py-3.5 px-4">
                                @if($block->event_type === 'sale.committed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        <i data-lucide="shopping-bag" class="w-3 h-3 text-pink-600"></i>
                                        Sale Committed
                                    </span>
                                @elseif($block->event_type === 'sale.accessed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        <i data-lucide="eye" class="w-3 h-3 text-blue-600"></i>
                                        Sale Accessed
                                    </span>
                                @elseif($block->event_type === 'sale.receipt_printed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-900 border border-yellow-200">
                                        <i data-lucide="printer" class="w-3 h-3 text-yellow-700"></i>
                                        Receipt Printed
                                    </span>
                                @elseif($block->event_type === 'system.genesis')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-900 text-yellow-300 border border-slate-700">
                                        <i data-lucide="sparkles" class="w-3 h-3 text-yellow-400"></i>
                                        Genesis Block
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">
                                        {{ $block->event_type }}
                                    </span>
                                @endif
                            </td>

                            <!-- Timestamp -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 block text-xs">{{ $block->formatted_date }}</span>
                                <span class="text-[10px] text-slate-400">{{ $block->created_at?->diffForHumans() }}</span>
                            </td>

                            <!-- Transaction / Scope -->
                            <td class="py-3.5 px-4">
                                @if($block->transaction_code)
                                    <span class="font-bold text-purple-950 font-mono text-xs block">{{ $block->transaction_code }}</span>
                                    <span class="text-[11px] text-slate-500">
                                        {{ $block->transaction?->customer?->full_name ?? ($block->payload['customer']['full_name'] ?? 'Walk-in Client') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">System Genesis</span>
                                @endif
                            </td>

                            <!-- Grand Total -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($block->event_type === 'sale.committed' && isset($block->payload['financials']['grand_total']))
                                    <span class="font-black text-purple-700 text-xs">
                                        ₱{{ number_format((float) $block->payload['financials']['grand_total'], 2) }}
                                    </span>
                                    <span class="block text-[10px] uppercase font-bold text-pink-600">
                                        {{ $block->payload['financials']['payment_method'] ?? 'cash' }}
                                    </span>
                                @elseif(isset($block->payload['grand_total']))
                                    <span class="font-semibold text-slate-700 text-xs">
                                        ₱{{ number_format((float) $block->payload['grand_total'], 2) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Operator & IP -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-800 text-xs block">{{ $block->operator_name ?? 'Staff' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $block->ip_address ?? '127.0.0.1' }}</span>
                            </td>

                            <!-- Current Hash -->
                            <td class="py-3.5 px-4 font-mono text-xs whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-900 border border-purple-200/80 font-bold" title="{{ $block->hash }}">
                                        {{ $block->short_hash }}
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="copyText('{{ $block->hash }}', 'Block hash copied!')"
                                        class="text-slate-400 hover:text-purple-600 transition"
                                        title="Copy SHA-256 hash"
                                    >
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </td>

                            <!-- Previous Hash -->
                            <td class="py-3.5 px-4 font-mono text-xs whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded {{ $block->isGenesis() ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-slate-50 text-slate-700 border border-slate-200' }}" title="{{ $block->previous_hash }}">
                                        {{ $block->short_previous_hash }}
                                    </span>
                                    @if(!$block->isGenesis())
                                        <button 
                                            type="button" 
                                            @click="copyText('{{ $block->previous_hash }}', 'Previous hash copied!')"
                                            class="text-slate-400 hover:text-purple-600 transition"
                                            title="Copy previous hash pointer"
                                        >
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Action: Inspect Block -->
                            <td class="py-3.5 px-4 text-right">
                                <button 
                                    type="button" 
                                    @click="inspectBlock({{ $block->id }})" 
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:text-white bg-purple-50 hover:bg-purple-600 px-3 py-1.5 rounded-lg border border-purple-200 hover:border-purple-600 transition shadow-xs cursor-pointer"
                                >
                                    <i data-lucide="file-code" class="w-3.5 h-3.5"></i>
                                    <span>Inspect</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                <i data-lucide="blocks" class="w-10 h-10 text-purple-300 mx-auto mb-2 opacity-60"></i>
                                <p class="font-bold text-slate-700 text-sm">No ledger blocks found</p>
                                <p class="text-slate-400 text-xs mt-0.5">Click "Sync Historical Sales" to anchor any unrecorded transactions into the blockchain.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($blocks->hasPages())
            <div class="p-4 border-t border-purple-100 bg-purple-50/20">
                {{ $blocks->links() }}
            </div>
        @endif
    </div>

    <!-- Block Inspector Modal -->
    <div 
        x-show="inspectorModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs"
        style="display: none;"
    >
        <div 
            class="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-purple-200 overflow-hidden flex flex-col max-h-[90vh]"
            @click.outside="inspectorModal = false"
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-purple-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-purple-600 text-white shadow-xs">
                        <i data-lucide="cube" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-900" x-text="'Block #' + String(inspectedBlock?.block_index || 0).padStart(5, '0')"></h3>
                            <span 
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono"
                                :class="inspectedBlock?.is_valid ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800'"
                                x-text="inspectedBlock?.is_valid ? 'MATHEMATICAL HASH VALID ✓' : 'TAMPERED HASH MISMATCH ✗'"
                            ></span>
                        </div>
                        <p class="text-xs text-slate-500" x-text="'Event: ' + (inspectedBlock?.event_type || '') + ' • Logged: ' + (inspectedBlock?.formatted_date || '')"></p>
                    </div>
                </div>
                <button @click="inspectorModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="overflow-y-auto py-4 space-y-4 flex-1 text-xs text-slate-700 pr-1">
                <!-- Hashes Grid -->
                <div class="space-y-2 bg-purple-50/50 p-4 rounded-2xl border border-purple-100">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Current Block SHA-256 Hash</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="font-mono text-xs font-black text-purple-900 bg-white px-2.5 py-1.5 rounded-xl border border-purple-200 break-all select-all flex-1" x-text="inspectedBlock?.hash"></span>
                            <button 
                                type="button" 
                                @click="copyText(inspectedBlock?.hash, 'Hash copied!')"
                                class="p-2 text-purple-600 hover:bg-purple-100 rounded-lg shrink-0 transition"
                                title="Copy"
                            >
                                <i data-lucide="copy" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Previous Block Pointer Hash</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="font-mono text-xs font-bold text-slate-700 bg-white px-2.5 py-1.5 rounded-xl border border-purple-200 break-all select-all flex-1" x-text="inspectedBlock?.previous_hash"></span>
                            <button 
                                type="button" 
                                @click="copyText(inspectedBlock?.previous_hash, 'Previous hash copied!')"
                                class="p-2 text-purple-600 hover:bg-purple-100 rounded-lg shrink-0 transition"
                                title="Copy"
                            >
                                <i data-lucide="copy" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Block Metadata Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Transaction Code</span>
                        <span class="text-xs font-black text-slate-900 font-mono" x-text="inspectedBlock?.transaction_code || 'GENESIS'"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Operator</span>
                        <span class="text-xs font-bold text-slate-900 truncate block" x-text="inspectedBlock?.operator_name || 'System'"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Client IP</span>
                        <span class="text-xs font-mono font-semibold text-slate-800" x-text="inspectedBlock?.ip_address || '127.0.0.1'"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Nonce</span>
                        <span class="text-xs font-mono font-bold text-slate-800" x-text="inspectedBlock?.nonce || 0"></span>
                    </div>
                </div>

                <!-- Canonical Payload JSON View -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="code" class="w-3.5 h-3.5 text-purple-600"></i>
                            <span>Anchored Financial Snapshot Payload (Canonical JSON)</span>
                        </span>
                        <button 
                            type="button" 
                            @click="copyText(JSON.stringify(inspectedBlock?.payload, null, 2), 'JSON Payload copied!')"
                            class="text-[10px] font-bold text-purple-700 hover:text-purple-900"
                        >
                            Copy Payload
                        </button>
                    </div>
                    <pre class="bg-slate-950 text-purple-200 font-mono text-[11px] p-4 rounded-2xl overflow-x-auto max-h-64 leading-relaxed border border-slate-800 shadow-inner" x-text="JSON.stringify(inspectedBlock?.payload, null, 2)"></pre>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-purple-100 flex items-center justify-between shrink-0">
                <span class="text-[11px] text-slate-500 font-medium">
                    Strictly Immutable & Append-Only Record
                </span>
                <button 
                    type="button" 
                    @click="inspectorModal = false" 
                    class="px-5 py-2 bg-gradient-to-r from-purple-600 to-pink-500 text-white text-xs font-bold rounded-xl hover:opacity-95 shadow-xs transition"
                >
                    Close Inspector
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div 
        x-show="toast.visible" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 border border-purple-400/30 text-xs font-bold"
        style="display: none;"
    >
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
        <span x-text="toast.message"></span>
    </div>
</div>

@push('scripts')
<script>
    function blockchainExplorer() {
        return {
            isVerifying: false,
            inspectorModal: false,
            inspectedBlock: null,
            verificationStatus: {
                isValid: {{ $stats['verification']['is_valid'] ? 'true' : 'false' }},
                message: "{{ addslashes($stats['verification']['message']) }}",
                totalBlocks: {{ $stats['verification']['total_blocks'] }},
                verifiedBlocks: {{ $stats['verification']['verified_blocks'] }},
                verifiedAt: "{{ $stats['verification']['verified_at'] }}",
            },
            toast: {
                visible: false,
                message: '',
                timer: null
            },
            showToast(msg) {
                this.toast.message = msg;
                this.toast.visible = true;
                clearTimeout(this.toast.timer);
                this.toast.timer = setTimeout(() => {
                    this.toast.visible = false;
                }, 3000);
                setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
            },
            copyText(text, successMsg = 'Copied to clipboard!') {
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(successMsg);
                }).catch(() => {
                    // Fallback
                    const el = document.createElement('textarea');
                    el.value = text;
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    this.showToast(successMsg);
                });
            },
            async verifyLedger() {
                this.isVerifying = true;
                try {
                    const res = await fetch("{{ route('admin.audit.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data && data.result) {
                        this.verificationStatus.isValid = data.result.is_valid;
                        this.verificationStatus.message = data.result.message;
                        this.verificationStatus.totalBlocks = data.result.total_blocks;
                        this.verificationStatus.verifiedBlocks = data.result.verified_blocks;
                        this.verificationStatus.verifiedAt = data.result.verified_at;
                        this.showToast(data.result.is_valid ? 'Ledger verified: 100% Tamper-Free!' : 'ALERT: Ledger verification failed!');
                    }
                } catch (err) {
                    console.error('Verification error:', err);
                    this.showToast('Verification check finished.');
                } finally {
                    this.isVerifying = false;
                    setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                }
            },
            async inspectBlock(blockId) {
                try {
                    const res = await fetch(`/admin/blockchain-audit/${blockId}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data && data.block) {
                        this.inspectedBlock = {
                            ...data.block,
                            is_valid: data.is_valid,
                            formatted_date: data.formatted_date,
                            canonical_payload: data.canonical_payload,
                        };
                        this.inspectorModal = true;
                        setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                    }
                } catch (err) {
                    console.error('Error fetching block:', err);
                    this.showToast('Failed to load block details.');
                }
            }
        };
    }
</script>
@endpush
@endsection

