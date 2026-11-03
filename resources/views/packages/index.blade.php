@extends('layouts.app')

@section('title', 'Packages & Add-ons')

@section('content')
<div class="space-y-6 pt-2" x-data="{ showNewPackageModal: false, showNewAddonModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Catalog Configuration</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Studio Packages & Add-on Services</h1>
            <p class="text-xs text-slate-500">Configure self-shoot tier pricing, durations, photographer fees, and pet extensions.</p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                @click="showNewPackageModal = true"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-[#1c053a] to-purple-900 hover:opacity-95 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition shadow-xs"
            >
                <i data-lucide="plus" class="w-4 h-4 text-yellow-300"></i>
                <span>+ New Package</span>
            </button>
            <button 
                type="button" 
                @click="showNewAddonModal = true"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 hover:opacity-95 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition shadow-md shadow-purple-600/20"
            >
                <i data-lucide="sparkles" class="w-4 h-4 text-yellow-200"></i>
                <span>+ New Add-on</span>
            </button>
        </div>
    </div>

    <!-- Section A: Studio Packages Grid -->
    <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="package" class="w-4 h-4 text-purple-600"></i>
            <span>Active Self Shoot Packages ({{ $packages->count() }})</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($packages as $pkg)
                <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs flex flex-col justify-between hover:border-purple-300 hover:shadow-md hover:shadow-purple-500/10 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $pkg->is_active ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $pkg->is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <span class="text-xs text-purple-400 font-semibold">{{ $pkg->transactions_count }} booked</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">{{ $pkg->title }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">{{ $pkg->description }}</p>
                    </div>

                    <div class="pt-4 border-t border-purple-50 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-purple-500 block font-semibold">BASE RATE</span>
                            <span class="text-xl font-black text-purple-700">{{ $pkg->formatted_price }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 text-right leading-tight">
                            Includes 4R Dual Strip<br>& Solo 4R Print
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section B: Package Add-ons Table -->
    <div class="space-y-3 pt-4">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="sparkles" class="w-4 h-4 text-pink-500"></i>
            <span>Session Add-ons ({{ $addons->count() }})</span>
        </h2>

        <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/70 border-b border-purple-100 text-[11px] uppercase tracking-wider text-purple-900/70 font-semibold">
                        <th class="py-3 px-6">Add-on Service</th>
                        <th class="py-3 px-6">Rate (PHP)</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6 text-right">Service Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50 text-xs text-slate-700">
                    @foreach ($addons as $ad)
                        <tr class="hover:bg-purple-50/40 transition">
                            <td class="py-3.5 px-6 font-bold text-slate-900">
                                {{ $ad->title }}
                            </td>
                            <td class="py-3.5 px-6 font-black text-pink-600 text-sm">
                                {{ $ad->formatted_price }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    {{ $ad->is_active ? 'Available' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right text-slate-400">
                                Standard Add-on
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: New Package -->
    <div 
        x-show="showNewPackageModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-purple-200" @click.outside="showNewPackageModal = false">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-purple-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                    <h3 class="text-sm font-bold text-slate-900">Add Studio Package</h3>
                </div>
                <button @click="showNewPackageModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('packages.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Package Title</label>
                    <input type="text" name="title" required placeholder="e.g. Family Shoot Deluxe" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Base Price (₱)</label>
                    <input type="number" name="base_price" step="0.01" required placeholder="699.00" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Session duration, pax count..." class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500"></textarea>
                </div>
                <div class="pt-3 flex items-center justify-end gap-2 border-t border-purple-100">
                    <button type="button" @click="showNewPackageModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-xs">Save Package</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: New Add-on -->
    <div 
        x-show="showNewAddonModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-purple-200" @click.outside="showNewAddonModal = false">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-purple-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                    <h3 class="text-sm font-bold text-slate-900">Add New Service Add-on</h3>
                </div>
                <button @click="showNewAddonModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('packages.addons.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Add-on Title</label>
                    <input type="text" name="title" required placeholder="e.g. Soft Copy Digital Drive" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Price (₱)</label>
                    <input type="number" name="price" step="0.01" required placeholder="150.00" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div class="pt-3 flex items-center justify-end gap-2 border-t border-purple-100">
                    <button type="button" @click="showNewAddonModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-xs">Save Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
