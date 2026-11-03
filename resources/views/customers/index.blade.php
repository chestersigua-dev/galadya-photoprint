@extends('layouts.app')

@section('title', 'Client Database')

@section('content')
<div 
    class="space-y-6 pt-2"
    x-data="{
        drawerOpen: false,
        activeCustomer: null,
        loadingDetails: false,
        showCreateModal: false,

        viewCustomer(id) {
            this.drawerOpen = true;
            this.loadingDetails = true;
            fetch(`/clients/${id}`)
                .then(r => r.json())
                .then(res => {
                    this.loadingDetails = false;
                    if (res.success) {
                        this.activeCustomer = res.data;
                        setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                    }
                })
                .catch(err => {
                    this.loadingDetails = false;
                    alert('Error loading client profile.');
                });
        }
    }"
>
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Client CRM & Loyalty</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Client Database & Studio History</h1>
            <p class="text-xs text-slate-500">Track client visits, lifetime spend, past booked packages, and contact information.</p>
        </div>

        <button 
            type="button" 
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 hover:opacity-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-md shadow-purple-600/25 text-xs transition"
        >
            <i data-lucide="user-plus" class="w-4 h-4 text-yellow-200"></i>
            <span>Register New Client</span>
        </button>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('clients.index') }}" class="w-full sm:w-96 flex items-center gap-2">
            <div class="relative w-full">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Search by name, phone, or email..."
                    class="w-full pl-10 pr-4 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs text-slate-800 placeholder-purple-400/60 focus:outline-none focus:border-purple-500 focus:bg-white transition"
                >
                <div class="absolute left-3 top-2.5 text-purple-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-[#1c053a] to-purple-900 text-white text-xs font-semibold rounded-xl hover:opacity-95 transition shadow-xs">
                Search
            </button>
            @if ($search)
                <a href="{{ route('clients.index') }}" class="px-2.5 py-2 text-xs text-purple-600 hover:text-purple-800 font-medium">Clear</a>
            @endif
        </form>

        <div class="text-xs text-slate-500">
            Total Studio Clients: <span class="font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">{{ $customers->total() }}</span>
        </div>
    </div>

    <!-- Clients Datatable -->
    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/70 border-b border-purple-100 text-[11px] uppercase tracking-wider text-purple-900/70 font-semibold">
                        <th class="py-3.5 px-6">Client Name</th>
                        <th class="py-3.5 px-6">Contact Number</th>
                        <th class="py-3.5 px-6">Email</th>
                        <th class="py-3.5 px-6 text-center">Total Sessions</th>
                        <th class="py-3.5 px-6 text-right">Lifetime Spend</th>
                        <th class="py-3.5 px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50 text-xs text-slate-700">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-purple-50/40 transition cursor-pointer" @click="viewCustomer({{ $customer->id }})">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-700 to-pink-600 text-yellow-300 font-bold flex items-center justify-center text-xs shadow-xs">
                                        {{ substr($customer->first_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $customer->full_name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $customer->address ?? 'Metro Manila' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono font-medium text-slate-800">
                                {{ $customer->contact_number }}
                            </td>
                            <td class="py-4 px-6 text-slate-500">
                                {{ $customer->email ?? '—' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                    {{ $customer->total_sessions_count }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-black text-purple-700">
                                {{ $customer->formatted_lifetime_spend }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button 
                                    type="button" 
                                    @click.stop="viewCustomer({{ $customer->id }})"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700 hover:text-white bg-purple-50 hover:bg-purple-600 px-3 py-1 rounded-lg transition"
                                >
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>History</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                <i data-lucide="users" class="w-8 h-8 text-purple-300 mx-auto mb-2 opacity-50"></i>
                                No clients found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($customers->hasPages())
            <div class="p-4 border-t border-purple-100 bg-purple-50/20">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- SLIDE-OVER MODAL DRAWER: CLIENT HISTORY & SPEND (Requirement 3.C) -->
    <!-- ========================================================================= -->
    <div 
        x-show="drawerOpen" 
        x-transition.opacity 
        class="fixed inset-0 z-50 overflow-hidden bg-slate-950/60 backdrop-blur-xs flex justify-end"
        style="display: none;"
    >
        <div 
            class="w-full max-w-lg bg-white h-full shadow-2xl flex flex-col border-l border-purple-200"
            @click.outside="drawerOpen = false"
            x-show="drawerOpen"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Drawer Header -->
            <div class="p-6 border-b border-purple-800/40 flex items-center justify-between bg-gradient-to-r from-[#1c053a] via-purple-950 to-[#2e0854] text-white">
                <div>
                    <span class="text-[10px] font-bold text-yellow-300 uppercase tracking-widest flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3 h-3 text-pink-400"></i>
                        Customer Dossier
                    </span>
                    <h2 class="text-lg font-bold text-white mt-0.5" x-text="activeCustomer?.full_name || 'Loading Client...'"></h2>
                </div>
                <button @click="drawerOpen = false" class="text-purple-300 hover:text-white p-1 rounded-lg hover:bg-purple-900/50 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Drawer Content Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-purple-50/20">
                <!-- Loading State -->
                <div x-show="loadingDetails" class="text-center py-12 text-slate-400 text-xs">
                    <div class="w-8 h-8 border-2 border-purple-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                    <span>Loading customer history...</span>
                </div>

                <template x-if="!loadingDetails && activeCustomer">
                    <div class="space-y-6">
                        <!-- Key Stats Cards -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100/70 border border-purple-200 p-4 rounded-xl">
                                <span class="text-[10px] font-semibold text-purple-700 uppercase tracking-wider">Total Sessions</span>
                                <p class="text-2xl font-black text-purple-950 mt-1" x-text="activeCustomer.total_sessions_count"></p>
                            </div>
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100/70 border border-pink-200 p-4 rounded-xl">
                                <span class="text-[10px] font-semibold text-pink-700 uppercase tracking-wider">Lifetime Spend</span>
                                <p class="text-2xl font-black text-pink-950 mt-1" x-text="activeCustomer.lifetime_spend"></p>
                            </div>
                        </div>

                        <!-- Contact Details Block -->
                        <div class="bg-white p-4 rounded-xl border border-purple-100 shadow-xs space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Contact Number:</span>
                                <span class="font-bold text-purple-950 font-mono" x-text="activeCustomer.contact_number"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Email:</span>
                                <span class="font-medium text-slate-800" x-text="activeCustomer.email || 'None provided'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Address:</span>
                                <span class="font-medium text-slate-800" x-text="activeCustomer.address || 'Metro Manila'"></span>
                            </div>
                            <div class="pt-2 border-t border-purple-100">
                                <span class="text-slate-500 font-medium block mb-0.5">Studio Notes:</span>
                                <p class="text-slate-700 italic" x-text="activeCustomer.notes || 'No specific preferences recorded.'"></p>
                            </div>
                        </div>

                        <!-- Past Availed Sessions Table -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i data-lucide="history" class="w-4 h-4 text-purple-600"></i>
                                <span>Past Availed Sessions (<span x-text="activeCustomer.transactions?.length || 0"></span>)</span>
                            </h3>

                            <div class="space-y-2.5">
                                <template x-for="tx in activeCustomer.transactions" :key="tx.id">
                                    <div class="bg-white p-3 rounded-xl border border-purple-100 shadow-xs flex items-center justify-between text-xs hover:border-purple-300 transition">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-purple-950" x-text="tx.code"></span>
                                                <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 text-[10px] font-semibold" x-text="tx.package_title"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-0.5" x-text="tx.date"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-purple-700" x-text="tx.grand_total"></p>
                                            <span class="text-[9px] uppercase font-bold text-pink-600 bg-pink-50 px-1.5 py-0.5 rounded" x-text="tx.payment_method"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Drawer Footer: Quick Shoot Trigger -->
            <div class="p-4 border-t border-purple-100 bg-white flex items-center justify-between">
                <button @click="drawerOpen = false" class="text-xs text-slate-600 font-medium hover:text-purple-700">Close Drawer</button>
                <a 
                    href="{{ route('workflow.start') }}" 
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs transition shadow-sm"
                >
                    Start New Session for Client &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Create Client Modal -->
    <div 
        x-show="showCreateModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-purple-200" @click.outside="showCreateModal = false">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-purple-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                    <h3 class="text-sm font-bold text-slate-900">Register New Client</h3>
                </div>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('clients.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">First Name *</label>
                        <input type="text" name="first_name" required class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Last Name *</label>
                        <input type="text" name="last_name" required class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Contact Number *</label>
                    <input type="text" name="contact_number" required placeholder="0917..." class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Email Address</label>
                    <input type="email" name="email" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address</label>
                    <input type="text" name="address" placeholder="City or District" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Preferences, occasions..." class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-purple-100">
                    <button type="button" @click="showCreateModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium hover:text-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs rounded-xl transition shadow-xs">Create Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
