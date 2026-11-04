@extends('layouts.app')

@section('title', 'Transaction History')

@section('content')
<div class="space-y-6 pt-2" x-data="{ receiptModal: false, activeTx: null, receiptData: null,
    viewTx(id) {
        fetch(`/transactions/${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.activeTx = res.transaction;
                    this.receiptData = res.receipt_data;
                    this.receiptModal = true;
                    setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                }
            });
    },
    printModalReceipt() {
        const printContent = document.getElementById('modal-receipt-print-area');
        if (!printContent) return;
        const win = window.open('', '_blank', 'width=380,height=600');
        win.document.write('<html><head><title>POS Receipt</title><style>body{font-family:monospace;padding:10px;margin:0;width:75mm;font-size:11px;}</style></head><body>');
        win.document.write(printContent.innerHTML);
        win.document.write('</body></html>');
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); win.close(); }, 300);
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Financial & Session Audit</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Transaction History & Print Archives</h1>
            <p class="text-xs text-slate-500">Review past client bills, reprint POS thermal receipts, and redownload 4R files.</p>
        </div>

        <div class="flex items-center gap-2">
            <a 
                href="{{ route('admin.audit.index') }}" 
                class="inline-flex items-center gap-1.5 bg-purple-100 hover:bg-purple-200 text-purple-900 border border-purple-200 font-bold px-3.5 py-2.5 rounded-xl text-xs transition"
            >
                <i data-lucide="shield-check" class="w-4 h-4 text-purple-700"></i>
                <span>Blockchain Audit</span>
            </a>

            <a 
                href="{{ route('workflow.start') }}" 
                class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 hover:opacity-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-md shadow-purple-600/25 text-xs transition"
            >
                <i data-lucide="camera" class="w-4 h-4 text-yellow-200"></i>
                <span>New Shoot Session</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative w-64">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Search by code or customer..."
                    class="w-full pl-9 pr-3 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs text-slate-800 placeholder-purple-400/60 focus:outline-none focus:border-purple-500 focus:bg-white transition"
                >
                <div class="absolute left-3 top-2.5 text-purple-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </div>
            </div>

            <select name="status" class="px-3 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs text-slate-800 focus:outline-none focus:border-purple-500">
                <option value="">All Statuses</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-[#1c053a] to-purple-900 text-white text-xs font-semibold rounded-xl hover:opacity-95 transition shadow-xs">
                Filter
            </button>
            @if ($search || $status)
                <a href="{{ route('transactions.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-500">
            Total Transactions: <span class="font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">{{ $transactions->total() }}</span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/70 border-b border-purple-100 text-[11px] uppercase tracking-wider text-purple-900/70 font-semibold">
                        <th class="py-3.5 px-6">Tx Code</th>
                        <th class="py-3.5 px-6">Client</th>
                        <th class="py-3.5 px-6">Package</th>
                        <th class="py-3.5 px-6">Addons</th>
                        <th class="py-3.5 px-6">Grand Total</th>
                        <th class="py-3.5 px-6">Payment</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50 text-xs text-slate-700">
                    @forelse ($transactions as $tx)
                        <tr class="hover:bg-purple-50/40 transition">
                            <td class="py-3.5 px-6 font-mono font-bold text-purple-950">
                                {{ $tx->transaction_code }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-slate-900 block">{{ $tx->customer->full_name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-slate-400">{{ $tx->customer->contact_number }}</span>
                                    @if($tx->rfid_scanned)
                                        <span class="inline-flex items-center gap-1 text-[9px] font-mono font-bold text-purple-800 bg-purple-100 border border-purple-200 px-1.5 py-0.2 rounded">
                                            <i data-lucide="credit-card" class="w-2.5 h-2.5 text-pink-600"></i>
                                            {{ $tx->rfid_code ?? 'RFID' }} (+1 pt)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-0.5 rounded-full bg-purple-100 font-medium text-purple-800 text-[11px]">
                                    {{ $tx->package->title }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-slate-500">
                                {{ $tx->addons->count() > 0 ? $tx->addons->count() . ' items (' . $tx->formatted_addons_total . ')' : 'None' }}
                            </td>
                            <td class="py-3.5 px-6 font-black text-purple-700 text-sm">
                                {{ $tx->formatted_grand_total }}
                            </td>
                            <td class="py-3.5 px-6 uppercase font-bold text-[10px] text-pink-600">
                                {{ $tx->payment_method }}
                            </td>
                            <td class="py-3.5 px-6">
                                @if($tx->payment_status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-50 text-yellow-800 border border-yellow-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-slate-500 text-[11px]">
                                {{ $tx->formatted_date }}
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <button 
                                    type="button" 
                                    @click="viewTx({{ $tx->id }})"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700 hover:text-white bg-purple-50 hover:bg-purple-600 px-3 py-1 rounded-lg transition"
                                >
                                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                                    <span>Receipt</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                <i data-lucide="receipt-text" class="w-8 h-8 text-purple-300 mx-auto mb-2 opacity-50"></i>
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="p-4 border-t border-purple-100 bg-purple-50/20">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Receipt Modal -->
    <div 
        x-show="receiptModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-purple-200" @click.outside="receiptModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-purple-100 mb-3">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/logo 1.png') }}" class="h-6 object-contain" alt="Galadya Logo">
                    <span class="text-xs font-bold text-slate-800">POS Receipt View</span>
                </div>
                <button @click="receiptModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Receipt Content -->
            <div id="modal-receipt-print-area" class="font-mono text-xs bg-purple-50/30 p-4 rounded-xl border border-purple-200/80 text-slate-800 space-y-2">
                <div class="text-center pb-2 border-b border-dashed border-purple-200">
                    <img src="{{ asset('images/logo 1.png') }}" class="h-8 mx-auto mb-1 object-contain" alt="Galadya Logo">
                    <p class="font-bold text-sm text-purple-950" x-text="receiptData?.studio_name"></p>
                    <p class="text-[10px] text-slate-500" x-text="receiptData?.studio_tagline"></p>
                    <p class="text-[10px] text-slate-500" x-text="receiptData?.studio_contact"></p>
                </div>
                <div class="space-y-1 text-[11px] pb-2 border-b border-dashed border-purple-200">
                    <div>Code: <strong class="text-purple-950" x-text="receiptData?.transaction_code"></strong></div>
                    <div>Date: <span x-text="receiptData?.date_time"></span></div>
                    <div>Client: <span x-text="receiptData?.customer_name"></span></div>
                    <div>Staff: <span x-text="receiptData?.operator"></span></div>
                </div>
                <div class="space-y-1 text-[11px] pb-2 border-b border-dashed border-purple-200">
                    <div class="flex justify-between">
                        <span x-text="receiptData?.package_title"></span>
                        <strong x-text="receiptData?.package_price"></strong>
                    </div>
                    <template x-for="ad in receiptData?.addons" :key="ad.title">
                        <div class="flex justify-between pl-2 text-slate-600">
                            <span x-text="ad.title + ' x' + ad.qty"></span>
                            <span x-text="ad.subtotal"></span>
                        </div>
                    </template>
                </div>
                <div class="flex justify-between text-sm font-bold pt-1">
                    <span class="text-slate-900">GRAND TOTAL</span>
                    <span class="text-purple-700" x-text="receiptData?.grand_total"></span>
                </div>
                <template x-if="receiptData?.rfid_scanned">
                    <div class="space-y-0.5 text-[11px] pt-1.5 border-t border-dashed border-purple-200">
                        <div class="flex justify-between">
                            <span>Loyalty Card:</span>
                            <span class="font-bold font-mono" x-text="receiptData?.rfid_used || 'Active'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Points Earned:</span>
                            <span class="font-bold text-purple-700">+1 pt</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Points:</span>
                            <span class="font-bold" x-text="(receiptData?.total_points || 0) + ' pts'"></span>
                        </div>
                    </div>
                </template>
                <template x-if="receiptData?.blockchain_block">
                    <div class="space-y-0.5 text-[10px] pt-1.5 border-t border-dashed border-purple-200 font-mono">
                        <div class="flex justify-between items-center text-purple-950 font-bold">
                            <span>Blockchain Block:</span>
                            <span x-text="receiptData?.blockchain_block"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Ledger Hash:</span>
                            <span class="text-[9px]" x-text="receiptData?.blockchain_hash"></span>
                        </div>
                    </div>
                </template>
                <div class="text-[10px] text-purple-700/80 text-center pt-2 italic" x-text="receiptData?.footer_text"></div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2 border-t border-purple-100 pt-3">
                <button type="button" @click="receiptModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium hover:text-slate-800">Close</button>
                <button type="button" @click="printModalReceipt()" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 shadow-xs">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Thermal Receipt</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
