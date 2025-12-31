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

        return Inertia::render('Payment/PaymentPage', [
            'collection' => [
                'uuid' => $collection->uuid,
                'title' => $collection->title,
                'description' => $collection->description,
                'status' => $collection->status,
                'items' => $collection->paymentItems->map(fn ($item) => [
                    'id' => $item->id,
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
            // Allow processing to continue for a short time, but check if it's stale
            if ($paymentItem->updated_at->diffInMinutes(now()) < 15) {
                return response()->json([
                    'error' => 'Payment is already being processed. Please wait.',
                ], 409);
            }
            // If it's been processing for more than 15 minutes, allow retry
            \Log::warning('Stale processing payment detected, allowing retry', [
                'payment_item_id' => $paymentItem->id,
                'updated_at' => $paymentItem->updated_at,
            ]);
        }

        try {
            // Use retry method for failed payments, regular method for pending
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

            // Determine redirect URL based on payment link token
            $redirectUrl = $collection->payment_link_token
                ? route('pay.show', $collection->payment_link_token)
                : route('payment.show', $collection->uuid);

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
}
