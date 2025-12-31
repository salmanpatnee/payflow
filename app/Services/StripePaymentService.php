<?php

namespace App\Services;

use App\Models\PaymentItem;
use App\Models\PaymentTransaction;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePaymentService
{
    public function __construct(
        private StripeClient $stripe
    ) {
        // Initialize Stripe client with secret key
        // API version is set globally via config or per-request
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Create a PaymentIntent for a payment item
     *
     * @return array{client_secret: string, payment_intent_id: string, status: string}
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(PaymentItem $paymentItem): array
    {
        $startTime = microtime(true);

        // Check if PaymentIntent already exists and is reusable
        if ($paymentItem->stripe_payment_intent_id) {
            try {
                $existingPaymentIntent = $this->stripe->paymentIntents->retrieve($paymentItem->stripe_payment_intent_id);

                // Reuse if in a valid state (requires_payment_method, requires_confirmation, or requires_action)
                if (in_array($existingPaymentIntent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'])) {
                    $duration = (microtime(true) - $startTime) * 1000;
                    \Log::info('Reusing existing PaymentIntent', [
                        'payment_item_id' => $paymentItem->id,
                        'payment_intent_id' => $existingPaymentIntent->id,
                        'status' => $existingPaymentIntent->status,
                        'duration_ms' => round($duration, 2),
                    ]);

                    return [
                        'client_secret' => $existingPaymentIntent->client_secret,
                        'payment_intent_id' => $existingPaymentIntent->id,
                        'status' => $existingPaymentIntent->status,
                    ];
                }
            } catch (ApiErrorException $e) {
                // If retrieval fails, log and create a new one
                \Log::warning('Failed to retrieve existing PaymentIntent, creating new one', [
                    'payment_item_id' => $paymentItem->id,
                    'payment_intent_id' => $paymentItem->stripe_payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Generate unique idempotency key including timestamp for new attempts
        $idempotencyKey = 'pi_create_'.$paymentItem->id.'_'.time();

        try {
            // Create PaymentIntent with idempotency key
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToStripeAmount($paymentItem->amount, $paymentItem->currency),
                'currency' => $paymentItem->currency,
                'metadata' => [
                    'payment_item_id' => $paymentItem->id,
                    'payment_collection_id' => $paymentItem->payment_collection_id,
                ],
                'description' => $paymentItem->description,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Update payment item with PaymentIntent ID and status
            $paymentItem->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'processing',
            ]);

            // Record transaction
            $this->recordTransaction($paymentItem, $paymentIntent);

            // Log performance metrics
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            \Log::info('PaymentIntent created successfully', [
                'payment_item_id' => $paymentItem->id,
                'payment_intent_id' => $paymentIntent->id,
                'duration_ms' => round($duration, 2),
                'amount' => $paymentItem->amount,
                'currency' => $paymentItem->currency,
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            // Log error with performance metrics
            $duration = (microtime(true) - $startTime) * 1000;
            \Log::error('Stripe PaymentIntent creation failed', [
                'payment_item_id' => $paymentItem->id,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
                'duration_ms' => round($duration, 2),
            ]);

            throw $e;
        }
    }

    /**
     * Confirm a payment after client-side processing
     *
     * @return array{status: string, message: string}
     *
     * @throws ApiErrorException
     */
    public function confirmPayment(PaymentItem $paymentItem, string $paymentIntentId): array
    {
        $startTime = microtime(true);

        try {
            // Retrieve the PaymentIntent to get current status
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            // Record transaction
            $this->recordTransaction($paymentItem, $paymentIntent);

            if ($paymentIntent->status === 'succeeded') {
                // Update payment item status
                $paymentItem->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                $duration = (microtime(true) - $startTime) * 1000;
                \Log::info('Payment confirmed successfully', [
                    'payment_item_id' => $paymentItem->id,
                    'payment_intent_id' => $paymentIntentId,
                    'duration_ms' => round($duration, 2),
                ]);

                return [
                    'status' => 'completed',
                    'message' => 'Payment confirmed successfully',
                ];
            }

            if ($paymentIntent->status === 'requires_payment_method') {
                // Payment failed
                $paymentItem->update([
                    'status' => 'failed',
                ]);

                $duration = (microtime(true) - $startTime) * 1000;
                \Log::warning('Payment failed', [
                    'payment_item_id' => $paymentItem->id,
                    'payment_intent_id' => $paymentIntentId,
                    'duration_ms' => round($duration, 2),
                ]);

                return [
                    'status' => 'failed',
                    'message' => 'Payment failed. Please try again with a different payment method.',
                ];
            }

            // Payment still processing
            $duration = (microtime(true) - $startTime) * 1000;
            \Log::info('Payment still processing', [
                'payment_item_id' => $paymentItem->id,
                'payment_intent_id' => $paymentIntentId,
                'duration_ms' => round($duration, 2),
            ]);

            return [
                'status' => 'processing',
                'message' => 'Payment is being processed',
            ];
        } catch (ApiErrorException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            \Log::error('Stripe payment confirmation failed', [
                'payment_item_id' => $paymentItem->id,
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
                'duration_ms' => round($duration, 2),
            ]);

            throw $e;
        }
    }

    /**
     * Handle webhook events from Stripe
     *
     * @throws \UnexpectedValueException
     */
    public function handleWebhook(array $payload, string $signature): bool
    {
        try {
            // Verify webhook signature
            $event = \Stripe\Webhook::constructEvent(
                json_encode($payload),
                $signature,
                config('stripe.webhook.secret')
            );

            // Handle different event types
            return match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
                'payment_intent.processing' => $this->handlePaymentIntentProcessing($event->data->object),
                default => true, // Unhandled event type, return success
            };
        } catch (\UnexpectedValueException $e) {
            \Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentIntentSucceeded(\Stripe\PaymentIntent $paymentIntent): bool
    {
        $paymentItemId = $paymentIntent->metadata->payment_item_id ?? null;

        if (! $paymentItemId) {
            \Log::warning('PaymentIntent succeeded but no payment_item_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return false;
        }

        $paymentItem = PaymentItem::find($paymentItemId);

        if (! $paymentItem) {
            \Log::warning('PaymentItem not found for succeeded PaymentIntent', [
                'payment_item_id' => $paymentItemId,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return false;
        }

        // Update payment item status
        $paymentItem->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Record transaction
        $this->recordTransaction($paymentItem, $paymentIntent);

        \Log::info('Payment succeeded via webhook', [
            'payment_item_id' => $paymentItem->id,
            'payment_intent_id' => $paymentIntent->id,
        ]);

        return true;
    }

    /**
     * Handle failed payment intent
     */
    private function handlePaymentIntentFailed(\Stripe\PaymentIntent $paymentIntent): bool
    {
        $paymentItemId = $paymentIntent->metadata->payment_item_id ?? null;

        if (! $paymentItemId) {
            return false;
        }

        $paymentItem = PaymentItem::find($paymentItemId);

        if (! $paymentItem) {
            return false;
        }

        // Update payment item status
        $paymentItem->update([
            'status' => 'failed',
        ]);

        // Record transaction with failure details
        $this->recordTransaction($paymentItem, $paymentIntent);

        \Log::info('Payment failed via webhook', [
            'payment_item_id' => $paymentItem->id,
            'payment_intent_id' => $paymentIntent->id,
            'failure_code' => $paymentIntent->last_payment_error?->code,
        ]);

        return true;
    }

    /**
     * Handle processing payment intent
     */
    private function handlePaymentIntentProcessing(\Stripe\PaymentIntent $paymentIntent): bool
    {
        $paymentItemId = $paymentIntent->metadata->payment_item_id ?? null;

        if (! $paymentItemId) {
            return false;
        }

        $paymentItem = PaymentItem::find($paymentItemId);

        if (! $paymentItem) {
            return false;
        }

        // Update payment item status if not already processing
        if ($paymentItem->status !== 'processing') {
            $paymentItem->update([
                'status' => 'processing',
            ]);
        }

        // Record transaction
        $this->recordTransaction($paymentItem, $paymentIntent);

        return true;
    }

    /**
     * Record a payment transaction
     */
    private function recordTransaction(PaymentItem $paymentItem, PaymentIntent $paymentIntent): void
    {
        PaymentTransaction::create([
            'payment_item_id' => $paymentItem->id,
            'stripe_response' => $paymentIntent->toArray(),
            'status' => $paymentIntent->status,
            'amount' => $this->convertFromStripeAmount($paymentIntent->amount, $paymentIntent->currency),
            'currency' => $paymentIntent->currency,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
            'failure_code' => $paymentIntent->last_payment_error?->code,
            'failure_message' => $paymentIntent->last_payment_error?->message,
            'created_at' => now(),
        ]);
    }

    /**
     * Convert amount to Stripe format (cents for most currencies)
     */
    private function convertToStripeAmount(float $amount, string $currency): int
    {
        // Zero-decimal currencies (e.g., JPY, KRW) don't need multiplication
        $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];

        if (in_array(strtolower($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }

        return (int) ($amount * 100);
    }

    /**
     * Convert amount from Stripe format back to decimal
     */
    private function convertFromStripeAmount(int $amount, string $currency): float
    {
        $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];

        if (in_array(strtolower($currency), $zeroDecimalCurrencies)) {
            return (float) $amount;
        }

        return $amount / 100;
    }

    /**
     * Retry a failed payment
     *
     * @return array{client_secret: string, payment_intent_id: string, status: string}
     *
     * @throws ApiErrorException
     */
    public function retryPayment(PaymentItem $paymentItem): array
    {
        // Reset status to pending to allow retry
        if ($paymentItem->status === 'failed') {
            $paymentItem->update(['status' => 'pending']);
        }

        // Create a new PaymentIntent for the retry
        // Use a different idempotency key to avoid conflicts
        $idempotencyKey = 'pi_retry_'.$paymentItem->id.'_'.time();

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToStripeAmount($paymentItem->amount, $paymentItem->currency),
                'currency' => $paymentItem->currency,
                'metadata' => [
                    'payment_item_id' => $paymentItem->id,
                    'payment_collection_id' => $paymentItem->payment_collection_id,
                    'retry' => 'true',
                ],
                'description' => $paymentItem->description.' (Retry)',
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Update payment item with new PaymentIntent ID
            $paymentItem->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'processing',
            ]);

            // Record transaction
            $this->recordTransaction($paymentItem, $paymentIntent);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            \Log::error('Stripe payment retry failed', [
                'payment_item_id' => $paymentItem->id,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
            ]);

            throw $e;
        }
    }
}
