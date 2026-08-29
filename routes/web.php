<?php

use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\UserInviteController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\PasskeyEnrollController;
use App\Http\Controllers\Auth\PinSetupController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/listing/{listing}', [CatalogController::class, 'show'])->name('catalog.show');

// No 'guest' middleware here on purpose — AdminAuthController@create itself
// branches on auth state, so /admin works as both the login screen and the
// admin entry point (see the controller for why).
Route::get('/admin', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login/pin', [AdminAuthController::class, 'pinLogin'])
    ->middleware(['guest', 'throttle:5,15'])
    ->name('admin.login.pin');
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

// Invite -> PIN -> mandatory passkey onboarding. The 'guest' middleware here
// just means "not already logged in as someone else" — the signed-URL check
// (and, for the passkey step, an in-progress pin_set session) is the real gate.
Route::middleware('guest')->group(function () {
    // Only the GET (the magic link itself) needs the signature check — the
    // POST is guarded instead by the user's own status flipping away from
    // 'invited' the moment a PIN is set, which makes the link effectively
    // one-time-use without needing to re-verify a signature on submit.
    Route::get('/onboarding/pin/{user}', [PinSetupController::class, 'create'])
        ->middleware('signed')
        ->name('onboarding.pin.create');
    Route::post('/onboarding/pin/{user}', [PinSetupController::class, 'store'])
        ->name('onboarding.pin.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/onboarding/passkey', [PasskeyEnrollController::class, 'create'])
        ->name('onboarding.passkey.create');
});

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::get('/images', [ListingController::class, 'images'])->name('images.index');
    Route::delete('/images/{image}', [ListingController::class, 'destroyImage'])->name('images.destroy');
    Route::delete('/videos/{video}', [ListingController::class, 'destroyVideo'])->name('videos.destroy');

    Route::get('/users/invite', [UserInviteController::class, 'create'])->name('users.invite.create');
    Route::post('/users/invite', [UserInviteController::class, 'store'])->name('users.invite.store');
});
