<?php

use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/listing/{listing}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login')->middleware('guest');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('guest');
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::delete('/images/{image}', [ListingController::class, 'destroyImage'])->name('images.destroy');
});
