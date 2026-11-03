@extends('layouts.app')

@section('title', 'Packages & Add-ons')

@section('content')
<div class="space-y-6 pt-2" x-data="{ showNewPackageModal: false, showNewAddonModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Catalog Configuration</span>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Studio Packages & Add-on Services</h1>
            <p class="text-xs text-slate-500">Configure self-shoot tier pricing, durations, photographer fees, and pet extensions.</p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                @click="showNewPackageModal = true"
                class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition"
            >
                <i data-lucide="plus" class="w-4 h-4 text-amber-400"></i>
                <span>+ New Package</span>
            </button>
            <button 
                type="button" 
                @click="showNewAddonModal = true"
                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-3.5 py-2 rounded-xl text-xs transition shadow-sm"
            >
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>+ New Add-on</span>
            </button>
        </div>
    </div>

    <!-- Section A: Studio Packages Grid -->
    <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="package" class="w-4 h-4 text-amber-500"></i>
            <span>Active Self Shoot Packages ({{ $packages->count() }})</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($packages as $pkg)
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between hover:border-amber-400 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                {{ $pkg->is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">{{ $pkg->transactions_count }} booked</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">{{ $pkg->title }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">{{ $pkg->description }}</p>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">BASE RATE</span>
                            <span class="text-xl font-black text-amber-600">{{ $pkg->formatted_price }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 text-right">
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
            <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
            <span>Session Add-ons ({{ $addons->count() }})</span>
        </h2>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500 font-semibold">
                        <th class="py-3 px-6">Add-on Service</th>
                        <th class="py-3 px-6">Rate (PHP)</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @foreach ($addons as $ad)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-6 font-bold text-slate-900">
                                {{ $ad->title }}
                            </td>
                            <td class="py-3.5 px-6 font-black text-amber-600 text-sm">
                                {{ $ad->formatted_price }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
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
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="showNewPackageModal = false">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Add Studio Package</h3>
            <form method="POST" action="{{ route('packages.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Package Title</label>
                    <input type="text" name="title" required placeholder="e.g. Family Shoot Deluxe" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Base Price (₱)</label>
                    <input type="number" name="base_price" step="0.01" required placeholder="699.00" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Session duration, pax count..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs"></textarea>
                </div>
                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="showNewPackageModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl">Save Package</button>
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
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="showNewAddonModal = false">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Add New Service Add-on</h3>
            <form method="POST" action="{{ route('packages.addons.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Add-on Title</label>
                    <input type="text" name="title" required placeholder="e.g. Soft Copy Digital Drive" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Price (₱)</label>
                    <input type="number" name="price" step="0.01" required placeholder="150.00" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>
                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="showNewAddonModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl">Save Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

