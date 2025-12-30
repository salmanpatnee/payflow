<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public payment link routes
Route::get('/pay/{token}', [\App\Http\Controllers\PaymentLinkController::class, 'show'])
    ->middleware('web')
    ->name('pay.show');

Route::get('/pay/{token}/item/{itemId}', [\App\Http\Controllers\PaymentLinkController::class, 'showItem'])
    ->middleware('web')
    ->name('pay.item');

// Admin payment collection routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/payment-collections/{id}/generate-link', [\App\Http\Controllers\Admin\PaymentCollectionLinkController::class, 'generate'])
        ->name('payment-collections.generate-link');

    Route::get('/payment-collections/{id}/payment-link', [\App\Http\Controllers\Admin\PaymentCollectionLinkController::class, 'show'])
        ->name('payment-collections.payment-link');

    Route::delete('/payment-collections/{id}/payment-link', [\App\Http\Controllers\Admin\PaymentCollectionLinkController::class, 'revoke'])
        ->name('payment-collections.revoke-link');
});

require __DIR__.'/settings.php';
