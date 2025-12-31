<?php

namespace App\Jobs;

use App\Mail\PaymentReceiptMail;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeliverPaymentReceiptJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(
        public PaymentReceipt $receipt
    ) {}

    public function handle(): void
    {
        try {
            $recipientEmail = $this->getRecipientEmail();

            if (! $recipientEmail) {
                Log::warning('No recipient email found for receipt delivery', [
                    'receipt_id' => $this->receipt->id,
                ]);

                $this->receipt->update([
                    'delivery_status' => 'failed',
                    'delivery_error' => 'No recipient email found',
                ]);

                return;
            }

            // Send email with PDF attachment
            Mail::to($recipientEmail)->send(
                new PaymentReceiptMail($this->receipt, $recipientEmail)
            );

            // Update receipt delivery status
            $this->receipt->update([
                'delivery_status' => 'sent',
                'delivered_at' => now(),
            ]);

            Log::info('Payment receipt delivered successfully', [
                'receipt_id' => $this->receipt->id,
                'receipt_number' => $this->receipt->receipt_number,
                'recipient_email' => $recipientEmail,
            ]);
        } catch (\Exception $e) {
            $this->receipt->increment('delivery_attempts');
            $this->receipt->update([
                'delivery_status' => 'failed',
                'delivery_error' => $e->getMessage(),
            ]);

            Log::error('Failed to deliver payment receipt', [
                'receipt_id' => $this->receipt->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function getRecipientEmail(): ?string
    {
        // Try to get email from receipt data
        if (isset($this->receipt->receipt_data['customer_email'])) {
            return $this->receipt->receipt_data['customer_email'];
        }

        // Try to get from payment transaction payload
        $transaction = $this->receipt->paymentTransaction;

        if ($transaction && isset($transaction->payload['data']['object']['receipt_email'])) {
            return $transaction->payload['data']['object']['receipt_email'];
        }

        // Try to get from billing details
        if ($transaction && isset($transaction->payload['data']['object']['charges']['data'][0]['billing_details']['email'])) {
            return $transaction->payload['data']['object']['charges']['data'][0]['billing_details']['email'];
        }

        // For now, use a fallback (this should be improved to get actual customer email)
        return config('mail.from.address');
    }
}
