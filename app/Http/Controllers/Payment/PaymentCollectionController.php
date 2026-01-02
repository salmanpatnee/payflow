<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\Payment\PaymentCollectionResource;
use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;

class PaymentCollectionController extends Controller
{
    public function __construct(
        private StripePaymentService $stripePaymentService
    ) {}

    /**
     * Display the payment page for a collection
     */
    public function show(string $uuid): Response
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->with(['paymentItems' => function ($query) {
                $query->orderBy('created_at');
            }])
            ->firstOrFail();

        // Check if collection is accessible
        if ($collection->status === 'expired') {
            abort(403, 'This payment collection has expired.');
        }

        if ($collection->status === 'completed') {
            return Inertia::render('Payment/ThankYouPage', [
                'collection' => $collection,
            ]);
        }

        // Check if collection has expired
        if ($collection->expires_at && $collection->expires_at->isPast()) {
            $collection->update(['status' => 'expired']);
            abort(403, 'This payment collection has expired.');
        }

        // Auto-detect and reset stale processing payments
        $this->resetStaleProcessingPayments($collection);

        return Inertia::render('Payment/PaymentPage', [
            'collection' => [
                'uuid' => $collection->uuid,
                'title' => $collection->title,
                'description' => $collection->description,
                'status' => $collection->status,
                'items' => $collection->paymentItems->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'status' => $item->status,
                    'paid_at' => $item->paid_at?->toIso8601String(),
                ]),
            ],
            'stripeKey' => config('stripe.key'),
        ]);
    }

    /**
     * Create a PaymentIntent for a payment item
     */
    public function createPaymentIntent(string $uuid, ProcessPaymentRequest $request): JsonResponse
    {
        $collection = PaymentCollection::where('uuid', $uuid)->firstOrFail();

        // Verify collection is still active
        if ($collection->status !== 'active') {
            return response()->json([
                'error' => 'This payment collection is no longer active.',
            ], 403);
        }

        $paymentItem = PaymentItem::findOrFail($request->payment_item_id);

        // Verify payment item belongs to this collection
        if ($paymentItem->payment_collection_id !== $collection->id) {
            return response()->json([
                'error' => 'Payment item does not belong to this collection.',
            ], 403);
        }

        // Check if payment is already completed
        if ($paymentItem->status === 'completed') {
            return response()->json([
                'error' => 'This payment has already been completed.',
            ], 409);
        }

        // Check if payment is currently being processed by another request
        if ($paymentItem->status === 'processing') {
            // If there's an existing PaymentIntent, check its status
            if ($paymentItem->stripe_payment_intent_id) {
                try {
                    $stripe = new \Stripe\StripeClient(config('stripe.secret'));
                    $existingPaymentIntent = $stripe->paymentIntents->retrieve($paymentItem->stripe_payment_intent_id);

                    // If PaymentIntent requires payment method (declined/failed), allow retry
                    if ($existingPaymentIntent->status === 'requires_payment_method') {
                        \Log::info('Payment declined, allowing retry', [
                            'payment_item_id' => $paymentItem->id,
                            'payment_intent_id' => $paymentItem->stripe_payment_intent_id,
                        ]);
                        // Reset status to failed to allow retry flow
                        $paymentItem->update(['status' => 'failed']);
                    } elseif ($paymentItem->updated_at->diffInMinutes(now()) < 15) {
                        // Still actually processing and not stale
                        return response()->json([
                            'error' => 'Payment is already being processed. Please wait.',
                        ], 409);
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // If we can't retrieve the PaymentIntent, log and allow retry
                    \Log::warning('Could not retrieve PaymentIntent, allowing retry', [
                        'payment_item_id' => $paymentItem->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($paymentItem->updated_at->diffInMinutes(now()) < 15) {
                // No PaymentIntent ID but still processing and not stale
                return response()->json([
                    'error' => 'Payment is already being processed. Please wait.',
                ], 409);
            }

            // If it's been processing for more than 15 minutes, allow retry
            if ($paymentItem->updated_at->diffInMinutes(now()) >= 15) {
                \Log::warning('Stale processing payment detected, allowing retry', [
                    'payment_item_id' => $paymentItem->id,
                    'updated_at' => $paymentItem->updated_at,
                ]);
            }
        }

        try {
            // Use retry method for failed payments, regular method for pending/processing
            if ($paymentItem->status === 'failed') {
                $result = $this->stripePaymentService->retryPayment($paymentItem);
            } else {
                $result = $this->stripePaymentService->createPaymentIntent($paymentItem);
            }

            return response()->json($result, 200);
        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to create payment intent. Please try again.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm a payment after client-side processing
     */
    public function confirmPayment(string $uuid, ProcessPaymentRequest $request): JsonResponse
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->with('paymentItems')
            ->firstOrFail();

        $paymentItem = PaymentItem::findOrFail($request->payment_item_id);

        // Verify payment item belongs to this collection
        if ($paymentItem->payment_collection_id !== $collection->id) {
            return response()->json([
                'error' => 'Payment item does not belong to this collection.',
            ], 403);
        }

        try {
            $result = $this->stripePaymentService->confirmPayment(
                $paymentItem,
                $request->payment_intent_id
            );

            // Refresh collection to get updated payment statuses
            $collection->refresh();

            // Check if all payments are completed
            $totalItems = $collection->paymentItems->count();
            $completedItems = $collection->paymentItems->where('status', 'completed')->count();
            $allCompleted = $completedItems === $totalItems;

            // Update collection status if all payments are completed
            if ($allCompleted && $collection->status !== 'completed') {
                $collection->update(['status' => 'completed']);
            }

            // Determine redirect URL based on completion status and payment link token
            if ($allCompleted) {
                // All payments completed - redirect to thank you page
                $redirectUrl = $collection->payment_link_token
                    ? route('pay.thank-you', $collection->payment_link_token)
                    : route('payment.thank-you', $collection->uuid);
            } else {
                // More payments remaining - redirect back to payment page
                $redirectUrl = $collection->payment_link_token
                    ? route('pay.show', $collection->payment_link_token)
                    : route('payment.show', $collection->uuid);
            }

            return response()->json([
                ...$result,
                'collection_status' => [
                    'all_completed' => $allCompleted,
                    'completed_count' => $completedItems,
                    'total_count' => $totalItems,
                    'remaining_count' => $totalItems - $completedItems,
                ],
                'redirect_url' => $redirectUrl,
            ], 200);
        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to confirm payment. Please try again.',
                'message' => $e->getMessage(),
            ], 424);
        }
    }

    /**
     * Display thank you page after successful payment
     */
    public function thankYou(string $uuid): Response
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->with('paymentItems')
            ->firstOrFail();

        // Only show thank you page if all payments are completed
        if ($collection->status !== 'completed') {
            return redirect()->route('payment.show', $uuid);
        }

        return Inertia::render('Payment/ThankYouPage', [
            'collection' => [
                'uuid' => $collection->uuid,
                'title' => $collection->title,
                'description' => $collection->description,
                'items' => $collection->paymentItems->map(fn ($item) => [
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'status' => $item->status,
                    'paid_at' => $item->paid_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Get current status of payment collection
     */
    public function status(string $uuid): JsonResponse
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->with('paymentItems')
            ->firstOrFail();

        return response()->json(new PaymentCollectionResource($collection));
    }

    /**
     * Reset stale processing payments by checking their actual Stripe status
     */
    private function resetStaleProcessingPayments(PaymentCollection $collection): void
    {
        $processingItems = $collection->paymentItems->where('status', 'processing');

        if ($processingItems->isEmpty()) {
            return;
        }

        $stripe = new \Stripe\StripeClient(config('stripe.secret'));

        foreach ($processingItems as $item) {
            if (! $item->stripe_payment_intent_id) {
                // No PaymentIntent ID, reset to pending
                $item->update(['status' => 'pending']);
                \Log::info('Reset payment item without PaymentIntent to pending', [
                    'payment_item_id' => $item->id,
                ]);

                continue;
            }

            try {
                $paymentIntent = $stripe->paymentIntents->retrieve($item->stripe_payment_intent_id);

                // Reset based on actual Stripe status
                if ($paymentIntent->status === 'requires_payment_method') {
                    // Payment was declined or failed
                    $item->update(['status' => 'failed']);
                    \Log::info('Reset declined payment to failed', [
                        'payment_item_id' => $item->id,
                        'payment_intent_id' => $item->stripe_payment_intent_id,
                    ]);
                } elseif ($paymentIntent->status === 'succeeded') {
                    // Payment succeeded but wasn't updated
                    $item->update(['status' => 'completed', 'paid_at' => now()]);
                    \Log::info('Reset succeeded payment to completed', [
                        'payment_item_id' => $item->id,
                        'payment_intent_id' => $item->stripe_payment_intent_id,
                    ]);
                } elseif ($paymentIntent->status === 'canceled') {
                    // Payment was canceled
                    $item->update(['status' => 'failed']);
                    \Log::info('Reset canceled payment to failed', [
                        'payment_item_id' => $item->id,
                        'payment_intent_id' => $item->stripe_payment_intent_id,
                    ]);
                }
                // If still in processing/requires_confirmation/requires_action, keep as processing
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // If we can't retrieve the PaymentIntent, reset to failed
                $item->update(['status' => 'failed']);
                \Log::warning('Could not retrieve PaymentIntent, reset to failed', [
                    'payment_item_id' => $item->id,
                    'payment_intent_id' => $item->stripe_payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Reload the collection to get updated statuses
        $collection->load('paymentItems');
    }
}
