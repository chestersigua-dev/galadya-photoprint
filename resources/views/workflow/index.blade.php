@extends('layouts.app')

@section('title', 'Import & Print Workflow')

@section('content')
<div 
    class="max-w-7xl mx-auto space-y-6 pt-2"
    x-data="workflowWizard({
        initialPackages: {{ Js::from($packages) }},
        initialAddons: {{ Js::from($addons) }},
        initialCustomers: {{ Js::from($customers) }},
        maxLimit: {{ $maxSelectionLimit }}
    })"
>
    <!-- Wizard Stepper Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Operator Terminal</span>
                <h1 class="text-xl font-bold text-slate-900">Import & Interactive Photo Selection Workflow</h1>
            </div>

            <!-- Stepper Indicators -->
            <div class="flex items-center gap-2 sm:gap-3">
                <template x-for="(stepItem, index) in steps" :key="index">
                    <div class="flex items-center">
                        <button 
                            type="button"
                            @click="canNavigateTo(index + 1) && (currentStep = index + 1)"
                            :class="{
                                'bg-gradient-to-r from-purple-600 to-pink-500 text-white ring-4 ring-purple-500/20 font-bold shadow-sm': currentStep === (index + 1),
                                'bg-purple-600 text-white font-semibold': currentStep > (index + 1),
                                'bg-purple-50 text-purple-400 font-medium': currentStep < (index + 1)
                            }"
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-xs transition transform active:scale-95 cursor-pointer"
                            :title="stepItem.title"
                        >
                            <span x-show="currentStep <= (index + 1)" x-text="index + 1"></span>
                            <i x-show="currentStep > (index + 1)" data-lucide="check" class="w-4 h-4"></i>
                        </button>
                        <span 
                            class="hidden lg:inline ml-2 text-xs font-semibold"
                            :class="currentStep === (index + 1) ? 'text-purple-950 font-bold' : 'text-slate-400'"
                            x-text="stepItem.title"
                        ></span>
                        <div x-show="index < steps.length - 1" class="w-4 sm:w-8 h-0.5 bg-purple-100 mx-2"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- STEP 1: SELECT CLIENT & PACKAGE -->
    <!-- ========================================================================= -->
    <div x-show="currentStep === 1" x-transition.opacity.duration.300ms class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left 2 Cols: Client Selection + Package Selection -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- A. Client Selection Card -->
                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-purple-600"></i>
                                <span>1. Select or Register Client</span>
                            </h2>
                            <p class="text-xs text-slate-500">Pick an existing client or register a new one on the fly.</p>
                        </div>

                        <!-- Add Client Modal Trigger -->
                        <button 
                            type="button" 
                            @click.stop="openAddClientModal()" 
                            class="inline-flex items-center gap-1.5 bg-purple-50 hover:bg-purple-100 text-purple-900 text-xs font-bold px-3 py-1.5 rounded-xl border border-purple-200 transition"
                        >
                            <i data-lucide="user-plus" class="w-4 h-4 text-purple-600"></i>
                            <span>+ Add New Client</span>
                        </button>
                    </div>

                    <!-- Client Search Input & Dropdown -->
                    <div class="relative" @click.outside="openDropdown = false">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Search Client (Name, Phone, Email)</label>
                        <div class="relative">
                            <input 
                                id="client-search-input"
                                type="text" 
                                x-model="clientQuery"
                                @focus="openDropdown = true; searchClients()"
                                @input="openDropdown = true; searchClients()"
                                placeholder="Type client name e.g. Bea Alonzo or 0917..."
                                class="w-full px-4 py-2.5 rounded-xl bg-purple-50/30 border border-purple-200 text-sm focus:outline-none focus:border-purple-500 focus:bg-white transition"
                            >
                            <div class="absolute right-3 top-3 text-purple-400 flex items-center gap-1">
                                <span x-show="isSearchingClients" class="w-4 h-4 border-2 border-purple-600 border-t-transparent rounded-full animate-spin inline-block"></span>
                                <i x-show="!isSearchingClients" data-lucide="search" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Dropdown Results -->
                        <div 
                            x-show="openDropdown && (filteredClients.length > 0 || isSearchingClients || (clientQuery && clientQuery.trim().length > 0))" 
                            class="absolute z-20 left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white border border-purple-200 rounded-xl shadow-xl divide-y divide-purple-50"
                        >
                            <!-- Searching Indicator -->
                            <div x-show="isSearchingClients" class="p-3 text-center text-xs text-purple-600 flex items-center justify-center gap-2">
                                <span class="w-3.5 h-3.5 border-2 border-purple-600 border-t-transparent rounded-full animate-spin"></span>
                                <span>Searching client database...</span>
                            </div>

                            <!-- List of Clients -->
                            <template x-for="c in filteredClients" :key="c.id">
                                <div 
                                    @click="selectCustomer(c)"
                                    class="p-3 hover:bg-purple-50 cursor-pointer transition flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-xs font-bold text-purple-950" x-text="c.full_name || (c.first_name + ' ' + c.last_name)"></p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <p class="text-[11px] text-slate-500" x-text="(c.contact_number || '') + (c.email ? ' • ' + c.email : '')"></p>
                                            <template x-if="c.rfid">
                                                <span class="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-purple-100 text-purple-800 px-1.5 py-0.2 rounded border border-purple-200">
                                                    <i data-lucide="credit-card" class="w-2.5 h-2.5 text-pink-500"></i>
                                                    <span x-text="c.rfid"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-[10px] bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full font-bold block" x-text="(c.total_sessions_count || 0) + ' sessions'"></span>
                                        <span class="text-[9px] text-amber-700 font-bold mt-0.5 block" x-text="'⭐ ' + (c.points || 0) + ' pts'"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty Search / Not Found State with Quick Register Prompt -->
                            <div x-show="!isSearchingClients && filteredClients.length === 0 && clientQuery && clientQuery.trim().length > 0" class="p-4 text-center">
                                <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="user-x" class="w-4 h-4"></i>
                                </div>
                                <p class="text-xs font-semibold text-slate-800">No client found matching "<span class="text-purple-700" x-text="clientQuery"></span>"</p>
                                <p class="text-[11px] text-slate-500 mb-3">Register them now to proceed with this session.</p>
                                <button 
                                    type="button" 
                                    @click.stop="openAddClientModal(clientQuery)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-purple-700 to-pink-600 hover:from-purple-600 hover:to-pink-500 text-white font-bold text-xs shadow-sm transition"
                                >
                                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                    <span>Register "<span x-text="clientQuery"></span>"</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Selected Customer Pill -->
                    <div x-show="selectedCustomer" class="mt-4 p-4 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-pink-500 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                                <span x-text="selectedCustomer ? (selectedCustomer.full_name || selectedCustomer.first_name || '?').charAt(0) : '?'"></span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-purple-950" x-text="selectedCustomer ? (selectedCustomer.full_name || (selectedCustomer.first_name + ' ' + selectedCustomer.last_name)) : ''"></p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span class="text-xs text-purple-700" x-text="(selectedCustomer?.contact_number || '') + (selectedCustomer?.email ? ' • ' + selectedCustomer?.email : '')"></span>
                                    <template x-if="selectedCustomer?.rfid">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-mono font-bold text-purple-900 bg-purple-200/70 px-2 py-0.5 rounded-md">
                                            <i data-lucide="credit-card" class="w-2.5 h-2.5 text-pink-600"></i>
                                            <span x-text="selectedCustomer.rfid"></span>
                                        </span>
                                    </template>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-900 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded-md">
                                        ⭐ <span x-text="(selectedCustomer?.points || 0) + ' pts'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="changeSelectedCustomer()" 
                            class="text-xs text-pink-600 hover:text-pink-800 font-bold px-2 py-1"
                        >
                            Change
                        </button>
                    </div>
                </div>

                <!-- B. Package Selection Radio Grid -->
                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4 text-purple-600"></i>
                        <span>2. Choose Self Shoot Package</span>
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Select the studio duration and pax tier for this shoot.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="pkg in packages" :key="pkg.id">
                            <div 
                                @click="selectPackage(pkg.id)"
                                class="p-4 rounded-2xl border-2 cursor-pointer transition relative flex flex-col justify-between select-none"
                                :class="isPackageSelected(pkg.id) ? 'border-purple-600 bg-purple-50/50 ring-2 ring-purple-500/20 shadow-sm' : 'border-purple-100 hover:border-purple-300 bg-white'"
                            >
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-sm font-bold text-slate-900" x-text="pkg.title"></h3>
                                        <span class="text-sm font-black text-purple-700" x-text="'₱' + parseFloat(pkg.base_price).toFixed(2)"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-2" x-text="pkg.description"></p>
                                </div>
                                <div class="mt-3 flex items-center justify-between pt-2 border-t border-purple-100 text-[11px] text-slate-400">
                                    <span>Includes Dual Photostrip + Solo 4R</span>
                                    <div class="flex items-center gap-1.5">
                                        <span x-show="isPackageSelected(pkg.id)" class="text-[10px] font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">Selected</span>
                                        <i x-show="isPackageSelected(pkg.id)" data-lucide="check-circle-2" class="w-4 h-4 text-purple-600"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- C. Real-Time Add-ons Checkboxes & Counters -->
                <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-pink-500"></i>
                        <span>3. Optional Session Add-ons</span>
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Add extra shooting time, photographer assistance, pax, or pets.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="addon in addons" :key="addon.id">
                            <div 
                                @click="toggleAddon(addon.id)"
                                class="p-3.5 rounded-xl border flex items-center justify-between transition cursor-pointer select-none"
                                :class="isAddonSelected(addon.id) ? 'bg-pink-50/60 border-pink-300 ring-1 ring-pink-400/30' : 'bg-purple-50/30 border-purple-100 hover:border-purple-200'"
                            >
                                <div class="flex items-center gap-3 flex-1">
                                    <div 
                                        class="w-5 h-5 rounded-md flex items-center justify-center transition border"
                                        :class="isAddonSelected(addon.id) ? 'bg-pink-600 border-pink-600 text-white shadow-xs' : 'bg-white border-purple-300'"
                                    >
                                        <i x-show="isAddonSelected(addon.id)" data-lucide="check" class="w-3.5 h-3.5 stroke-[3]"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900" x-text="addon.title"></p>
                                        <p class="text-[11px] font-bold text-pink-600" x-text="'₱' + parseFloat(addon.price).toFixed(2)"></p>
                                    </div>
                                </div>

                                <!-- Qty Counter -->
                                <div 
                                    x-show="isAddonSelected(addon.id)" 
                                    @click.stop
                                    class="flex items-center gap-1 bg-white border border-pink-200 rounded-lg p-0.5 shadow-xs"
                                >
                                    <button 
                                        type="button" 
                                        @click.stop="decreaseAddonQty(addon.id)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-pink-100 hover:text-pink-700 text-xs font-bold transition"
                                    >-</button>
                                    <span class="w-6 text-center text-xs font-black text-purple-950" x-text="getAddonQty(addon.id)"></span>
                                    <button 
                                        type="button" 
                                        @click.stop="increaseAddonQty(addon.id)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-pink-100 hover:text-pink-700 text-xs font-bold transition"
                                    >+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Right Col: Live Summary & Navigation -->
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-[#1c053a] via-[#2d0856] to-[#140228] text-white p-6 rounded-2xl shadow-xl sticky top-24 border border-purple-800/60 relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-pink-500/20 rounded-full blur-2xl pointer-events-none"></div>

                    <span class="text-[10px] font-extrabold text-yellow-300 uppercase tracking-widest">Running Session Total</span>
                    <h3 class="text-lg font-black text-white mt-1 mb-4">Calculation Summary</h3>

                    <div class="space-y-3 text-xs border-b border-purple-800/80 pb-4">
                        <div class="flex items-center justify-between text-purple-200">
                            <span>Selected Client:</span>
                            <span class="font-bold text-white" x-text="selectedCustomer ? (selectedCustomer.full_name || (selectedCustomer.first_name + ' ' + selectedCustomer.last_name)) : 'None selected'"></span>
                        </div>

                        <div class="flex items-center justify-between text-purple-200">
                            <span>Base Package:</span>
                            <span class="font-bold text-white" x-text="getSelectedPackage() ? getSelectedPackage().title : 'None'"></span>
                        </div>
                        <div class="flex items-center justify-between text-purple-300 pl-2">
                            <span>Package Price:</span>
                            <span class="font-mono" x-text="'₱' + getPackagePrice().toFixed(2)"></span>
                        </div>

                        <div class="flex items-center justify-between text-purple-200">
                            <span>Add-ons Total:</span>
                            <span class="font-mono text-yellow-300 font-bold" x-text="'₱' + getAddonsTotal().toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-purple-300 font-bold">Running Total</span>
                        <span class="text-2xl font-black text-yellow-300" x-text="'₱' + getGrandTotal().toFixed(2)"></span>
                    </div>

                    <button 
                        type="button" 
                        @click="proceedToStep2()"
                        :disabled="!selectedCustomer || !selectedPackageId"
                        :class="(!selectedCustomer || !selectedPackageId) ? 'opacity-50 cursor-not-allowed bg-purple-950 text-purple-400' : 'bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white shadow-lg shadow-purple-950'"
                        class="w-full mt-6 py-3 px-4 rounded-xl font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <span>Proceed to Image Ingestion</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                    
                    <p x-show="!selectedCustomer || !selectedPackageId" class="text-[11px] text-yellow-300/90 text-center mt-2">
                        * Please select a client and package to continue.
                    </p>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- STEP 2 & STEP 3: FILE INGESTION & INTERACTIVE PHOTO SELECTION -->
    <!-- ========================================================================= -->
    <div x-show="currentStep === 2 || currentStep === 3" x-transition.opacity.duration.300ms class="space-y-6">
        
        <!-- Sticky Top Bar Monitor (Requirement 3.D Step 3) -->
        <div class="sticky top-16 z-30 bg-[#1c053a]/95 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-xl border border-purple-900/80 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Selected Count Badge with Color Switch Rule -->
                <div class="flex items-center gap-2">
                    <span class="text-xs text-purple-200 font-semibold">Photostrip Pool:</span>
                    <span 
                        class="px-3 py-1 rounded-xl text-xs font-black tracking-wider transition-all"
                        :class="{
                            'bg-yellow-400 text-purple-950 font-black ring-2 ring-yellow-400/40': starredCount <= maxLimit,
                            'bg-rose-600 text-white ring-4 ring-rose-500/30 animate-pulse font-black': starredCount > maxLimit
                        }"
                    >
                        <span x-text="'Selected: ' + starredCount + ' / ' + maxLimit"></span>
                    </span>
                </div>

                <!-- Favorite Pick Indicator -->
                <div class="flex items-center gap-2 border-l border-purple-800/80 pl-4">
                    <span class="text-xs text-purple-200 font-semibold">Solo 4R Favorite:</span>
                    <template x-if="favoritePhoto">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-pink-300 bg-pink-500/20 px-2.5 py-0.5 rounded-lg border border-pink-500/40 truncate max-w-[160px]">
                            <i data-lucide="heart" class="w-3.5 h-3.5 fill-pink-500 text-pink-500"></i>
                            <span x-text="favoritePhoto.original_filename"></span>
                        </span>
                    </template>
                    <template x-if="!favoritePhoto">
                        <span class="text-xs text-yellow-300 font-medium italic">Click ♥ on any photo</span>
                    </template>
                </div>
            </div>

            <!-- Quick Action Triggers -->
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="selectFirstTen()"
                    class="px-3 py-1.5 rounded-xl bg-purple-900/70 hover:bg-purple-800 text-purple-100 border border-purple-700/60 text-xs font-bold transition cursor-pointer"
                    title="Quick select the first 10 photos"
                >
                    Select First 10
                </button>
                <button 
                    type="button" 
                    @click="clearAllStarred()"
                    class="px-3 py-1.5 rounded-xl bg-purple-900/70 hover:bg-purple-800 text-purple-100 border border-purple-700/60 text-xs font-bold transition cursor-pointer"
                >
                    Clear Starred
                </button>
                <button 
                    type="button" 
                    x-show="photos.length > 0"
                    @click="clearAllPhotos()"
                    class="px-3 py-1.5 rounded-xl bg-rose-950/80 hover:bg-rose-900 text-rose-200 border border-rose-800 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    title="Clear all ingested images"
                >
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Clear All</span>
                </button>
                <button 
                    type="button" 
                    @click="proceedToStep4()"
                    :disabled="starredCount === 0 || !favoritePhoto"
                    :class="(starredCount === 0 || !favoritePhoto) ? 'opacity-50 cursor-not-allowed bg-purple-950 text-purple-400' : 'bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black shadow-md shadow-purple-950'"
                    class="px-4 py-1.5 rounded-xl text-xs uppercase tracking-wider flex items-center gap-1.5 transition cursor-pointer"
                >
                    <span>Preview 4R Prints</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Over-Selection Alert Notice (Non-blocking as required by spec) -->
        <div x-show="starredCount > maxLimit" x-transition.opacity class="bg-rose-50 border border-rose-300 p-4 rounded-2xl flex items-center gap-3 text-rose-800 shadow-xs">
            <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <div class="text-xs">
                <span class="font-bold">Notice: You have selected more than 10 photos (<span x-text="starredCount"></span> photos).</span>
                <p class="text-rose-700 mt-0.5">The photostrip composite uses exactly 10 photos (5 per column). You may continue adjusting your selections freely before final template generation.</p>
            </div>
        </div>

        <!-- Drag-and-Drop Ingestion Area (RAW & Standard Support) -->
        <div class="bg-purple-50/30 p-6 rounded-2xl border-2 border-dashed border-purple-200 hover:border-pink-500 transition relative overflow-hidden group">
            <input 
                type="file" 
                multiple
                accept=".jpg,.jpeg,.png,.cr2,.cr3,.nef,.arw,.dng" 
                @change="handleFileUpload($event)"
                class="absolute inset-0 opacity-0 cursor-pointer z-10"
            >
            <div class="flex flex-col items-center justify-center text-center py-6">
                <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center mb-3 group-hover:scale-110 transition shadow-xs">
                    <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                </div>
                <h3 class="text-sm font-black text-purple-950">Drag & Drop Camera RAW or Standard Images</h3>
                <p class="text-xs text-slate-500 max-w-md mt-1">
                    Supports <span class="font-bold text-purple-900">Canon (.CR2, .CR3), Nikon (.NEF), Sony (.ARW), Adobe (.DNG), .JPG, .PNG</span>. Auto-extracts embedded high-res JPEGs with zero lag.
                </p>

                <!-- Upload Progress Indicator -->
                <div x-show="isUploading" class="w-full max-w-xs mt-4">
                    <div class="w-full bg-purple-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-400 to-pink-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div>
                    </div>
                    <span class="text-[11px] font-bold text-purple-700 mt-1 block" x-text="'Processing ' + uploadProgress + '%...'"></span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- DUAL PHOTOSTRIP INGEST SPLIT BOARD (First 5 Left, Second 5 Right) -->
        <!-- ========================================================================= -->
        <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-purple-50">
                <div>
                    <h3 class="text-sm font-black text-purple-950 flex items-center gap-2">
                        <i data-lucide="columns-2" class="w-4 h-4 text-purple-600"></i>
                        <span>Dual Photostrip Real-Time Layout (10 4R Slots)</span>
                    </h3>
                    <p class="text-xs text-slate-500">
                        First 5 photos are mapped to the <strong class="text-purple-700">Left Strip</strong>; second 5 photos are mapped to the <strong class="text-yellow-700">Right Strip</strong>.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                        <span x-text="'Left Strip: ' + Math.min(starredCount, 5) + ' / 5'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-yellow-50 text-yellow-800 border border-yellow-200">
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                        <span x-text="'Right Strip: ' + (starredCount > 5 ? Math.min(starredCount - 5, 5) : 0) + ' / 5'"></span>
                    </span>
                </div>
            </div>

            <!-- Two-part Photostrip Board -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 relative">
                
                <!-- Left Part: Photostrip Column 1 (Photos 1 to 5) -->
                <div class="bg-gradient-to-b from-purple-50/70 via-purple-50/30 to-white p-4 rounded-xl border-2 border-purple-300/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-purple-100">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-purple-700 text-white font-black text-xs flex items-center justify-center shadow-xs">L</span>
                                <div>
                                    <h4 class="text-xs font-black text-purple-950">Left Part: Strip 1 (Photos 1 – 5)</h4>
                                    <p class="text-[10px] text-purple-700">First 5 selected photos printed on left photostrip column</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-800" x-text="Math.min(starredCount, 5) + '/5 Slots'"></span>
                        </div>

                        <!-- 5 Slots for Left Part -->
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="slot in getLeftStripSlots()" :key="'left-' + slot.slotNumber">
                                <div class="flex flex-col">
                                    <div 
                                        class="aspect-[3/4] rounded-xl border-2 relative overflow-hidden transition group"
                                        :class="slot.photo ? (slot.photo.is_favorite ? 'border-pink-500 ring-2 ring-pink-500/40 bg-slate-950' : 'border-purple-400 bg-slate-950') : 'border-dashed border-purple-200 bg-purple-50/40 flex flex-col items-center justify-center text-center p-1'"
                                    >
                                        <!-- Photo Assigned -->
                                        <template x-if="slot.photo">
                                            <div class="w-full h-full relative">
                                                <img :src="slot.photo.preview_url" class="w-full h-full object-cover">
                                                <!-- Slot Badge -->
                                                <span class="absolute top-1 left-1 bg-purple-700/90 backdrop-blur-xs text-white text-[9px] font-bold px-1.5 py-0.2 rounded shadow-xs" x-text="'#' + slot.slotNumber"></span>
                                                <!-- Solo 4R Heart Badge -->
                                                <div x-show="slot.photo.is_favorite" class="absolute bottom-1 left-1">
                                                    <span class="w-4 h-4 rounded-full bg-pink-600 text-white flex items-center justify-center shadow-xs text-[9px]">♥</span>
                                                </div>
                                                <!-- Action Overlay on Hover -->
                                                <div class="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1 p-1">
                                                    <button 
                                                        type="button" 
                                                        @click.stop="moveSlotEarlier(slot.photo)"
                                                        class="w-5 h-5 rounded bg-white/90 text-purple-900 hover:bg-purple-600 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Move up / earlier"
                                                    >◀</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="setFavorite(slot.photo)" 
                                                        class="w-5 h-5 rounded bg-white/90 text-pink-600 hover:bg-pink-500 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Set Solo 4R Favorite"
                                                    >♥</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="moveSlotLater(slot.photo)"
                                                        class="w-5 h-5 rounded bg-white/90 text-purple-900 hover:bg-purple-600 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Move down / later"
                                                    >▶</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="toggleStar(slot.photo)" 
                                                        class="w-5 h-5 rounded bg-white/90 text-slate-700 hover:bg-rose-600 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Remove from strip"
                                                    >✕</button>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Empty Slot Placeholder -->
                                        <template x-if="!slot.photo">
                                            <div class="flex flex-col items-center justify-center text-center p-1 w-full h-full">
                                                <span class="text-[11px] font-bold text-purple-600" x-text="'#' + slot.slotNumber"></span>
                                                <span class="text-[9px] text-purple-400 mt-0.5">Empty</span>
                                            </div>
                                        </template>
                                    </div>
                                    <span class="text-[9px] text-slate-500 truncate text-center mt-1" x-text="slot.photo ? slot.photo.original_filename : 'Slot ' + slot.slotNumber"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer Branding Mock for Left Strip (Authentic Logo) -->
                    <div class="mt-3 pt-2 border-t border-purple-100 flex items-center justify-between text-[10px] text-slate-500">
                        <img src="{{ asset('images/logo 1.png') }}" alt="Galadya" class="h-6 w-auto object-contain">
                        <span class="text-purple-600 font-mono text-[9px] font-bold">Col 1 Footer</span>
                    </div>
                </div>

                <!-- Right Part: Photostrip Column 2 (Photos 6 to 10) -->
                <div class="bg-gradient-to-b from-yellow-50/70 via-yellow-50/30 to-white p-4 rounded-xl border-2 border-yellow-300/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-yellow-100">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-yellow-400 text-purple-950 font-black text-xs flex items-center justify-center shadow-xs">R</span>
                                <div>
                                    <h4 class="text-xs font-black text-purple-950">Right Part: Strip 2 (Photos 6 – 10)</h4>
                                    <p class="text-[10px] text-yellow-700 font-semibold">Second 5 selected photos printed on right photostrip column</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-yellow-100 text-yellow-900" x-text="(starredCount > 5 ? Math.min(starredCount - 5, 5) : 0) + '/5 Slots'"></span>
                        </div>

                        <!-- 5 Slots for Right Part -->
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="slot in getRightStripSlots()" :key="'right-' + slot.slotNumber">
                                <div class="flex flex-col">
                                    <div 
                                        class="aspect-[3/4] rounded-xl border-2 relative overflow-hidden transition group"
                                        :class="slot.photo ? (slot.photo.is_favorite ? 'border-pink-500 ring-2 ring-pink-500/40 bg-slate-950' : 'border-yellow-400 bg-slate-950') : 'border-dashed border-yellow-200 bg-yellow-50/40 flex flex-col items-center justify-center text-center p-1'"
                                    >
                                        <!-- Photo Assigned -->
                                        <template x-if="slot.photo">
                                            <div class="w-full h-full relative">
                                                <img :src="slot.photo.preview_url" class="w-full h-full object-cover">
                                                <!-- Slot Badge -->
                                                <span class="absolute top-1 left-1 bg-yellow-400 text-purple-950 text-[9px] font-black px-1.5 py-0.2 rounded shadow-xs" x-text="'#' + slot.slotNumber"></span>
                                                <!-- Solo 4R Heart Badge -->
                                                <div x-show="slot.photo.is_favorite" class="absolute bottom-1 left-1">
                                                    <span class="w-4 h-4 rounded-full bg-pink-600 text-white flex items-center justify-center shadow-xs text-[9px]">♥</span>
                                                </div>
                                                <!-- Action Overlay on Hover -->
                                                <div class="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1 p-1">
                                                    <button 
                                                        type="button" 
                                                        @click.stop="moveSlotEarlier(slot.photo)"
                                                        class="w-5 h-5 rounded bg-white/90 text-purple-900 hover:bg-yellow-400 hover:text-purple-950 flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Move up / earlier"
                                                    >◀</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="setFavorite(slot.photo)" 
                                                        class="w-5 h-5 rounded bg-white/90 text-pink-600 hover:bg-pink-500 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Set Solo 4R Favorite"
                                                    >♥</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="moveSlotLater(slot.photo)"
                                                        class="w-5 h-5 rounded bg-white/90 text-purple-900 hover:bg-yellow-400 hover:text-purple-950 flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Move down / later"
                                                    >▶</button>
                                                    <button 
                                                        type="button" 
                                                        @click.stop="toggleStar(slot.photo)" 
                                                        class="w-5 h-5 rounded bg-white/90 text-slate-700 hover:bg-rose-600 hover:text-white flex items-center justify-center text-[10px] transition cursor-pointer"
                                                        title="Remove from strip"
                                                    >✕</button>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Empty Slot Placeholder -->
                                        <template x-if="!slot.photo">
                                            <div class="flex flex-col items-center justify-center text-center p-1 w-full h-full">
                                                <span class="text-[11px] font-bold text-yellow-600" x-text="'#' + slot.slotNumber"></span>
                                                <span class="text-[9px] text-yellow-500 mt-0.5">Empty</span>
                                            </div>
                                        </template>
                                    </div>
                                    <span class="text-[9px] text-slate-500 truncate text-center mt-1" x-text="slot.photo ? slot.photo.original_filename : 'Slot ' + slot.slotNumber"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer Branding Mock for Right Strip (Authentic Logo) -->
                    <div class="mt-3 pt-2 border-t border-yellow-100 flex items-center justify-between text-[10px] text-slate-500">
                        <img src="{{ asset('images/logo 1.png') }}" alt="Galadya" class="h-6 w-auto object-contain">
                        <span class="text-yellow-700 font-mono text-[9px] font-bold">Col 2 Footer</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Ingested Photos Grid Presentation -->
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-black text-purple-950 flex items-center gap-2">
                        <i data-lucide="image" class="w-4 h-4 text-purple-600"></i>
                        <span>Ingested Photos (<span x-text="photos.length"></span>)</span>
                    </h3>
                    <p class="text-xs text-slate-500">Click ★ to add to 10-photo strip pool. First 5 go to Left Strip; next 5 go to Right Strip. Click ♥ for the single favorite 4R print.</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500 mr-1">
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-purple-600 inline-block"></span> Left Strip (#1–5)</span>
                        <span class="inline-flex items-center gap-1 ml-2"><span class="w-3 h-3 rounded bg-yellow-400 inline-block"></span> Right Strip (#6–10)</span>
                        <span class="inline-flex items-center gap-1 ml-2"><span class="w-3 h-3 rounded bg-pink-500 inline-block"></span> Favorite (Solo 4R)</span>
                    </div>

                    <!-- Clear All Ingested Photos Button -->
                    <button 
                        type="button" 
                        x-show="photos.length > 0"
                        @click="clearAllPhotos()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition cursor-pointer shadow-xs"
                        title="Remove all ingested photos and reset selection"
                    >
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-600"></i>
                        <span>Clear All Photos</span>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="photos.length === 0" class="bg-white rounded-2xl border border-purple-100 p-12 text-center text-slate-400">
                <i data-lucide="camera-off" class="w-10 h-10 mx-auto text-purple-300 mb-2"></i>
                <p class="text-sm font-bold text-purple-950">No photos ingested yet.</p>
                <p class="text-xs">Drag and drop photos from the camera SD card above to begin interactive selection.</p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <template x-for="(photo, index) in photos" :key="photo.id">
                    <div 
                        class="bg-white rounded-2xl border-2 overflow-hidden shadow-xs relative group transition-all"
                        :class="{
                            'border-purple-600 ring-2 ring-purple-600/30': getPhotoSlotInfo(photo)?.strip === 'left' && !photo.is_favorite,
                            'border-yellow-400 ring-2 ring-yellow-400/30': getPhotoSlotInfo(photo)?.strip === 'right' && !photo.is_favorite,
                            'border-pink-500 ring-4 ring-pink-500/40': photo.is_favorite,
                            'border-purple-100 hover:border-purple-300': !photo.is_starred && !photo.is_favorite
                        }"
                    >
                        <!-- Thumbnail Image Container -->
                        <div class="aspect-[3/4] bg-slate-950 relative overflow-hidden">
                            <img 
                                :src="photo.preview_url" 
                                :alt="photo.original_filename" 
                                class="w-full h-full object-cover transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            >

                            <!-- Ingestion Badge: RAW or Standard -->
                            <div class="absolute top-2 left-2 flex items-center gap-1">
                                <span 
                                    x-show="photo.is_raw"
                                    class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-purple-900/90 text-purple-200 shadow-xs border border-purple-700/50"
                                >
                                    RAW
                                </span>
                                <span 
                                    x-show="!photo.is_raw"
                                    class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-900/80 text-slate-200 shadow-xs"
                                >
                                    JPG
                                </span>
                            </div>

                            <!-- Strip Slot Badge (Left Strip #1..5, Right Strip #6..10, or Queue) -->
                            <template x-if="getPhotoSlotInfo(photo)">
                                <div class="absolute top-2 right-2 flex items-center gap-1 z-10">
                                    <span 
                                        class="text-[9px] px-2 py-0.5 rounded-full shadow-md font-bold"
                                        :class="getPhotoSlotInfo(photo).badgeClass"
                                        x-text="getPhotoSlotInfo(photo).label"
                                    ></span>
                                </div>
                            </template>

                            <!-- Favorite Glowing Heart Badge (if favorite) -->
                            <div x-show="photo.is_favorite" class="absolute bottom-2 left-2">
                                <span class="px-2 py-0.5 rounded-full bg-pink-600 text-white font-bold text-[10px] flex items-center gap-1 shadow-md">
                                    <i data-lucide="heart" class="w-3 h-3 fill-white text-white"></i>
                                    <span>Solo 4R</span>
                                </span>
                            </div>
                        </div>

                        <!-- Interactive Control Bar on Each Card -->
                        <div class="p-2.5 bg-white border-t border-purple-50 flex items-center justify-between">
                            <div class="truncate max-w-[80px]" :title="photo.original_filename">
                                <p class="text-[11px] font-bold text-slate-800 truncate" x-text="photo.original_filename"></p>
                                <p class="text-[9px] text-slate-400 font-mono" x-text="photo.width + 'x' + photo.height"></p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <!-- Reorder Earlier / Later (if starred) -->
                                <template x-if="photo.is_starred">
                                    <div class="flex items-center gap-0.5">
                                        <button 
                                            type="button" 
                                            @click="moveSlotEarlier(photo)"
                                            class="w-6 h-6 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 flex items-center justify-center text-[9px] font-bold transition cursor-pointer"
                                            title="Shift earlier"
                                        >◀</button>
                                        <button 
                                            type="button" 
                                            @click="moveSlotLater(photo)"
                                            class="w-6 h-6 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 flex items-center justify-center text-[9px] font-bold transition cursor-pointer"
                                            title="Shift later"
                                        >▶</button>
                                    </div>
                                </template>

                                <!-- Star Toggle Button (★) -->
                                <button 
                                    type="button" 
                                    @click="toggleStar(photo)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition cursor-pointer"
                                    :class="photo.is_starred ? 'bg-yellow-100 text-yellow-800 font-bold' : 'bg-purple-50 text-purple-400 hover:text-yellow-600 hover:bg-yellow-50'"
                                    :title="photo.is_starred ? 'Remove from photostrip selection' : 'Add to 10-photo strip pool'"
                                >
                                    ★
                                </button>

                                <!-- Favorite Selector Button (♥) -->
                                <button 
                                    type="button" 
                                    @click="setFavorite(photo)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition cursor-pointer"
                                    :class="photo.is_favorite ? 'bg-pink-100 text-pink-600 font-bold' : 'bg-purple-50 text-purple-400 hover:text-pink-600 hover:bg-pink-50'"
                                    title="Set as single favorite for standalone 4R print"
                                >
                                    ♥
                                </button>

                                <!-- Remove Individual Photo Button (Trash) -->
                                <button 
                                    type="button" 
                                    @click="removePhoto(photo)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs text-purple-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                    title="Remove this photo from session"
                                >
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- STEP 4 & STEP 5: 4R TEMPLATE PREVIEW & TRANSACTION FINALIZATION -->
    <!-- ========================================================================= -->
    <div x-show="currentStep === 4" x-transition.opacity.duration.300ms class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-purple-100">
                <div>
                    <h2 class="text-base font-black text-purple-950 flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4 text-purple-600"></i>
                        <span>Step 4: Automated 4R Print Template Verification</span>
                    </h2>
                    <p class="text-xs text-slate-500">Verify the high-resolution 4R dual-strip and solo favorite layouts before final POS commit.</p>
                </div>

                <button 
                    type="button" 
                    @click="currentStep = 3"
                    class="inline-flex items-center gap-1.5 text-xs text-purple-700 hover:text-purple-950 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-3 py-1.5 rounded-xl font-bold transition cursor-pointer"
                >
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Adjust Photo Selections</span>
                </button>
            </div>

            <!-- Previews Comparison Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Output A: 4R Photostrip Composite Preview -->
                <div class="bg-purple-50/30 p-5 rounded-2xl border border-purple-100 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black text-purple-950">Output A: 4R Dual Photostrip (10 Photos)</span>
                        <span class="text-[10px] font-mono bg-purple-100 text-purple-800 px-2 py-0.5 rounded font-bold">1200 x 1800 px</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-3">Two photostrip columns side-by-side (5 photos per column) with centered Galadya Studio footer logo.</p>

                    <!-- Mock Photostrip Canvas (CSS representation of PrintLayoutService layout) -->
                    <div class="bg-white border-2 border-purple-200 rounded-2xl p-2.5 shadow-md aspect-[2/3] max-w-sm mx-auto flex gap-2">
                        <!-- Strip 1 (Left Part: Photos 1 to 5) -->
                        <div class="flex-1 flex flex-col justify-between border-r border-dashed border-purple-200 pr-1">
                            <div>
                                <div class="text-[9px] font-black uppercase tracking-wider text-purple-800 bg-purple-100 py-0.5 rounded text-center mb-1.5">
                                    Left Strip (1 – 5)
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(p, i) in getStripCol1()" :key="i">
                                        <div class="aspect-[16/9] bg-purple-50/60 rounded-lg overflow-hidden relative shadow-2xs border border-purple-100 flex items-center justify-center">
                                            <template x-if="p && p.preview_url">
                                                <img :src="p.preview_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!p || !p.preview_url">
                                                <div class="flex flex-col items-center justify-center text-center p-1">
                                                    <span class="text-[9px] font-bold text-purple-400 font-mono" x-text="'Slot #' + (i + 1)"></span>
                                                </div>
                                            </template>
                                            <span class="absolute top-0.5 left-0.5 bg-purple-900/80 text-white text-[8px] font-bold px-1 rounded" x-text="'#' + (i + 1)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Centered Footer Branding (Authentic Studio Logo) -->
                            <div class="text-center py-2 border-t border-purple-100 mt-1">
                                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-6 mx-auto object-contain">
                            </div>
                        </div>

                        <!-- Strip 2 (Right Part: Photos 6 to 10) -->
                        <div class="flex-1 flex flex-col justify-between pl-1">
                            <div>
                                <div class="text-[9px] font-black uppercase tracking-wider text-yellow-900 bg-yellow-100 py-0.5 rounded text-center mb-1.5">
                                    Right Strip (6 – 10)
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(p, i) in getStripCol2()" :key="i">
                                        <div class="aspect-[16/9] bg-yellow-50/60 rounded-lg overflow-hidden relative shadow-2xs border border-yellow-200 flex items-center justify-center">
                                            <template x-if="p && p.preview_url">
                                                <img :src="p.preview_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!p || !p.preview_url">
                                                <div class="flex flex-col items-center justify-center text-center p-1">
                                                    <span class="text-[9px] font-bold text-yellow-700 font-mono" x-text="'Slot #' + (i + 6)"></span>
                                                </div>
                                            </template>
                                            <span class="absolute top-0.5 left-0.5 bg-yellow-500 text-purple-950 text-[8px] font-black px-1 rounded" x-text="'#' + (i + 6)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Centered Footer Branding (Authentic Studio Logo) -->
                            <div class="text-center py-2 border-t border-purple-100 mt-1">
                                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-6 mx-auto object-contain">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Output B: Solo Favorite 4R Print Preview -->
                <div class="bg-purple-50/30 p-5 rounded-2xl border border-purple-100 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black text-purple-950">Output B: Solo Favorite 4R Print</span>
                        <span class="text-[10px] font-mono bg-purple-100 text-purple-800 px-2 py-0.5 rounded font-bold">1200 x 1800 px</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-3">Selected favorite scaled and centered with Galadya Studio logo placed at bottom-left corner (3% canvas area).</p>

                    <!-- Mock Solo Canvas -->
                    <div class="bg-white border-2 border-purple-200 rounded-2xl p-2.5 shadow-md aspect-[2/3] max-w-sm mx-auto relative flex items-center justify-center overflow-hidden">
                        <template x-if="favoritePhoto">
                            <img :src="favoritePhoto.preview_url" class="w-full h-full object-contain">
                        </template>
                        <template x-if="!favoritePhoto">
                            <div class="text-center p-6 text-purple-400">
                                <i data-lucide="heart" class="w-8 h-8 mx-auto text-pink-300 mb-2"></i>
                                <p class="text-xs font-bold text-purple-900">No favorite photo selected</p>
                                <p class="text-[10px] text-purple-500 mt-1">Select a favorite photo (♥) in Step 3 to preview solo 4R print.</p>
                            </div>
                        </template>

                        <!-- Bottom-left authentic logo overlay @ 3% canvas area -->
                        <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-xs px-2.5 py-1.5 rounded-xl shadow-md border border-purple-200/60">
                            <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-6 w-auto object-contain">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Payment Finalization Section -->
            <div class="mt-8 pt-6 border-t border-purple-100">
                <h3 class="text-base font-black text-purple-950 mb-4 flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-purple-600"></i>
                    <span>Step 5: Finalize Payment & Commit Transaction</span>
                </h3>

                <!-- RFID Loyalty & Discount Card Scanning Station -->
                <div class="mb-6 p-4 rounded-2xl border-2 transition-all"
                    :class="rfidCardScanned ? 'bg-gradient-to-r from-purple-50 via-pink-50 to-amber-50 border-purple-300 shadow-sm' : 'bg-purple-50/40 border-purple-200/80'">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-purple-700 to-pink-600 text-yellow-300 flex items-center justify-center shadow-xs">
                                <i data-lucide="scan-line" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-purple-950 flex items-center gap-2">
                                    <span>RFID Discount & Loyalty Card</span>
                                    <span class="text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-full font-black bg-amber-100 text-amber-900 border border-amber-200 shadow-2xs">
                                        ⭐ +1 Point per Scan
                                    </span>
                                </h4>
                                <p class="text-[11px] text-slate-500">Scan or tap client's RFID card to activate loyalty discount privileges and credit 1 point.</p>
                            </div>
                        </div>

                        <!-- Scanned Status Badge -->
                        <div class="flex items-center gap-2">
                            <template x-if="rfidCardScanned">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>RFID Active (+1 Pt on Commit)</span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Scanner Input & Controls -->
                    <div class="flex flex-col sm:flex-row items-stretch gap-2">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                x-model="rfidInput" 
                                @keydown.enter.prevent="scanRfidCard()"
                                placeholder="Tap client RFID card on reader or enter card UID..." 
                                class="w-full pl-9 pr-24 py-2.5 rounded-xl border text-xs font-mono font-bold transition focus:outline-none"
                                :class="rfidCardScanned ? 'border-purple-400 bg-white text-purple-950' : 'border-purple-200 bg-white text-slate-800 focus:border-purple-500'"
                            >
                            <div class="absolute left-3 top-3 text-purple-500">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </div>
                            <button 
                                type="button" 
                                @click="scanRfidCard()"
                                :disabled="isScanningRfid"
                                class="absolute right-1.5 top-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-purple-700 to-pink-600 hover:opacity-95 text-white text-[11px] font-bold transition cursor-pointer flex items-center gap-1"
                            >
                                <span x-show="isScanningRfid" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span x-text="isScanningRfid ? 'Verifying...' : 'Scan / Verify'"></span>
                            </button>
                        </div>

                        <template x-if="rfidCardScanned">
                            <button 
                                type="button" 
                                @click="clearRfidScan()"
                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-xs font-bold transition cursor-pointer shrink-0"
                                title="Remove scanned RFID card"
                            >
                                Clear Card
                            </button>
                        </template>
                    </div>

                    <!-- Feedback Message / Alert -->
                    <div x-show="rfidScanMessage" class="mt-2.5 text-xs font-semibold flex items-center gap-2"
                        :class="rfidScanSuccess ? 'text-emerald-700' : 'text-rose-600'">
                        <i :data-lucide="rfidScanSuccess ? 'check' : 'alert-circle'" class="w-4 h-4 shrink-0"></i>
                        <span x-text="rfidScanMessage"></span>
                    </div>

                    <!-- Active RFID Loyalty Perks Bar (Visible when card is scanned) -->
                    <template x-if="rfidCardScanned">
                        <div class="mt-3 pt-3 border-t border-purple-200/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-slate-600 font-medium">Cardholder:</span>
                                    <strong class="text-purple-950 font-bold" x-text="rfidCustomerName || selectedCustomer?.full_name"></strong>
                                </div>
                                <div class="flex items-center gap-1.5 pl-3 border-l border-purple-200">
                                    <span class="text-slate-600 font-medium">Current Points:</span>
                                    <span class="font-bold text-amber-700 font-mono" x-text="(rfidCurrentPoints || 0) + ' pts'"></span>
                                    <span class="text-[10px] text-emerald-800 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                        ➜ <span x-text="((rfidCurrentPoints || 0) + 1) + ' pts'"></span> after checkout (+1 pt)
                                    </span>
                                </div>
                            </div>

                            <!-- Discount Card Quick Perks -->
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-purple-900">Discount Perks:</span>
                                <button 
                                    type="button" 
                                    @click="applyLoyaltyDiscountPercent(10)" 
                                    class="px-2.5 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-pink-800 text-[11px] font-bold border border-pink-200 transition cursor-pointer"
                                    title="Apply 10% studio loyalty discount"
                                >
                                    Apply 10% Off
                                </button>
                                <button 
                                    type="button" 
                                    @click="applyLoyaltyDiscountFixed(50)" 
                                    class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 text-[11px] font-bold border border-amber-200 transition cursor-pointer"
                                    title="Apply ₱50 loyalty perk"
                                >
                                    Apply ₱50 Perk
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Payment Method</label>
                        <select 
                            x-model="paymentMethod" 
                            class="w-full px-3 py-2 rounded-xl bg-purple-50/40 border border-purple-200 text-xs font-bold text-purple-950 focus:outline-none focus:border-purple-500"
                        >
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <!-- Discount Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Discount Amount (₱)</label>
                        <input 
                            type="number" 
                            x-model.number="discountAmount" 
                            min="0" 
                            step="10"
                            class="w-full px-3 py-2 rounded-xl bg-purple-50/40 border border-purple-200 text-xs font-bold text-purple-950 focus:outline-none focus:border-purple-500"
                        >
                    </div>

                    <!-- Grand Total Display & Finalize Button -->
                    <div class="flex flex-col justify-end">
                        <button 
                            type="button" 
                            @click="finalizeTransaction()"
                            :disabled="isSubmitting"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-purple-950/30 flex items-center justify-center gap-2 transition cursor-pointer"
                        >
                            <i x-show="!isSubmitting" data-lucide="check-check" class="w-4 h-4"></i>
                            <span x-show="!isSubmitting" x-text="'Commit ₱' + getGrandTotal().toFixed(2) + ' & Generate Prints'"></span>
                            <span x-show="isSubmitting">Generating 4R JPEGs...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- INLINE ADD CLIENT MODAL -->
    <!-- ========================================================================= -->
    <div 
        x-show="showAddClientModal" 
        x-transition.opacity 
        @click.self="showAddClientModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-purple-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-purple-200 relative">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-purple-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                    <h3 class="text-sm font-black text-purple-950">Add New Studio Client</h3>
                </div>
                <button type="button" @click="showAddClientModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewClientInline()" class="space-y-3">
                <div x-show="clientModalGeneralError" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold" x-text="clientModalGeneralError"></div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">First Name *</label>
                        <input type="text" x-model="newClient.first_name" required class="w-full px-3 py-1.5 rounded-lg border text-xs focus:outline-none focus:ring-1" :class="clientModalErrors.first_name ? 'border-rose-400 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-400' : 'border-purple-200 focus:border-purple-500 focus:ring-purple-400'">
                        <p x-show="clientModalErrors.first_name" class="text-[10px] text-rose-600 mt-1 font-semibold" x-text="clientModalErrors.first_name"></p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Last Name *</label>
                        <input type="text" x-model="newClient.last_name" required class="w-full px-3 py-1.5 rounded-lg border text-xs focus:outline-none focus:ring-1" :class="clientModalErrors.last_name ? 'border-rose-400 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-400' : 'border-purple-200 focus:border-purple-500 focus:ring-purple-400'">
                        <p x-show="clientModalErrors.last_name" class="text-[10px] text-rose-600 mt-1 font-semibold" x-text="clientModalErrors.last_name"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Contact Number *</label>
                    <input type="text" x-model="newClient.contact_number" required placeholder="0917..." class="w-full px-3 py-1.5 rounded-lg border text-xs focus:outline-none focus:ring-1" :class="clientModalErrors.contact_number ? 'border-rose-400 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-400' : 'border-purple-200 focus:border-purple-500 focus:ring-purple-400'">
                    <p x-show="clientModalErrors.contact_number" class="text-[10px] text-rose-600 mt-1 font-semibold" x-text="clientModalErrors.contact_number"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Email Address</label>
                    <input type="email" x-model="newClient.email" class="w-full px-3 py-1.5 rounded-lg border text-xs focus:outline-none focus:ring-1" :class="clientModalErrors.email ? 'border-rose-400 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-400' : 'border-purple-200 focus:border-purple-500 focus:ring-purple-400'">
                    <p x-show="clientModalErrors.email" class="text-[10px] text-rose-600 mt-1 font-semibold" x-text="clientModalErrors.email"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                        <span>RFID Card UID (Loyalty & Discount Card)</span>
                        <span class="text-[10px] text-purple-600 font-normal">Optional</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="newClient.rfid" 
                            placeholder="Tap card on reader or enter card UID..." 
                            class="w-full pl-8 pr-3 py-1.5 rounded-lg border text-xs focus:outline-none focus:ring-1 font-mono"
                            :class="clientModalErrors.rfid ? 'border-rose-400 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-400' : 'border-purple-200 focus:border-purple-500 focus:ring-purple-400'"
                        >
                        <div class="absolute left-2.5 top-2 text-purple-400 pointer-events-none">
                            <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                        </div>
                    </div>
                    <p x-show="clientModalErrors.rfid" class="text-[10px] text-rose-600 mt-1 font-semibold" x-text="clientModalErrors.rfid"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Notes</label>
                    <input type="text" x-model="newClient.notes" placeholder="e.g. Birthday, graduation shoot" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-purple-100">
                    <button type="button" @click="showAddClientModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-semibold hover:text-slate-800">Cancel</button>
                    <button type="submit" :disabled="isSavingClient" class="px-4 py-2 bg-gradient-to-r from-purple-700 to-pink-600 hover:from-purple-600 hover:to-pink-500 text-white font-bold text-xs rounded-xl transition shadow-xs flex items-center gap-2">
                        <span x-show="isSavingClient" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>Save & Select Client</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FINALIZATION COMPLETION MODAL & POS RECEIPT DIALOG (Requirement 3.D Step 5) -->
    <!-- ========================================================================= -->
    <div 
        x-show="showSuccessModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-purple-950/80 backdrop-blur-sm"
        style="display: none;"
    >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-purple-200 text-center relative overflow-hidden">
            <!-- Authentic Studio Logo Branding in Modal -->
            <div class="inline-flex items-center justify-center p-2.5 rounded-2xl bg-white shadow-md border border-purple-100 mx-auto mb-3">
                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-10 w-auto object-contain">
            </div>

            <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center mx-auto mb-2 ring-8 ring-purple-50">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>

            <span class="text-xs font-bold text-pink-600 uppercase tracking-widest">Transaction Completed</span>
            <h2 class="text-xl font-black text-purple-950 mt-1 mb-1">Session Saved & 4R Prints Ready!</h2>
            <p class="text-xs text-purple-600/80 mb-4 font-mono font-semibold" x-text="'Tx Code: ' + (finalResult?.transaction?.transaction_code || '')"></p>

            <!-- RFID Loyalty Points Announcement Banner -->
            <template x-if="finalResult?.rfid_scanned">
                <div class="mb-4 p-3.5 rounded-2xl bg-gradient-to-r from-amber-50 to-pink-50 border border-amber-200 flex items-center justify-between text-xs shadow-xs">
                    <div class="flex items-center gap-2.5 text-left">
                        <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-pink-500 text-white flex items-center justify-center font-black text-sm shadow-xs shrink-0">
                            ⭐
                        </span>
                        <div>
                            <p class="font-black text-amber-950">+1 Loyalty Point Earned!</p>
                            <p class="text-[10px] text-amber-800 font-mono" x-text="'Card: ' + (finalResult.rfid_code || 'Verified')"></p>
                        </div>
                    </div>
                    <div class="text-right pl-3 border-l border-amber-200 shrink-0">
                        <span class="text-[10px] text-slate-500 font-medium block">Total Balance</span>
                        <p class="font-black text-sm text-purple-950" x-text="(finalResult.total_points || 0) + ' pts'"></p>
                    </div>
                </div>
            </template>

            <!-- Blockchain Immutable Anchor Stamp -->
            <template x-if="finalResult?.blockchain_block">
                <div class="mb-5 px-3.5 py-2.5 rounded-2xl bg-purple-50/80 border border-purple-200 text-left flex items-center justify-between text-xs shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <span class="font-bold text-purple-950 font-mono text-[11px]" x-text="'Block #' + String(finalResult.blockchain_block.block_index).padStart(5, '0')"></span>
                            <span class="text-[10px] text-slate-500 font-mono block truncate max-w-[220px]" x-text="finalResult.blockchain_block.short_hash"></span>
                        </div>
                    </div>
                    <span class="text-[9px] font-mono font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full border border-emerald-200 shrink-0">
                        Ledger Anchored
                    </span>
                </div>
            </template>

            <!-- Instant Download & Print Action Buttons -->
            <div class="space-y-3 mb-6">
                <!-- Download Dual Photostrip -->
                <a 
                    :href="finalResult?.print_photostrip_url" 
                    download
                    target="_blank"
                    class="w-full py-3 px-4 rounded-xl bg-[#1c053a] hover:bg-[#2c0859] text-white font-bold text-xs flex items-center justify-center gap-2 transition shadow-md border border-purple-900"
                >
                    <i data-lucide="download" class="w-4 h-4 text-yellow-300"></i>
                    <span>Download Output A: 4R Dual Photostrip JPEG</span>
                </a>

                <!-- Download Solo 4R Print -->
                <a 
                    :href="finalResult?.print_solo_url" 
                    download
                    target="_blank"
                    class="w-full py-3 px-4 rounded-xl bg-[#1c053a] hover:bg-[#2c0859] text-white font-bold text-xs flex items-center justify-center gap-2 transition shadow-md border border-purple-900"
                >
                    <i data-lucide="download" class="w-4 h-4 text-pink-400"></i>
                    <span>Download Output B: Solo Favorite 4R JPEG</span>
                </a>

                <!-- Open Thermal Receipt Trigger -->
                <button 
                    type="button" 
                    @click="openThermalReceiptPrint()"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black text-xs flex items-center justify-center gap-2 transition shadow-lg shadow-purple-950/30 uppercase tracking-wider cursor-pointer"
                >
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print Thermal POS Receipt (80mm)</span>
                </button>
            </div>

            <div class="pt-4 border-t border-purple-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-xs text-purple-700 hover:text-purple-950 font-bold">Return to Dashboard</a>
                <button 
                    type="button" 
                    @click="resetWizard()" 
                    class="text-xs font-black text-pink-600 hover:text-pink-700 cursor-pointer"
                >
                    Start Next Shoot Session &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Thermal POS Receipt Print Container -->
    <div id="thermal-receipt-area" style="display: none;">
        <template x-if="finalResult?.receipt_data">
            <div style="font-family: monospace; font-size: 12px; line-height: 1.4;">
                <div style="text-align: center; margin-bottom: 8px;">
                    <div style="font-size: 16px; font-weight: bold;" x-text="finalResult.receipt_data.studio_name"></div>
                    <div style="font-size: 10px;" x-text="finalResult.receipt_data.studio_tagline"></div>
                    <div style="font-size: 10px;" x-text="finalResult.receipt_data.studio_contact"></div>
                    <div style="font-size: 10px;" x-text="finalResult.receipt_data.studio_address"></div>
                </div>
                <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
                <div>Code: <strong x-text="finalResult.receipt_data.transaction_code"></strong></div>
                <div>Date: <span x-text="finalResult.receipt_data.date_time"></span></div>
                <div>Client: <span x-text="finalResult.receipt_data.customer_name"></span></div>
                <div>Staff: <span x-text="finalResult.receipt_data.operator"></span></div>
                <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
                <div style="display: flex; justify-content: space-between;">
                    <span x-text="finalResult.receipt_data.package_title"></span>
                    <strong x-text="finalResult.receipt_data.package_price"></strong>
                </div>
                <template x-for="item in finalResult.receipt_data.addons" :key="item.title">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; padding-left: 8px;">
                        <span x-text="item.title + ' x' + item.qty"></span>
                        <span x-text="item.subtotal"></span>
                    </div>
                </template>
                <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>
                <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: bold;">
                    <span>GRAND TOTAL</span>
                    <span x-text="finalResult.receipt_data.grand_total"></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 4px;">
                    <span>Payment Method:</span>
                    <strong x-text="finalResult.receipt_data.payment_method"></strong>
                </div>
                <template x-if="finalResult.receipt_data.rfid_scanned">
                    <div style="border-top: 1px dashed #000; margin-top: 6px; padding-top: 4px; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>RFID Card:</span>
                            <span style="font-weight: bold;" x-text="finalResult.receipt_data.rfid_used || 'Active'"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Points Earned:</span>
                            <strong style="font-weight: bold;">+1 pt</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Points Balance:</span>
                            <strong style="font-weight: bold;" x-text="finalResult.receipt_data.total_points + ' pts'"></strong>
                        </div>
                    </div>
                </template>
                <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
                <div style="text-align: center; font-size: 10px; margin-top: 8px;" x-text="finalResult.receipt_data.footer_text"></div>
            </div>
        </template>
    </div>

</div>

<!-- Alpine.js Workflow Controller Component -->
<script>
function workflowWizard(config) {
    return {
        currentStep: 1,
        maxLimit: config.maxLimit || 10,
        packages: config.initialPackages || [],
        addons: config.initialAddons || [],
        customers: config.initialCustomers || [],
        
        // Step 1 State
        clientQuery: '',
        filteredClients: config.initialCustomers || [],
        selectedCustomer: null,
        selectedPackageId: config.initialPackages.length > 0 ? Number(config.initialPackages[0].id) : null,
        selectedAddonsMap: {}, // addonId => qty
        showAddClientModal: false,
        openDropdown: false,
        isSavingClient: false,
        isSearchingClients: false,
        clientModalErrors: {},
        clientModalGeneralError: '',
        searchDebounceTimer: null,
        newClient: { first_name: '', last_name: '', rfid: '', contact_number: '', email: '', notes: '' },

        // RFID Loyalty & Discount Card State (1 point per scan)
        rfidInput: '',
        rfidCardScanned: false,
        rfidCustomerName: '',
        rfidCurrentPoints: 0,
        rfidScanMessage: '',
        rfidScanSuccess: false,
        isScanningRfid: false,

        // Step 2 & 3 State (Photos)
        photos: [],
        isUploading: false,
        uploadProgress: 0,

        // Step 4 & 5 State (Checkout & Finalization)
        paymentMethod: 'cash',
        discountAmount: 0,
        isSubmitting: false,
        showSuccessModal: false,
        finalResult: null,

        steps: [
            { title: 'Client & Package' },
            { title: 'File Ingestion' },
            { title: 'Interactive Selection' },
            { title: '4R Templates & POS' },
        ],

        init() {
            this.$watch('clientQuery', (val) => {
                if (!val) {
                    this.filteredClients = this.customers;
                }
            });
        },

        canNavigateTo(step) {
            if (step === 2 || step === 3) {
                return !!(this.selectedCustomer && this.selectedPackageId);
            }
            if (step === 4) {
                return this.starredCount > 0 && this.favoritePhoto !== null;
            }
            return true;
        },

        openAddClientModal(prefillName = '') {
            let fn = '';
            let ln = '';
            if (prefillName && typeof prefillName === 'string') {
                const parts = prefillName.trim().split(/\s+/);
                if (parts.length === 1) {
                    fn = parts[0];
                } else if (parts.length > 1) {
                    fn = parts[0];
                    ln = parts.slice(1).join(' ');
                }
            }
            this.newClient = { first_name: fn, last_name: ln, rfid: '', contact_number: '', email: '', notes: '' };
            this.clientModalErrors = {};
            this.clientModalGeneralError = '';
            this.isSavingClient = false;
            this.showAddClientModal = true;
            this.openDropdown = false;
            setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
        },

        changeSelectedCustomer() {
            this.selectedCustomer = null;
            this.clientQuery = '';
            this.filteredClients = this.customers;
            this.rfidInput = '';
            this.rfidCardScanned = false;
            this.rfidCustomerName = '';
            this.rfidCurrentPoints = 0;
            this.openDropdown = true;
            setTimeout(() => {
                const input = document.getElementById('client-search-input');
                if (input) input.focus();
            }, 50);
        },

        // Client search with local cache + async backend lookup
        searchClients() {
            const q = (this.clientQuery || '').trim().toLowerCase();
            if (!q) {
                this.filteredClients = this.customers;
                this.isSearchingClients = false;
                return;
            }

            // 1. Instant local filter
            const localMatches = this.customers.filter(c => {
                const fn = (c.first_name || '').toLowerCase();
                const ln = (c.last_name || '').toLowerCase();
                const full = (c.full_name || `${fn} ${ln}`).toLowerCase();
                const ph = (c.contact_number || '');
                const em = (c.email || '').toLowerCase();
                const rf = (c.rfid || '').toLowerCase();
                return fn.includes(q) || ln.includes(q) || full.includes(q) || ph.includes(q) || em.includes(q) || rf.includes(q);
            });

            this.filteredClients = localMatches;
            this.isSearchingClients = true;

            // 2. Async backend query to search all clients in database
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                fetch(`{{ route('workflow.api.clients') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(res => {
                    this.isSearchingClients = false;
                    if (res && res.success && Array.isArray(res.data)) {
                        const map = new Map();
                        // Put local matches first
                        this.filteredClients.forEach(c => map.set(c.id, c));
                        // Add database matches
                        res.data.forEach(c => map.set(c.id, c));
                        this.filteredClients = Array.from(map.values());
                    }
                })
                .catch(() => {
                    this.isSearchingClients = false;
                });
            }, 180);
        },

        selectCustomer(cust) {
            if (!cust) return;
            if (!cust.full_name) {
                cust.full_name = `${cust.first_name || ''} ${cust.last_name || ''}`.trim() || `Client #${cust.id}`;
            }
            this.selectedCustomer = cust;
            this.clientQuery = cust.full_name;
            this.openDropdown = false;

            // If selected customer already has an RFID registered, initialize card state
            if (cust.rfid) {
                this.rfidInput = cust.rfid;
                this.rfidCustomerName = cust.full_name;
                this.rfidCurrentPoints = Number(cust.points || 0);
            } else {
                this.rfidInput = '';
                this.rfidCustomerName = '';
                this.rfidCurrentPoints = Number(cust.points || 0);
            }
        },

        submitNewClientInline() {
            this.clientModalErrors = {};
            this.clientModalGeneralError = '';

            if (!this.newClient.first_name) {
                this.clientModalErrors.first_name = 'First name is required.';
            }
            if (!this.newClient.last_name) {
                this.clientModalErrors.last_name = 'Last name is required.';
            }
            if (!this.newClient.contact_number) {
                this.clientModalErrors.contact_number = 'Contact number is required.';
            }
            if (Object.keys(this.clientModalErrors).length > 0) {
                return;
            }

            this.isSavingClient = true;
            fetch('{{ route("workflow.api.clients.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(this.newClient)
            })
            .then(async (res) => {
                this.isSavingClient = false;
                const data = await res.json().catch(() => null);
                if (res.ok && data && data.success) {
                    const client = data.data;
                    if (!client.full_name) {
                        client.full_name = `${client.first_name || ''} ${client.last_name || ''}`.trim();
                    }
                    this.customers = [client, ...this.customers.filter(c => c.id !== client.id)];
                    this.filteredClients = [client, ...this.filteredClients.filter(c => c.id !== client.id)];
                    this.selectCustomer(client);
                    this.showAddClientModal = false;
                    this.newClient = { first_name: '', last_name: '', rfid: '', contact_number: '', email: '', notes: '' };
                    this.clientModalErrors = {};
                    this.clientModalGeneralError = '';
                } else {
                    if (data?.errors) {
                        this.clientModalErrors = data.errors;
                    } else {
                        this.clientModalGeneralError = data?.message || 'Failed to register client. Please check the fields.';
                    }
                }
            })
            .catch(err => {
                this.isSavingClient = false;
                this.clientModalGeneralError = 'Network error: ' + err.message;
            });
        },

        // Package helpers
        selectPackage(id) {
            this.selectedPackageId = Number(id);
        },
        isPackageSelected(id) {
            return Number(this.selectedPackageId) === Number(id);
        },
        getSelectedPackage() {
            return this.packages.find(p => Number(p.id) === Number(this.selectedPackageId)) || null;
        },
        getPackagePrice() {
            const pkg = this.getSelectedPackage();
            return pkg ? parseFloat(pkg.base_price) : 0;
        },

        // Addons helpers (Immutably updated for guaranteed Vue/Alpine Proxy reactivity)
        isAddonSelected(id) {
            const numId = Number(id);
            return typeof this.selectedAddonsMap[numId] === 'number' && this.selectedAddonsMap[numId] > 0;
        },
        toggleAddon(id) {
            const numId = Number(id);
            const copy = { ...this.selectedAddonsMap };
            if (copy[numId]) {
                delete copy[numId];
            } else {
                copy[numId] = 1;
            }
            this.selectedAddonsMap = copy;
        },
        getAddonQty(id) {
            const numId = Number(id);
            return this.selectedAddonsMap[numId] || 1;
        },
        increaseAddonQty(id) {
            const numId = Number(id);
            const copy = { ...this.selectedAddonsMap };
            copy[numId] = (copy[numId] || 1) + 1;
            this.selectedAddonsMap = copy;
        },
        decreaseAddonQty(id) {
            const numId = Number(id);
            const copy = { ...this.selectedAddonsMap };
            if (copy[numId] > 1) {
                copy[numId]--;
            } else {
                delete copy[numId];
            }
            this.selectedAddonsMap = copy;
        },
        getAddonsTotal() {
            let total = 0;
            for (const [addonId, qty] of Object.entries(this.selectedAddonsMap)) {
                const item = this.addons.find(a => Number(a.id) === Number(addonId));
                if (item) {
                    total += parseFloat(item.price) * qty;
                }
            }
            return total;
        },
        getGrandTotal() {
            const sum = (this.getPackagePrice() + this.getAddonsTotal()) - (parseFloat(this.discountAmount) || 0);
            return Math.max(0, sum);
        },

        proceedToStep2() {
            if (!this.selectedCustomer || !this.selectedPackageId) {
                alert('Please select a client and a package first.');
                return;
            }
            this.currentStep = 2;
            setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
        },

        // Upload handler
        handleFileUpload(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            this.isUploading = true;
            this.uploadProgress = 10;

            const total = files.length;
            let completed = 0;

            Array.from(files).forEach((file) => {
                const formData = new FormData();
                formData.append('photo', file);

                fetch('{{ route("workflow.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    completed++;
                    this.uploadProgress = Math.round((completed / total) * 100);

                    if (data.success) {
                        this.photos.push(data.data);

                        // If favorite is not yet set, set first uploaded as default favorite
                        if (!this.favoritePhoto) {
                            this.setFavorite(data.data);
                        }

                        // Auto-star first 10
                        if (this.starredCount < this.maxLimit) {
                            data.data.is_starred = true;
                        }
                    }
                    if (completed >= total) {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                    }
                })
                .catch(err => {
                    completed++;
                    if (completed >= total) {
                        this.isUploading = false;
                    }
                    console.error('Upload error:', err);
                });
            });
        },

        // Photo Selection Logic (Requirement 3.D Step 3)
        get starredCount() {
            return this.photos.filter(p => p.is_starred).length;
        },
        get favoritePhoto() {
            return this.photos.find(p => p.is_favorite) || null;
        },

        toggleStar(photo) {
            photo.is_starred = !photo.is_starred;
        },

        setFavorite(photo) {
            // Radio-style exclusive selector
            this.photos.forEach(p => p.is_favorite = false);
            photo.is_favorite = true;
            // Also ensure it is starred into the pool
            photo.is_starred = true;
        },

        selectFirstTen() {
            this.photos.forEach((p, idx) => {
                p.is_starred = idx < this.maxLimit;
            });
            if (!this.favoritePhoto && this.photos.length > 0) {
                this.setFavorite(this.photos[0]);
            }
        },

        clearAllStarred() {
            this.photos.forEach(p => p.is_starred = false);
        },

        removePhoto(photo) {
            const idx = this.photos.findIndex(p => p.id === photo.id);
            if (idx !== -1) {
                const removed = this.photos.splice(idx, 1)[0];
                
                // If the removed photo was favorite, select next starred or first available photo
                if (removed.is_favorite) {
                    const nextFav = this.photos.find(p => p.is_starred) || (this.photos.length > 0 ? this.photos[0] : null);
                    if (nextFav) {
                        this.setFavorite(nextFav);
                    }
                }

                // Delete temp files from backend storage asynchronously
                fetch('{{ route("workflow.delete.photo") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        storage_path: removed.storage_path,
                        preview_path: removed.preview_path
                    })
                }).catch(e => console.error('Error removing file from disk:', e));

                setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
            }
        },

        clearAllPhotos() {
            if (this.photos.length === 0) return;
            if (!confirm(`Are you sure you want to clear all ${this.photos.length} ingested photos?`)) {
                return;
            }

            const photosToDelete = [...this.photos];
            this.photos = [];

            // Delete temp files from backend storage asynchronously
            fetch('{{ route("workflow.clear.photos") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ photos: photosToDelete })
            }).catch(e => console.error('Error clearing files:', e));

            setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
        },

        // Photostrip Column & Slot Slicers (Requirement: First 5 photos on Left Part, Second 5 on Right Part)
        getStarredPhotos() {
            const starred = this.photos.filter(p => p.is_starred);
            if (starred.length === 0) {
                return Array(10).fill(null);
            }
            // Pad up to 10 if needed
            let list = [...starred];
            while (list.length < 10) {
                list = list.concat(starred);
            }
            return list.slice(0, 10);
        },
        getStripCol1() {
            return this.getStarredPhotos().slice(0, 5);
        },
        getStripCol2() {
            return this.getStarredPhotos().slice(5, 10);
        },

        // Real-Time Ingest Slots for Dual Photostrips
        getLeftStripSlots() {
            const starred = this.photos.filter(p => p.is_starred);
            const slots = [];
            for (let i = 0; i < 5; i++) {
                slots.push({
                    slotNumber: i + 1,
                    photo: starred[i] || null
                });
            }
            return slots;
        },
        getRightStripSlots() {
            const starred = this.photos.filter(p => p.is_starred);
            const slots = [];
            for (let i = 5; i < 10; i++) {
                slots.push({
                    slotNumber: i + 1,
                    photo: starred[i] || null
                });
            }
            return slots;
        },

        // Slot and Strip placement badge info for any photo card
        getPhotoSlotInfo(photo) {
            if (!photo.is_starred) return null;
            const starred = this.photos.filter(p => p.is_starred);
            const idx = starred.findIndex(p => p.id === photo.id);
            if (idx === -1) return null;

            if (idx < 5) {
                return {
                    strip: 'left',
                    slot: idx + 1,
                    label: `Left #${idx + 1}`,
                    badgeClass: 'bg-sky-600 text-white'
                };
            } else if (idx < 10) {
                return {
                    strip: 'right',
                    slot: idx + 1,
                    label: `Right #${idx + 1}`,
                    badgeClass: 'bg-amber-500 text-slate-950 font-black'
                };
            } else {
                return {
                    strip: 'extra',
                    slot: idx + 1,
                    label: `Queue #${idx + 1}`,
                    badgeClass: 'bg-rose-500 text-white'
                };
            }
        },

        // Move photo earlier or later among starred slots
        moveSlotEarlier(photo) {
            const starred = this.photos.filter(p => p.is_starred);
            const idx = starred.findIndex(p => p.id === photo.id);
            if (idx > 0) {
                const prevPhoto = starred[idx - 1];
                const p1Idx = this.photos.findIndex(p => p.id === photo.id);
                const p2Idx = this.photos.findIndex(p => p.id === prevPhoto.id);
                const temp = this.photos[p1Idx];
                this.photos[p1Idx] = this.photos[p2Idx];
                this.photos[p2Idx] = temp;
                setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
            }
        },
        moveSlotLater(photo) {
            const starred = this.photos.filter(p => p.is_starred);
            const idx = starred.findIndex(p => p.id === photo.id);
            if (idx >= 0 && idx < starred.length - 1) {
                const nextPhoto = starred[idx + 1];
                const p1Idx = this.photos.findIndex(p => p.id === photo.id);
                const p2Idx = this.photos.findIndex(p => p.id === nextPhoto.id);
                const temp = this.photos[p1Idx];
                this.photos[p1Idx] = this.photos[p2Idx];
                this.photos[p2Idx] = temp;
                setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
            }
        },

        proceedToStep4() {
            if (this.starredCount === 0) {
                alert('Please star (★) at least one photo for the photostrip.');
                return;
            }
            if (!this.favoritePhoto) {
                alert('Please select a favorite photo (♥) for the solo 4R print.');
                return;
            }
            this.currentStep = 4;
            setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
        },

        // RFID Loyalty & Discount Card Methods
        scanRfidCard() {
            const code = this.rfidInput ? this.rfidInput.trim() : '';
            if (!code) {
                this.rfidScanSuccess = false;
                this.rfidScanMessage = 'Please swipe or scan a valid RFID card UID.';
                return;
            }

            this.isScanningRfid = true;
            this.rfidScanMessage = '';

            fetch('{{ route("workflow.api.rfid.scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    rfid: code,
                    customer_id: this.selectedCustomer?.id || null
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isScanningRfid = false;
                if (data.success) {
                    this.rfidCardScanned = true;
                    this.rfidScanSuccess = true;
                    this.rfidScanMessage = data.message || 'RFID card verified! +1 loyalty point will be credited on checkout.';
                    this.rfidCustomerName = data.data?.full_name || this.selectedCustomer?.full_name;
                    this.rfidCurrentPoints = Number(data.data?.points || 0);
                    this.rfidInput = data.data?.rfid || code;

                    // If a customer was found by RFID and no customer was active, select them
                    if (!this.selectedCustomer && data.data?.id) {
                        this.selectCustomer(data.data);
                    }
                    setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                } else {
                    this.rfidScanSuccess = false;
                    this.rfidScanMessage = data.message || 'RFID card not found.';
                }
            })
            .catch(err => {
                this.isScanningRfid = false;
                this.rfidScanSuccess = false;
                this.rfidScanMessage = 'Network error verifying RFID card: ' + err.message;
            });
        },

        clearRfidScan() {
            this.rfidCardScanned = false;
            this.rfidInput = '';
            this.rfidScanMessage = '';
            this.rfidScanSuccess = false;
            setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
        },

        applyLoyaltyDiscountPercent(percent) {
            const subtotal = this.getPackagePrice() + this.getAddonsTotal();
            this.discountAmount = Math.round(subtotal * (percent / 100));
        },

        applyLoyaltyDiscountFixed(amount) {
            this.discountAmount = Number(amount);
        },

        // Step 5: Finalize & Generate Templates
        finalizeTransaction() {
            this.isSubmitting = true;

            // Ensure photos sent to server place the starred photos in strict strip order:
            // Left Strip (0..4), Right Strip (5..9), extra starred, followed by unstarred.
            const starred = this.photos.filter(p => p.is_starred);
            const unstarred = this.photos.filter(p => !p.is_starred);
            const sortedPhotos = [...starred, ...unstarred];

            const payload = {
                customer_id: this.selectedCustomer.id,
                package_id: this.selectedPackageId,
                addons: Object.entries(this.selectedAddonsMap).map(([id, qty]) => ({ id: parseInt(id), qty: parseInt(qty) })),
                discount_amount: parseFloat(this.discountAmount) || 0,
                payment_method: this.paymentMethod,
                payment_status: 'completed',
                rfid: this.rfidInput ? this.rfidInput.trim() : (this.selectedCustomer?.rfid || null),
                rfid_scanned: this.rfidCardScanned || false,
                photos: sortedPhotos.map(p => ({
                    original_filename: p.original_filename,
                    storage_path: p.storage_path,
                    preview_path: p.preview_path,
                    is_starred: !!p.is_starred,
                    is_favorite: !!p.is_favorite
                }))
            };

            fetch('{{ route("workflow.finalize") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSubmitting = false;
                if (data.success) {
                    this.finalResult = data.data;
                    if (this.selectedCustomer && data.data?.total_points !== undefined) {
                        this.selectedCustomer.points = data.data.total_points;
                    }
                    this.showSuccessModal = true;
                    setTimeout(() => window.initLucideIcons && window.initLucideIcons(), 50);
                } else {
                    alert('Finalization failed: ' + (data.message || 'Unknown error.'));
                }
            })
            .catch(err => {
                this.isSubmitting = false;
                alert('Server error: ' + err);
            });
        },

        openThermalReceiptPrint() {
            const printContent = document.getElementById('thermal-receipt-area');
            if (!printContent) return;
            
            const printWindow = window.open('', '_blank', 'width=380,height=600');
            printWindow.document.write('<html><head><title>Thermal POS Receipt</title><style>body { font-family: monospace; padding: 10px; margin: 0; width: 75mm; font-size: 11px; }</style></head><body>');
            printWindow.document.write(printContent.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        },

        resetWizard() {
            this.currentStep = 1;
            this.selectedCustomer = null;
            this.clientQuery = '';
            this.photos = [];
            this.selectedAddonsMap = {};
            this.discountAmount = 0;
            this.rfidInput = '';
            this.rfidCardScanned = false;
            this.rfidCustomerName = '';
            this.rfidCurrentPoints = 0;
            this.rfidScanMessage = '';
            this.rfidScanSuccess = false;
            this.showSuccessModal = false;
            this.finalResult = null;
        }
    };
}
</script>
@endsection

