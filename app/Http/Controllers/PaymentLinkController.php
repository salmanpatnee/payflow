<?php

namespace App\Http\Controllers;

use App\Models\ClientAccessRecord;
use App\Models\PaymentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentLinkController extends Controller
{
    /**
     * Display the payment details for a public payment link.
     */
    public function show(Request $request, string $token): Response
    {
        return $this->renderPaymentPage($request, $token);
    }

    /**
     * Display thank you page after all payments are completed.
     */
    public function thankYou(Request $request, string $token): Response
    {
        $collection = PaymentCollection::where('payment_link_token', $token)
            ->with('paymentItems')
            ->first();

        if (! $collection) {
            abort(404);
        }

        // Only show thank you page if all payments are completed
        $allCompleted = $collection->paymentItems->every(fn ($item) => $item->status === 'completed');

        if (! $allCompleted) {
            // Redirect back to payment page if not all completed
            return redirect()->route('pay.show', $token);
        }

        // Track client access
        ClientAccessRecord::create([
            'payment_collection_id' => $collection->id,
            'access_token' => $token,
            'ip_address' => $request->ip(),
            'accessed_at' => now(),
            'user_agent' => $request->userAgent(),
        ]);

        return Inertia::render('Payment/ThankYouPage', [
            'collection' => [
                'uuid' => $collection->uuid,
                'title' => $collection->name,
                'description' => $collection->description,
                'items' => $collection->paymentItems->map(fn ($item) => [
                    'name' => $item->name,
                    'description' => $item->description,
                    'amount' => $item->amount ?? (float) $item->price,
                    'currency' => $item->currency ?? 'usd',
                    'status' => $item->status,
                    'paid_at' => $item->paid_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Display a specific payment item for payment.
     */
    public function showItem(Request $request, string $token, int $itemId): Response
    {
        $collection = PaymentCollection::where('payment_link_token', $token)
            ->with('paymentItems')
            ->first();

        if (! $collection || ! $collection->isPaymentLinkActive()) {
            abort(404);
        }

        $item = $collection->paymentItems->find($itemId);
        if (! $item) {
            abort(404);
        }

        // Track client access
        ClientAccessRecord::create([
            'payment_collection_id' => $collection->id,
            'access_token' => $token,
            'ip_address' => $request->ip(),
            'accessed_at' => now(),
            'user_agent' => $request->userAgent(),
        ]);

        return Inertia::render('Pay/Item', [
            'collection' => [
                'id' => $collection->id,
                'uuid' => $collection->uuid,
                'name' => $collection->name,
                'description' => $collection->description,
                'expires_at' => $collection->payment_link_expires_at,
            ],
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'amount' => $item->amount ?? (float) $item->price,
                'currency' => $item->currency ?? 'usd',
                'status' => $item->status ?? 'pending',
                'due_date' => $collection->due_date,
            ],
            'token' => $token,
            'stripeKey' => config('stripe.key'),
        ]);
    }

    /**
     * Render payment page with all items.
     */
    private function renderPaymentPage(Request $request, string $token): Response
    {
        $collection = PaymentCollection::where('payment_link_token', $token)
            ->with('paymentItems')
            ->first();

        if (! $collection || ! $collection->isPaymentLinkActive()) {
            abort(404);
        }

        // Track client access
        ClientAccessRecord::create([
            'payment_collection_id' => $collection->id,
            'access_token' => $token,
            'ip_address' => $request->ip(),
            'accessed_at' => now(),
            'user_agent' => $request->userAgent(),
        ]);

        $items = $collection->paymentItems->map(function ($item) use ($collection) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'amount' => $item->amount ?? (float) $item->price,
                'status' => $item->status === 'completed' ? 'paid' : $item->status,
                'due_date' => $collection->due_date,
            ];
        });

        $totalAmount = $items->sum('amount');

        return Inertia::render('Pay/Show', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description,
                'expires_at' => $collection->payment_link_expires_at,
                'items' => $items,
                'total_amount' => $totalAmount,
            ],
            'token' => $token,
        ]);
    }
}
