<?php

use App\Http\Controllers\DiscordInteractionController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::inertia('terms', 'legal/Terms')->name('terms');
Route::inertia('privacy', 'legal/Privacy')->name('privacy');

// Server-to-server, signature-verified — never behind auth, and exempted
// from CSRF in bootstrap/app.php since Stripe can't carry our token.
Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// Discord calls this synchronously for every /clock interaction — signed
// with Ed25519, not CSRF-protected, same reasoning as the Stripe webhook.
Route::post('discord/interactions', DiscordInteractionController::class)->name('discord.interactions');

Route::middleware(['auth', 'verified'])->group(function () {
    // This app is exclusively an admin panel — there's no separate
    // "regular user" dashboard, so this just forwards to the real one.
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
