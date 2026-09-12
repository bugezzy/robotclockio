<?php

use App\Http\Controllers\Admin\CardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PunchController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');

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
});
