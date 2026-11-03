<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes: Galadya Photoprint
|--------------------------------------------------------------------------
*/

// Guest / Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

// Authenticated Application routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Default entry redirect
    Route::get('/', fn() => redirect()->route('dashboard'));

    // 1. Dashboard Module
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('module:dashboard')
        ->name('dashboard');

    // 2. Import & Print Workflow (Step-by-Step Wizard)
    Route::prefix('workflow')->name('workflow.')->middleware('module:import_print')->group(function () {
        Route::get('/start', [WorkflowController::class, 'index'])->name('start');
        Route::post('/upload', [WorkflowController::class, 'upload'])->name('upload');
        Route::post('/delete-photo', [WorkflowController::class, 'deletePhoto'])->name('delete.photo');
        Route::post('/clear-photos', [WorkflowController::class, 'clearPhotos'])->name('clear.photos');
        Route::post('/finalize', [WorkflowController::class, 'finalize'])->name('finalize');
        Route::get('/api/clients', [WorkflowController::class, 'apiClients'])->name('api.clients');
        Route::post('/api/clients', [WorkflowController::class, 'apiStoreClient'])->name('api.clients.store');
    });

    // 3. Client Database Module
    Route::prefix('clients')->name('clients.')->middleware('module:clients')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    });

    // 4. Packages & Add-ons Module
    Route::prefix('packages')->name('packages.')->middleware('module:packages')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::post('/', [PackageController::class, 'storePackage'])->name('store');
        Route::put('/{package}', [PackageController::class, 'updatePackage'])->name('update');
        Route::post('/addons', [PackageController::class, 'storeAddon'])->name('addons.store');
        Route::put('/addons/{addon}', [PackageController::class, 'updateAddon'])->name('addons.update');
    });

    // 5. Transaction History & Thermal Receipts
    Route::prefix('transactions')->name('transactions.')->middleware('module:transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('receipt');
    });

    // Direct download print files
    Route::get('/prints/download/{filename}', [TransactionController::class, 'downloadPrint'])
        ->name('prints.download');

    // 6. User & Role Management
    Route::prefix('admin/users')->name('admin.users.')->middleware('module:users_roles')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle', [UserController::class, 'toggleStatus'])->name('toggle');
    });

    // 7. Settings Module
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
    });
});
