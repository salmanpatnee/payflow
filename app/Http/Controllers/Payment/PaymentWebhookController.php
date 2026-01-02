<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private StripePaymentService $stripePaymentService
    ) {}

    /**
     * Handle incoming Stripe webhook events
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            \Log::warning('Webhook received without signature');

            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            // Verify signature and process webhook
            $this->stripePaymentService->handleWebhook(
                json_decode($payload, true),
                $signature
            );

            return response()->json(['status' => 'success'], 200);
        } catch (\UnexpectedValueException $e) {
            // Signature verification failed
            \Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            // Other errors
            \Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}
