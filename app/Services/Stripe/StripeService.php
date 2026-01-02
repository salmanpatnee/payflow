<?php

namespace App\Services\Stripe;

use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    public function getClient(): StripeClient
    {
        return $this->stripe;
    }

    public function createPaymentIntent(float $amount, ?string $currency = null, array $metadata = []): \Stripe\PaymentIntent
    {
        $currency = $currency ?? config('stripe.currency', 'usd');

        return $this->stripe->paymentIntents->create([
            'amount' => (int) ($amount * 100), // Convert to cents
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    public function confirmPaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->confirm($paymentIntentId);
    }

    public function cancelPaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->cancel($paymentIntentId);
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('stripe.webhook.secret')
        );
    }

    public function isInitialized(): bool
    {
        try {
            $this->stripe->balance->retrieve();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
