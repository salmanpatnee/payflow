<?php

use App\Http\Controllers\Payment\PaymentCollectionController;
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

// Payment collection routes for Stripe processing
Route::prefix('payment')->name('payment.')->middleware(\App\Http\Middleware\SecurePaymentHeaders::class)->group(function () {
    // Display payment page
    Route::get('/{uuid}', [PaymentCollectionController::class, 'show'])
        ->name('show');

    // Thank you page
    Route::get('/{uuid}/thank-you', [PaymentCollectionController::class, 'thankYou'])
        ->name('thank-you');

    // API endpoints for payment processing with rate limiting
    Route::post('/{uuid}/payment-intent', [PaymentCollectionController::class, 'createPaymentIntent'])
        ->middleware('throttle:10,1') // 10 requests per minute per IP
        ->name('create-intent');

    Route::post('/{uuid}/confirm-payment', [PaymentCollectionController::class, 'confirmPayment'])
        ->middleware('throttle:10,1') // 10 requests per minute per IP
        ->name('confirm');

    // Status endpoint for real-time updates (higher limit for polling)
    Route::get('/{uuid}/status', [PaymentCollectionController::class, 'status'])
        ->middleware('throttle:30,1') // 30 requests per minute per IP for status polling
        ->name('status');
});

// Stripe webhook endpoint (exclude from CSRF protection in middleware)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe');

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
