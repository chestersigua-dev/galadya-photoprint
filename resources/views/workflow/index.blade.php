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
                                'bg-amber-500 text-slate-950 ring-4 ring-amber-500/20 font-bold': currentStep === (index + 1),
                                'bg-emerald-500 text-white font-semibold': currentStep > (index + 1),
                                'bg-slate-100 text-slate-400 font-medium': currentStep < (index + 1)
                            }"
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-xs transition transform active:scale-95"
                            :title="stepItem.title"
                        >
                            <span x-show="currentStep <= (index + 1)" x-text="index + 1"></span>
                            <i x-show="currentStep > (index + 1)" data-lucide="check" class="w-4 h-4"></i>
                        </button>
                        <span 
                            class="hidden lg:inline ml-2 text-xs font-semibold"
                            :class="currentStep === (index + 1) ? 'text-slate-900' : 'text-slate-400'"
                            x-text="stepItem.title"
                        ></span>
                        <div x-show="index < steps.length - 1" class="w-4 sm:w-8 h-0.5 bg-slate-200 mx-2"></div>
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
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-amber-500"></i>
                                <span>1. Select or Register Client</span>
                            </h2>
                            <p class="text-xs text-slate-500">Pick an existing client or register a new one on the fly.</p>
                        </div>

                        <!-- Add Client Modal Trigger -->
                        <button 
                            type="button" 
                            @click="showAddClientModal = true" 
                            class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold px-3 py-1.5 rounded-xl transition"
                        >
                            <i data-lucide="user-plus" class="w-4 h-4 text-amber-600"></i>
                            <span>+ Add New Client</span>
                        </button>
                    </div>

                    <!-- Client Search Input & Dropdown -->
                    <div class="relative" x-data="{ openDropdown: false }">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Search Client (Name, Phone, Email)</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="clientQuery"
                                @focus="openDropdown = true"
                                @input="searchClients()"
                                placeholder="Type client name e.g. Bea Alonzo or 0917..."
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-amber-500 focus:bg-white transition"
                            >
                            <div class="absolute right-3 top-3 text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Dropdown Results -->
                        <div 
                            x-show="openDropdown && filteredClients.length > 0" 
                            @click.outside="openDropdown = false"
                            class="absolute z-20 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl divide-y divide-slate-100"
                        >
                            <template x-for="c in filteredClients" :key="c.id">
                                <div 
                                    @click="selectCustomer(c); openDropdown = false"
                                    class="p-3 hover:bg-amber-50/60 cursor-pointer transition flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-xs font-bold text-slate-900" x-text="c.full_name"></p>
                                        <p class="text-[11px] text-slate-500" x-text="c.contact_number + (c.email ? ' * ' + c.email : '')"></p>
                                    </div>
                                    <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium" x-text="(c.total_sessions_count || 0) + ' sessions'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Active Selected Customer Pill -->
                    <div x-show="selectedCustomer" class="mt-4 p-4 rounded-xl bg-amber-50/80 border border-amber-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-500 text-slate-950 font-bold flex items-center justify-center text-sm shadow-sm">
                                <span x-text="selectedCustomer ? selectedCustomer.full_name.charAt(0) : '?'"></span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900" x-text="selectedCustomer?.full_name"></p>
                                <p class="text-xs text-slate-600" x-text="selectedCustomer?.contact_number + (selectedCustomer?.email ? ' * ' + selectedCustomer?.email : '')"></p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="selectedCustomer = null; clientQuery = ''" 
                            class="text-xs text-rose-600 hover:text-rose-800 font-semibold px-2 py-1"
                        >
                            Change
                        </button>
                    </div>
                </div>

                <!-- B. Package Selection Radio Grid -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4 text-amber-500"></i>
                        <span>2. Choose Self Shoot Package</span>
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Select the studio duration and pax tier for this shoot.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="pkg in packages" :key="pkg.id">
                            <label 
                                class="p-4 rounded-2xl border-2 cursor-pointer transition relative flex flex-col justify-between"
                                :class="selectedPackageId === pkg.id ? 'border-amber-500 bg-amber-50/40 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            >
                                <input 
                                    type="radio" 
                                    name="package" 
                                    :value="pkg.id" 
                                    x-model="selectedPackageId"
                                    class="sr-only"
                                >
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-sm font-bold text-slate-900" x-text="pkg.title"></h3>
                                        <span class="text-sm font-black text-amber-600" x-text="'₱' + parseFloat(pkg.base_price).toFixed(2)"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-2" x-text="pkg.description"></p>
                                </div>
                                <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                                    <span>Includes Dual Photostrip + Solo 4R</span>
                                    <i x-show="selectedPackageId === pkg.id" data-lucide="check-circle-2" class="w-4 h-4 text-amber-600"></i>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- C. Real-Time Add-ons Checkboxes & Counters -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-amber-500"></i>
                        <span>3. Optional Session Add-ons</span>
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Add extra shooting time, photographer assistance, pax, or pets.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="addon in addons" :key="addon.id">
                            <div 
                                class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between transition"
                                :class="isAddonSelected(addon.id) ? 'bg-amber-50/50 border-amber-300' : 'bg-slate-50/50'"
                            >
                                <label class="flex items-center gap-3 cursor-pointer flex-1">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAddonSelected(addon.id)"
                                        @change="toggleAddon(addon.id)"
                                        class="w-4 h-4 rounded text-amber-500 border-slate-300 focus:ring-amber-400"
                                    >
                                    <div>
                                        <p class="text-xs font-bold text-slate-900" x-text="addon.title"></p>
                                        <p class="text-[11px] font-semibold text-amber-600" x-text="'₱' + parseFloat(addon.price).toFixed(2)"></p>
                                    </div>
                                </label>

                                <!-- Qty Counter -->
                                <div x-show="isAddonSelected(addon.id)" class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-0.5">
                                    <button 
                                        type="button" 
                                        @click="decreaseAddonQty(addon.id)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xs font-bold"
                                    >-</button>
                                    <span class="w-6 text-center text-xs font-bold text-slate-900" x-text="getAddonQty(addon.id)"></span>
                                    <button 
                                        type="button" 
                                        @click="increaseAddonQty(addon.id)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xs font-bold"
                                    >+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Right Col: Live Summary & Navigation -->
            <div class="space-y-6">
                <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-xl sticky top-24 border border-slate-800">
                    <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">Running Session Total</span>
                    <h3 class="text-lg font-bold text-white mt-1 mb-4">Calculation Summary</h3>

                    <div class="space-y-3 text-xs border-b border-slate-800 pb-4">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Selected Client:</span>
                            <span class="font-semibold text-white" x-text="selectedCustomer ? selectedCustomer.full_name : 'None selected'"></span>
                        </div>

                        <div class="flex items-center justify-between text-slate-300">
                            <span>Base Package:</span>
                            <span class="font-semibold text-white" x-text="getSelectedPackage() ? getSelectedPackage().title : 'None'"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400 pl-2">
                            <span>Package Price:</span>
                            <span class="font-mono" x-text="'₱' + getPackagePrice().toFixed(2)"></span>
                        </div>

                        <div class="flex items-center justify-between text-slate-300">
                            <span>Add-ons Total:</span>
                            <span class="font-mono text-amber-400 font-semibold" x-text="'₱' + getAddonsTotal().toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Running Total</span>
                        <span class="text-2xl font-black text-amber-400" x-text="'₱' + getGrandTotal().toFixed(2)"></span>
                    </div>

                    <button 
                        type="button" 
                        @click="proceedToStep2()"
                        :disabled="!selectedCustomer || !selectedPackageId"
                        :class="(!selectedCustomer || !selectedPackageId) ? 'opacity-50 cursor-not-allowed bg-slate-700 text-slate-400' : 'bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 shadow-lg shadow-amber-500/20'"
                        class="w-full mt-6 py-3 px-4 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                        <span>Proceed to Image Ingestion</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                    
                    <p x-show="!selectedCustomer || !selectedPackageId" class="text-[11px] text-amber-400 text-center mt-2">
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
        <div class="sticky top-16 z-30 bg-slate-900/95 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-xl border border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Selected Count Badge with Color Switch Rule -->
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-medium">Photostrip Pool:</span>
                    <span 
                        class="px-3 py-1 rounded-xl text-xs font-black tracking-wider transition-all"
                        :class="{
                            'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40': starredCount <= maxLimit,
                            'bg-rose-600 text-white ring-4 ring-rose-500/30 animate-pulse font-black': starredCount > maxLimit
                        }"
                    >
                        <span x-text="'Selected: ' + starredCount + ' / ' + maxLimit"></span>
                    </span>
                </div>

                <!-- Favorite Pick Indicator -->
                <div class="flex items-center gap-2 border-l border-slate-700 pl-4">
                    <span class="text-xs text-slate-400 font-medium">Solo 4R Favorite:</span>
                    <template x-if="favoritePhoto">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-400 bg-rose-500/20 px-2.5 py-0.5 rounded-lg border border-rose-500/40 truncate max-w-[160px]">
                            <i data-lucide="heart" class="w-3.5 h-3.5 fill-rose-500 text-rose-500"></i>
                            <span x-text="favoritePhoto.original_filename"></span>
                        </span>
                    </template>
                    <template x-if="!favoritePhoto">
                        <span class="text-xs text-amber-400 font-medium italic">Click ♥ on any photo</span>
                    </template>
                </div>
            </div>

            <!-- Quick Action Triggers -->
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="selectFirstTen()"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition"
                    title="Quick select the first 10 photos"
                >
                    Select First 10
                </button>
                <button 
                    type="button" 
                    @click="clearAllStarred()"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition"
                >
                    Clear Starred
                </button>
                <button 
                    type="button" 
                    @click="proceedToStep4()"
                    :disabled="starredCount === 0 || !favoritePhoto"
                    :class="(starredCount === 0 || !favoritePhoto) ? 'opacity-50 cursor-not-allowed bg-slate-700 text-slate-400' : 'bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 text-slate-950 font-bold shadow-md shadow-amber-500/20'"
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
        <div class="bg-white p-6 rounded-2xl border-2 border-dashed border-slate-300 hover:border-amber-500 transition relative overflow-hidden group">
            <input 
                type="file" 
                multiple
                accept=".jpg,.jpeg,.png,.cr2,.cr3,.nef,.arw,.dng" 
                @change="handleFileUpload($event)"
                class="absolute inset-0 opacity-0 cursor-pointer z-10"
            >
            <div class="flex flex-col items-center justify-center text-center py-6">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Drag & Drop Camera RAW or Standard Images</h3>
                <p class="text-xs text-slate-500 max-w-md mt-1">
                    Supports <span class="font-bold text-slate-700">Canon (.CR2, .CR3), Nikon (.NEF), Sony (.ARW), Adobe (.DNG), .JPG, .PNG</span>. Auto-extracts embedded high-res JPEGs with zero lag.
                </p>

                <!-- Upload Progress Indicator -->
                <div x-show="isUploading" class="w-full max-w-xs mt-4">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-amber-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-600 mt-1 block" x-text="'Processing ' + uploadProgress + '%...'"></span>
                </div>
            </div>
        </div>

        <!-- Ingested Photos Grid Presentation -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="image" class="w-4 h-4 text-amber-500"></i>
                        <span>Ingested Photos (<span x-text="photos.length"></span>)</span>
                    </h3>
                    <p class="text-xs text-slate-500">Click ★ to add to 10-photo strip pool. Click ♥ for the single favorite 4R print.</p>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-400 inline-block"></span> Starred (Strip)</span>
                    <span class="inline-flex items-center gap-1 ml-3"><span class="w-3 h-3 rounded bg-rose-500 inline-block"></span> Favorite (Solo 4R)</span>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="photos.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <i data-lucide="camera-off" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                <p class="text-sm font-semibold">No photos ingested yet.</p>
                <p class="text-xs">Drag and drop photos from the camera SD card above to begin interactive selection.</p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <template x-for="(photo, index) in photos" :key="photo.id">
                    <div 
                        class="bg-white rounded-2xl border-2 overflow-hidden shadow-xs relative group transition-all"
                        :class="{
                            'border-amber-400 ring-2 ring-amber-400/30': photo.is_starred && !photo.is_favorite,
                            'border-rose-500 ring-4 ring-rose-500/40': photo.is_favorite,
                            'border-slate-200 hover:border-slate-300': !photo.is_starred && !photo.is_favorite
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

                            <!-- Star Flag Selection Badge (if starred) -->
                            <div x-show="photo.is_starred" class="absolute top-2 right-2">
                                <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center shadow-md">
                                    ★
                                </span>
                            </div>

                            <!-- Favorite Glowing Heart Badge (if favorite) -->
                            <div x-show="photo.is_favorite" class="absolute bottom-2 left-2">
                                <span class="px-2 py-0.5 rounded-full bg-rose-600 text-white font-bold text-[10px] flex items-center gap-1 shadow-md">
                                    <i data-lucide="heart" class="w-3 h-3 fill-white text-white"></i>
                                    <span>Solo 4R</span>
                                </span>
                            </div>
                        </div>

                        <!-- Interactive Control Bar on Each Card -->
                        <div class="p-2.5 bg-white border-t border-slate-100 flex items-center justify-between">
                            <div class="truncate max-w-[110px]" :title="photo.original_filename">
                                <p class="text-[11px] font-bold text-slate-800 truncate" x-text="photo.original_filename"></p>
                                <p class="text-[9px] text-slate-400 font-mono" x-text="photo.width + 'x' + photo.height"></p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <!-- Star Toggle Button (★) -->
                                <button 
                                    type="button" 
                                    @click="toggleStar(photo)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition"
                                    :class="photo.is_starred ? 'bg-amber-100 text-amber-700 font-bold' : 'bg-slate-100 text-slate-400 hover:text-amber-600 hover:bg-amber-50'"
                                    :title="photo.is_starred ? 'Remove from photostrip selection' : 'Add to 10-photo strip pool'"
                                >
                                    ★
                                </button>

                                <!-- Favorite Selector Button (♥) -->
                                <button 
                                    type="button" 
                                    @click="setFavorite(photo)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition"
                                    :class="photo.is_favorite ? 'bg-rose-100 text-rose-600 font-bold' : 'bg-slate-100 text-slate-400 hover:text-rose-600 hover:bg-rose-50'"
                                    title="Set as single favorite for standalone 4R print"
                                >
                                    ♥
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
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4 text-amber-500"></i>
                        <span>Step 4: Automated 4R Print Template Verification</span>
                    </h2>
                    <p class="text-xs text-slate-500">Verify the high-resolution 4R dual-strip and solo favorite layouts before final POS commit.</p>
                </div>

                <button 
                    type="button" 
                    @click="currentStep = 3"
                    class="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 px-3 py-1.5 rounded-xl font-medium"
                >
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Adjust Photo Selections</span>
                </button>
            </div>

            <!-- Previews Comparison Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Output A: 4R Photostrip Composite Preview -->
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-800">Output A: 4R Dual Photostrip (10 Photos)</span>
                        <span class="text-[10px] font-mono bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">1200 x 1800 px</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-3">Two photostrip columns side-by-side (5 photos per column) with centered Galadya Studio footer logo.</p>

                    <!-- Mock Photostrip Canvas (CSS representation of PrintLayoutService layout) -->
                    <div class="bg-white border-2 border-slate-300 rounded-xl p-2.5 shadow-md aspect-[2/3] max-w-sm mx-auto flex gap-2">
                        <!-- Strip 1 -->
                        <div class="flex-1 flex flex-col justify-between border-r border-dashed border-slate-200 pr-1">
                            <div class="space-y-1.5">
                                <template x-for="(p, i) in getStripCol1()" :key="i">
                                    <div class="aspect-[16/9] bg-slate-200 rounded overflow-hidden">
                                        <img :src="p?.preview_url" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </div>
                            <!-- Centered Footer Branding -->
                            <div class="text-center py-2">
                                <span class="text-[10px] font-black tracking-widest text-slate-900 block">GALADYA</span>
                                <span class="text-[8px] text-amber-600 font-bold block -mt-1">STUDIO</span>
                            </div>
                        </div>

                        <!-- Strip 2 -->
                        <div class="flex-1 flex flex-col justify-between pl-1">
                            <div class="space-y-1.5">
                                <template x-for="(p, i) in getStripCol2()" :key="i">
                                    <div class="aspect-[16/9] bg-slate-200 rounded overflow-hidden">
                                        <img :src="p?.preview_url" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </div>
                            <!-- Centered Footer Branding -->
                            <div class="text-center py-2">
                                <span class="text-[10px] font-black tracking-widest text-slate-900 block">GALADYA</span>
                                <span class="text-[8px] text-amber-600 font-bold block -mt-1">STUDIO</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Output B: Solo Favorite 4R Print Preview -->
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-800">Output B: Solo Favorite 4R Print</span>
                        <span class="text-[10px] font-mono bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">1200 x 1800 px</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-3">Selected favorite scaled and centered with Galadya Studio logo placed at bottom-left corner (3% canvas area).</p>

                    <!-- Mock Solo Canvas -->
                    <div class="bg-white border-2 border-slate-300 rounded-xl p-2.5 shadow-md aspect-[2/3] max-w-sm mx-auto relative flex items-center justify-center overflow-hidden">
                        <template x-if="favoritePhoto">
                            <img :src="favoritePhoto.preview_url" class="w-full h-full object-contain">
                        </template>

                        <!-- Bottom-left logo overlay @ 3% canvas area -->
                        <div class="absolute bottom-4 left-4 bg-white/80 backdrop-blur-xs px-2.5 py-1 rounded-md shadow-xs border border-white/50">
                            <span class="text-[10px] font-black tracking-widest text-slate-900 block">GALADYA</span>
                            <span class="text-[7px] text-amber-600 font-bold block -mt-1">STUDIO PHOTOPRINT</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Payment Finalization Section -->
            <div class="mt-8 pt-6 border-t border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-amber-500"></i>
                    <span>Step 5: Finalize Payment & Commit Transaction</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Method</label>
                        <select 
                            x-model="paymentMethod" 
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold focus:outline-none focus:border-amber-500"
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
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Amount (₱)</label>
                        <input 
                            type="number" 
                            x-model.number="discountAmount" 
                            min="0" 
                            step="10"
                            class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold focus:outline-none focus:border-amber-500"
                        >
                    </div>

                    <!-- Grand Total Display & Finalize Button -->
                    <div class="flex flex-col justify-end">
                        <button 
                            type="button" 
                            @click="finalizeTransaction()"
                            :disabled="isSubmitting"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 transition cursor-pointer"
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="showAddClientModal = false">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Add New Studio Client</h3>
                <button @click="showAddClientModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewClientInline()" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">First Name *</label>
                        <input type="text" x-model="newClient.first_name" required class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Last Name *</label>
                        <input type="text" x-model="newClient.last_name" required class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Contact Number *</label>
                    <input type="text" x-model="newClient.contact_number" required placeholder="0917..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Email Address</label>
                    <input type="email" x-model="newClient.email" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Notes</label>
                    <input type="text" x-model="newClient.notes" placeholder="e.g. Birthday, graduation shoot" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="showAddClientModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl transition">Save & Select</button>
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        style="display: none;"
    >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4 ring-8 ring-emerald-50">
                <i data-lucide="check-circle" class="w-8 h-8"></i>
            </div>

            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Transaction Completed</span>
            <h2 class="text-xl font-bold text-slate-900 mt-1 mb-1">Session Saved & 4R Prints Ready!</h2>
            <p class="text-xs text-slate-500 mb-6" x-text="'Tx Code: ' + (finalResult?.transaction?.transaction_code || '')"></p>

            <!-- Instant Download & Print Action Buttons -->
            <div class="space-y-3 mb-6">
                <!-- Download Dual Photostrip -->
                <a 
                    :href="finalResult?.print_photostrip_url" 
                    download
                    target="_blank"
                    class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 transition shadow-sm"
                >
                    <i data-lucide="download" class="w-4 h-4 text-amber-400"></i>
                    <span>Download Output A: 4R Dual Photostrip JPEG</span>
                </a>

                <!-- Download Solo 4R Print -->
                <a 
                    :href="finalResult?.print_solo_url" 
                    download
                    target="_blank"
                    class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 transition shadow-sm"
                >
                    <i data-lucide="download" class="w-4 h-4 text-rose-400"></i>
                    <span>Download Output B: Solo Favorite 4R JPEG</span>
                </a>

                <!-- Open Thermal Receipt Trigger -->
                <button 
                    type="button" 
                    @click="openThermalReceiptPrint()"
                    class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 transition shadow-md shadow-amber-500/20"
                >
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print Thermal POS Receipt (80mm)</span>
                </button>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">Return to Dashboard</a>
                <button 
                    type="button" 
                    @click="resetWizard()" 
                    class="text-xs font-bold text-amber-600 hover:text-amber-700"
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
        selectedPackageId: config.initialPackages.length > 0 ? config.initialPackages[0].id : null,
        selectedAddonsMap: {}, // addonId => qty
        showAddClientModal: false,
        newClient: { first_name: '', last_name: '', contact_number: '', email: '', notes: '' },

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
                return this.selectedCustomer && this.selectedPackageId;
            }
            if (step === 4) {
                return this.starredCount > 0 && this.favoritePhoto !== null;
            }
            return true;
        },

        // Client search
        searchClients() {
            const q = this.clientQuery.toLowerCase();
            if (!q) {
                this.filteredClients = this.customers;
                return;
            }
            this.filteredClients = this.customers.filter(c => 
                c.first_name.toLowerCase().includes(q) || 
                c.last_name.toLowerCase().includes(q) || 
                (c.contact_number && c.contact_number.includes(q)) ||
                (c.email && c.email.toLowerCase().includes(q))
            );
        },

        selectCustomer(cust) {
            this.selectedCustomer = cust;
            this.clientQuery = cust.full_name;
        },

        submitNewClientInline() {
            fetch('{{ route("workflow.api.clients.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.newClient)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.customers.unshift(data.data);
                    this.selectCustomer(data.data);
                    this.showAddClientModal = false;
                    this.newClient = { first_name: '', last_name: '', contact_number: '', email: '', notes: '' };
                } else {
                    alert('Error saving client: ' + (data.message || 'Validation failed.'));
                }
            })
            .catch(err => alert('Network error: ' + err));
        },

        // Package helpers
        getSelectedPackage() {
            return this.packages.find(p => p.id === this.selectedPackageId);
        },
        getPackagePrice() {
            const pkg = this.getSelectedPackage();
            return pkg ? parseFloat(pkg.base_price) : 0;
        },

        // Addons helpers
        isAddonSelected(id) {
            return !!this.selectedAddonsMap[id];
        },
        toggleAddon(id) {
            if (this.selectedAddonsMap[id]) {
                delete this.selectedAddonsMap[id];
            } else {
                this.selectedAddonsMap[id] = 1;
            }
        },
        getAddonQty(id) {
            return this.selectedAddonsMap[id] || 1;
        },
        increaseAddonQty(id) {
            this.selectedAddonsMap[id] = (this.selectedAddonsMap[id] || 1) + 1;
        },
        decreaseAddonQty(id) {
            if (this.selectedAddonsMap[id] > 1) {
                this.selectedAddonsMap[id]--;
            } else {
                delete this.selectedAddonsMap[id];
            }
        },
        getAddonsTotal() {
            let total = 0;
            for (const [addonId, qty] of Object.entries(this.selectedAddonsMap)) {
                const item = this.addons.find(a => a.id == addonId);
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

        // Photostrip Column Slicers for Preview
        getStarredPhotos() {
            const starred = this.photos.filter(p => p.is_starred);
            if (starred.length === 0) return [];
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
            return this.getStarredPhotos().slice(5, 5);
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

        // Step 5: Finalize & Generate Templates
        finalizeTransaction() {
            this.isSubmitting = true;

            const payload = {
                customer_id: this.selectedCustomer.id,
                package_id: this.selectedPackageId,
                addons: Object.entries(this.selectedAddonsMap).map(([id, qty]) => ({ id: parseInt(id), qty: parseInt(qty) })),
                discount_amount: parseFloat(this.discountAmount) || 0,
                payment_method: this.paymentMethod,
                payment_status: 'completed',
                photos: this.photos.map(p => ({
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
            this.showSuccessModal = false;
            this.finalResult = null;
        }
    };
}
</script>
@endsection

