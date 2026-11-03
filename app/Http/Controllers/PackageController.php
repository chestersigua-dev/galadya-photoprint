<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonRequest;
use App\Http\Requests\StorePackageRequest;
use App\Models\Package;
use App\Models\PackageAddon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    /**
     * Display all packages and add-ons.
     */
    public function index(): View
    {
        $packages = Package::withCount('transactions')->get();
        $addons = PackageAddon::all();

        return view('packages.index', compact('packages', 'addons'));
    }

    /**
     * Store new package.
     */
    public function storePackage(StorePackageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Package::create($data);

        return redirect()->route('packages.index')
            ->with('success', 'Package created successfully.');
    }

    /**
     * Update package.
     */
    public function updatePackage(StorePackageRequest $request, Package $package): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $package->update($data);

        return redirect()->route('packages.index')
            ->with('success', 'Package updated successfully.');
    }

    /**
     * Store new add-on.
     */
    public function storeAddon(StoreAddonRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        PackageAddon::create($data);

        return redirect()->route('packages.index')
            ->with('success', 'Add-on created successfully.');
    }

    /**
     * Update add-on.
     */
    public function updateAddon(StoreAddonRequest $request, PackageAddon $addon): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $addon->update($data);

        return redirect()->route('packages.index')
            ->with('success', 'Add-on updated successfully.');
    }
}

