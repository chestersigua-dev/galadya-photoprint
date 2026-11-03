@extends('layouts.app')

@section('title', 'Studio Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pt-2">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
            <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Configuration Center</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900 mt-1">Galadya Studio Settings</h1>
        <p class="text-xs text-slate-500">Configure studio branding, 4R print dimensions, thermal receipt formatting, and system preferences.</p>
    </div>

    <!-- Settings Form -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-purple-100 shadow-xs">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Section 1: Studio Branding -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="building" class="w-4 h-4 text-purple-600"></i>
                    <span>Studio Branding & Identification</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Studio Name</label>
                        <input type="text" name="studio_name" value="{{ $settings['studio_name'] ?? 'Galadya Photoprint' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tagline</label>
                        <input type="text" name="studio_tagline" value="{{ $settings['studio_tagline'] ?? 'Self-Shoot Studio & High-Definition Printing' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" name="studio_contact" value="{{ $settings['studio_contact'] ?? '+63 917 123 4567' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="studio_email" value="{{ $settings['studio_email'] ?? 'hello@galadyastudio.ph' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500 focus:bg-white transition">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Studio Address</label>
                        <input type="text" name="studio_address" value="{{ $settings['studio_address'] ?? 'Unit 302, Lifestyle Building, Quezon City, Philippines' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500 focus:bg-white transition">
                    </div>
                </div>
            </div>

            <div class="border-t border-purple-100"></div>

            <!-- Section 2: 4R Print Specifications -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4 text-pink-500"></i>
                    <span>4R Print Engine Specifications</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Print Safe Margin (px)</label>
                        <input type="number" name="print_margin_px" value="{{ $settings['print_margin_px'] ?? '10' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500">
                        <span class="text-[10px] text-slate-400 mt-1 block">Outer border margin (spec: 10px)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Print Resolution (DPI)</label>
                        <input type="number" name="print_dpi" value="{{ $settings['print_dpi'] ?? '300' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500">
                        <span class="text-[10px] text-slate-400 mt-1 block">Target DPI (spec: 300 DPI)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Max Selection Pool</label>
                        <input type="number" name="max_selection_limit" value="{{ $settings['max_selection_limit'] ?? '10' }}" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500">
                        <span class="text-[10px] text-slate-400 mt-1 block">Photostrip capacity (spec: 10)</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-purple-100"></div>

            <!-- Section 3: Receipt Customization -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-purple-600"></i>
                    <span>Thermal POS Receipt Customization</span>
                </h2>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Footer Note</label>
                    <textarea name="receipt_footer_text" rows="2" class="w-full px-3.5 py-2 rounded-xl bg-purple-50/40 border border-purple-200/80 text-xs font-medium focus:outline-none focus:border-purple-500">{{ $settings['receipt_footer_text'] ?? 'Thank you for capturing your memories with Galadya Studio! Tag us on IG @galadyaph' }}</textarea>
                </div>
            </div>

            <div class="border-t border-purple-100"></div>

            <!-- Section 4: Studio Logo Asset -->
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="image" class="w-4 h-4 text-yellow-500"></i>
                    <span>Studio Logo Asset (`logo 1.png`)</span>
                </h2>

                <div class="flex items-center gap-6">
                    <div class="p-3 bg-purple-50/60 rounded-xl border border-purple-200 shrink-0">
                        <img src="{{ asset('images/logo 1.png') }}" class="h-12 object-contain" alt="Galadya Studio Official Logo">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Update Brand Logo (PNG with transparency recommended)</label>
                        <input type="file" name="studio_logo" accept="image/png,image/jpeg,image/svg+xml" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gradient-to-r file:from-[#1c053a] file:to-purple-900 file:text-yellow-300 hover:file:opacity-95">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end border-t border-purple-100">
                <button 
                    type="submit" 
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 hover:opacity-95 text-white font-bold text-xs uppercase tracking-wider shadow-md shadow-purple-600/20 transition cursor-pointer"
                >
                    Save Studio Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
