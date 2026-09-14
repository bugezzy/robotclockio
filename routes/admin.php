<?php

use App\Http\Controllers\Admin\CardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PunchController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::get('organizations/{organization}/license', [OrganizationController::class, 'licenseHistory'])->name('organizations.license.history');
    Route::post('organizations/{organization}/license', [OrganizationController::class, 'issueLicense'])->name('organizations.license.issue');
    Route::delete('organizations/{organization}/license', [OrganizationController::class, 'revokeLicense'])->name('organizations.license.revoke');

    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{targetUser}', [UserController::class, 'update'])->name('users.update');
    Route::put('users/{targetUser}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
    Route::delete('users/{targetUser}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('cards', [CardController::class, 'index'])->name('cards.index');
    Route::post('cards', [CardController::class, 'store'])->name('cards.store');
    Route::delete('cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');

    Route::get('punches', [PunchController::class, 'index'])->name('punches.index');

    Route::get('downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::post('downloads', [DownloadController::class, 'store'])->name('downloads.store');

    Route::get('store', [StoreController::class, 'index'])->name('store.index');
    Route::post('store/checkout', [StoreController::class, 'checkout'])->name('store.checkout');
    Route::get('store/success', [StoreController::class, 'success'])->name('store.success');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
