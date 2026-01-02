<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [WebhookController::class, 'handleStripeWebhook'])->name('stripe.webhook');
