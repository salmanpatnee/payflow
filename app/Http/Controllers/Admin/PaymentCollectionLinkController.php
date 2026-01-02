<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentCollectionLinkController extends Controller
{
    /**
     * Generate a new payment link for a collection.
     */
    public function generate(Request $request, int $id): JsonResponse
    {
        $collection = PaymentCollection::findOrFail($id);

        $this->authorize('update', $collection);

        $token = $collection->generatePaymentLink();

        return response()->json([
            'token' => $token,
            'url' => $collection->getPaymentLinkUrl(),
            'expires_at' => $collection->payment_link_expires_at,
        ]);
    }

    /**
     * Get payment link info for a collection.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $collection = PaymentCollection::findOrFail($id);

        $this->authorize('view', $collection);

        return response()->json([
            'token' => $collection->payment_link_token,
            'url' => $collection->getPaymentLinkUrl(),
            'expires_at' => $collection->payment_link_expires_at,
            'is_active' => $collection->isPaymentLinkActive(),
        ]);
    }

    /**
     * Revoke a payment link.
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $collection = PaymentCollection::findOrFail($id);

        $this->authorize('update', $collection);

        $collection->revokePaymentLink();

        return response()->json([
            'message' => 'Payment link revoked successfully.',
        ]);
    }
}
