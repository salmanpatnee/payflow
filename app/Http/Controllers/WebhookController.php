<?php

namespace App\Http\Controllers;

use App\Models\PaymentItem;
use App\Models\PaymentTransaction;
use App\Services\Stripe\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    public function handleStripeWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Webhook signature verification failed', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', [
                    'type' => $event->type,
                ]);
        }

        return response('Webhook received', 200);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $paymentItem = PaymentItem::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($paymentItem) {
            $paymentItem->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            PaymentTransaction::create([
                'payment_item_id' => $paymentItem->id,
                'stripe_response' => $paymentIntent->toArray(),
                'status' => 'succeeded',
                'created_at' => now(),
            ]);

            Log::info('Payment intent succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'payment_item_id' => $paymentItem->id,
            ]);
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $paymentItem = PaymentItem::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($paymentItem) {
            $paymentItem->update([
                'status' => 'failed',
            ]);

            PaymentTransaction::create([
                'payment_item_id' => $paymentItem->id,
                'stripe_response' => $paymentIntent->toArray(),
                'status' => 'failed',
                'error_message' => $paymentIntent->last_payment_error?->message ?? 'Payment failed',
                'created_at' => now(),
            ]);

            Log::warning('Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'payment_item_id' => $paymentItem->id,
                'error' => $paymentIntent->last_payment_error?->message,
            ]);
        }
    }

    protected function handlePaymentIntentCanceled($paymentIntent): void
    {
        $paymentItem = PaymentItem::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($paymentItem) {
            $paymentItem->update([
                'status' => 'failed',
            ]);

            PaymentTransaction::create([
                'payment_item_id' => $paymentItem->id,
                'stripe_response' => $paymentIntent->toArray(),
                'status' => 'canceled',
                'error_message' => 'Payment canceled',
                'created_at' => now(),
            ]);

            Log::info('Payment intent canceled', [
                'payment_intent_id' => $paymentIntent->id,
                'payment_item_id' => $paymentItem->id,
            ]);
        }
    }
}
