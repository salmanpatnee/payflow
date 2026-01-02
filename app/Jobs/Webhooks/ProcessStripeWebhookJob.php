<?php

namespace App\Jobs\Webhooks;

use App\Jobs\GeneratePaymentReceiptJob;
use App\Models\PaymentItem;
use App\Models\PaymentTransaction;
use App\Services\PaymentStatusSyncService;
use App\Services\WebhookMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    public function __construct(
        public Event $event
    ) {}

    public function handle(WebhookMonitoringService $monitoring): void
    {
        // Check for duplicate event
        $existingTransaction = PaymentTransaction::where('stripe_event_id', $this->event->id)->first();

        if ($existingTransaction) {
            Log::info('Duplicate webhook event detected, skipping', [
                'event_id' => $this->event->id,
                'event_type' => $this->event->type,
            ]);

            return;
        }

        // Create transaction record
        $transaction = $this->createTransaction();

        try {
            // Handle the event based on type
            match ($this->event->type) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($transaction),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($transaction),
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($transaction),
                default => $this->handleUnsupportedEvent($transaction),
            };

            // Mark transaction as completed
            $transaction->update([
                'processed_at' => now(),
                'processing_status' => 'completed',
            ]);

            // Record successful webhook processing
            $monitoring->recordWebhookAttempt($transaction, true);

            Log::info('Webhook processed successfully', [
                'event_id' => $this->event->id,
                'event_type' => $this->event->type,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            $transaction->increment('processing_attempts');
            $transaction->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            // Record failed webhook processing
            $monitoring->recordWebhookAttempt($transaction->fresh(), false, $e->getMessage());

            Log::error('Webhook processing failed', [
                'event_id' => $this->event->id,
                'event_type' => $this->event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempts' => $transaction->processing_attempts,
            ]);

            throw $e;
        }
    }

    protected function createTransaction(): PaymentTransaction
    {
        return PaymentTransaction::create([
            'payment_item_id' => $this->getPaymentItemId(),
            'stripe_event_id' => $this->event->id,
            'stripe_event_type' => $this->event->type,
            'payload' => $this->event->toArray(),
            'processing_status' => 'processing',
            'processing_attempts' => 1,
        ]);
    }

    protected function getPaymentItemId(): ?int
    {
        $data = $this->event->data->object;

        // Try to get payment item ID from metadata
        if (isset($data->metadata->payment_item_id)) {
            return (int) $data->metadata->payment_item_id;
        }

        // Try to find by payment intent ID
        if (isset($data->payment_intent)) {
            $paymentItem = PaymentItem::where('stripe_payment_intent_id', $data->payment_intent)->first();

            return $paymentItem?->id;
        }

        // Try to find by session ID for checkout.session.completed
        if (isset($data->id) && $this->event->type === 'checkout.session.completed') {
            $paymentItem = PaymentItem::where('stripe_session_id', $data->id)->first();

            return $paymentItem?->id;
        }

        return null;
    }

    protected function handlePaymentIntentSucceeded(PaymentTransaction $transaction): void
    {
        $paymentIntent = $this->event->data->object;

        if (! $transaction->payment_item_id) {
            Log::warning('Payment item not found for successful payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $paymentItem = PaymentItem::find($transaction->payment_item_id);

        if (! $paymentItem) {
            throw new \Exception("Payment item {$transaction->payment_item_id} not found");
        }

        // Update paid_at timestamp
        $paymentItem->update(['paid_at' => now()]);

        // Use sync service to update status (handles events, cache, and collection sync)
        $syncService = app(PaymentStatusSyncService::class);
        $syncService->updatePaymentItemStatus($paymentItem->fresh(), 'completed');

        Log::info('Payment marked as completed', [
            'payment_item_id' => $paymentItem->id,
            'payment_intent_id' => $paymentIntent->id,
        ]);

        // Dispatch receipt generation job
        GeneratePaymentReceiptJob::dispatch($transaction);
    }

    protected function handlePaymentIntentFailed(PaymentTransaction $transaction): void
    {
        $paymentIntent = $this->event->data->object;

        if (! $transaction->payment_item_id) {
            Log::warning('Payment item not found for failed payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $paymentItem = PaymentItem::find($transaction->payment_item_id);

        if (! $paymentItem) {
            throw new \Exception("Payment item {$transaction->payment_item_id} not found");
        }

        $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';

        // Use sync service to update status (handles events, cache, and collection sync)
        $syncService = app(PaymentStatusSyncService::class);
        $syncService->updatePaymentItemStatus($paymentItem, 'failed');

        Log::info('Payment marked as failed', [
            'payment_item_id' => $paymentItem->id,
            'payment_intent_id' => $paymentIntent->id,
            'error' => $errorMessage,
        ]);
    }

    protected function handleCheckoutSessionCompleted(PaymentTransaction $transaction): void
    {
        $session = $this->event->data->object;

        if (! $transaction->payment_item_id) {
            Log::warning('Payment item not found for completed checkout session', [
                'session_id' => $session->id,
            ]);

            return;
        }

        $paymentItem = PaymentItem::find($transaction->payment_item_id);

        if (! $paymentItem) {
            throw new \Exception("Payment item {$transaction->payment_item_id} not found");
        }

        // Update paid_at timestamp
        $paymentItem->update(['paid_at' => now()]);

        // Use sync service to update status (handles events, cache, and collection sync)
        $syncService = app(PaymentStatusSyncService::class);
        $syncService->updatePaymentItemStatus($paymentItem->fresh(), 'completed');

        Log::info('Payment marked as completed from checkout session', [
            'payment_item_id' => $paymentItem->id,
            'session_id' => $session->id,
        ]);

        // Dispatch receipt generation job
        GeneratePaymentReceiptJob::dispatch($transaction);
    }

    protected function handleUnsupportedEvent(PaymentTransaction $transaction): void
    {
        Log::info('Unsupported webhook event type received', [
            'event_type' => $this->event->type,
            'event_id' => $this->event->id,
        ]);
    }
}
