<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Services\ReceiptGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeneratePaymentReceiptJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(
        public PaymentTransaction $transaction
    ) {}

    public function handle(ReceiptGenerationService $receiptService): void
    {
        try {
            // Check if payment was successful
            if (! $this->shouldGenerateReceipt()) {
                Log::info('Skipping receipt generation for non-successful payment', [
                    'transaction_id' => $this->transaction->id,
                    'payment_status' => $this->transaction->paymentItem?->status,
                ]);

                return;
            }

            // Generate receipt
            $receipt = $receiptService->generateReceiptForTransaction($this->transaction);

            Log::info('Payment receipt generated successfully', [
                'transaction_id' => $this->transaction->id,
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
            ]);

            // Dispatch delivery job
            DeliverPaymentReceiptJob::dispatch($receipt);
        } catch (\Exception $e) {
            Log::error('Failed to generate payment receipt', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function shouldGenerateReceipt(): bool
    {
        $paymentItem = $this->transaction->paymentItem;

        if (! $paymentItem) {
            return false;
        }

        return $paymentItem->status === 'completed' && $paymentItem->paid_at !== null;
    }
}
