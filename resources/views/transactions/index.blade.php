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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Financial & Session Audit</span>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Transaction History & Print Archives</h1>
            <p class="text-xs text-slate-500">Review past client bills, reprint POS thermal receipts, and redownload 4R files.</p>
        </div>

        <a 
            href="{{ route('workflow.start') }}" 
            class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md shadow-amber-500/20 text-xs transition"
        >
            <i data-lucide="camera" class="w-4 h-4"></i>
            <span>New Shoot Session</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative w-64">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Search by code or customer..."
                    class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs focus:outline-none focus:border-amber-500"
                >
                <div class="absolute left-3 top-2 text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </div>
            </div>

            <select name="status" class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <option value="">All Statuses</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition">
                Filter
            </button>
            @if ($search || $status)
                <a href="{{ route('transactions.index') }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-500">
            Total Transactions: <span class="font-bold text-slate-800">{{ $transactions->total() }}</span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-semibold">
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
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse ($transactions as $tx)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                {{ $tx->transaction_code }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-slate-900 block">{{ $tx->customer->full_name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $tx->customer->contact_number }}</span>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-medium text-slate-700 text-[11px]">
                                    {{ $tx->package->title }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-slate-500">
                                {{ $tx->addons->count() > 0 ? $tx->addons->count() . ' items (' . $tx->formatted_addons_total . ')' : 'None' }}
                            </td>
                            <td class="py-3.5 px-6 font-black text-amber-600 text-sm">
                                {{ $tx->formatted_grand_total }}
                            </td>
                            <td class="py-3.5 px-6 uppercase font-bold text-[10px] text-slate-600">
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
                                <button 
                                    type="button" 
                                    @click="viewTx({{ $tx->id }})"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700 hover:text-amber-600 bg-slate-100 hover:bg-amber-50 px-2.5 py-1 rounded-lg transition"
                                >
                                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                                    <span>Receipt</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 text-xs">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="p-4 border-t border-slate-100">
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
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200" @click.outside="receiptModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <span class="text-xs font-bold text-slate-800">POS Receipt View</span>
                <button @click="receiptModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Receipt Content -->
            <div id="modal-receipt-print-area" class="font-mono text-xs bg-slate-50 p-4 rounded-xl border border-slate-200 text-slate-800 space-y-2">
                <div class="text-center pb-2 border-b border-dashed border-slate-300">
                    <p class="font-bold text-sm" x-text="receiptData?.studio_name"></p>
                    <p class="text-[10px] text-slate-500" x-text="receiptData?.studio_tagline"></p>
                    <p class="text-[10px] text-slate-500" x-text="receiptData?.studio_contact"></p>
                </div>
                <div class="space-y-1 text-[11px] pb-2 border-b border-dashed border-slate-300">
                    <div>Code: <strong x-text="receiptData?.transaction_code"></strong></div>
                    <div>Date: <span x-text="receiptData?.date_time"></span></div>
                    <div>Client: <span x-text="receiptData?.customer_name"></span></div>
                    <div>Staff: <span x-text="receiptData?.operator"></span></div>
                </div>
                <div class="space-y-1 text-[11px] pb-2 border-b border-dashed border-slate-300">
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
                    <span>GRAND TOTAL</span>
                    <span class="text-amber-600" x-text="receiptData?.grand_total"></span>
                </div>
                <div class="text-[10px] text-slate-500 text-center pt-2 italic" x-text="receiptData?.footer_text"></div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" @click="receiptModal = false" class="px-3 py-1.5 text-xs text-slate-600">Close</button>
                <button type="button" @click="printModalReceipt()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl flex items-center gap-1.5">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Thermal Receipt</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

