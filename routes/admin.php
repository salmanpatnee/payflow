<?php

use App\Http\Controllers\Admin\PaymentCollectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group and require authentication.
|
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    // Payment Collections
    Route::get('/payment-collections', [PaymentCollectionController::class, 'index'])
        ->name('admin.payment-collections.index');
    Route::get('/payment-collections/create', [PaymentCollectionController::class, 'create'])
        ->name('admin.payment-collections.create');
    Route::post('/payment-collections', [PaymentCollectionController::class, 'store'])
        ->name('admin.payment-collections.store');
    Route::get('/payment-collections/{id}', [PaymentCollectionController::class, 'show'])
        ->name('admin.payment-collections.show');
    Route::get('/payment-collections/{id}/edit', [PaymentCollectionController::class, 'edit'])
        ->name('admin.payment-collections.edit');
    Route::put('/payment-collections/{id}', [PaymentCollectionController::class, 'update'])
        ->name('admin.payment-collections.update');
    Route::delete('/payment-collections/{id}', [PaymentCollectionController::class, 'destroy'])
        ->name('admin.payment-collections.destroy');
});
