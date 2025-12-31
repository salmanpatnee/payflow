<?php

namespace App\Services;

use App\Models\PaymentItem;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\View;

class ReceiptGenerationService
{
    public function generateReceiptForTransaction(PaymentTransaction $transaction): PaymentReceipt
    {
        // Check if receipt already exists
        if ($transaction->receipt) {
            return $transaction->receipt;
        }

        $paymentItem = $transaction->paymentItem;

        if (! $paymentItem) {
            throw new \Exception('Payment item not found for transaction');
        }

        // Generate receipt number
        $receiptNumber = $this->generateReceiptNumber();

        // Prepare receipt data
        $receiptData = $this->prepareReceiptData($transaction, $paymentItem);

        // Create receipt record
        $receipt = PaymentReceipt::create([
            'payment_transaction_id' => $transaction->id,
            'receipt_number' => $receiptNumber,
            'receipt_data' => $receiptData,
            'delivery_status' => 'pending',
        ]);

        return $receipt;
    }

    public function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    protected function prepareReceiptData(PaymentTransaction $transaction, PaymentItem $paymentItem): array
    {
        $paymentCollection = $paymentItem->paymentCollection;

        return [
            'receipt_number' => $this->generateReceiptNumber(),
            'transaction_id' => $transaction->id,
            'transaction_date' => $transaction->created_at->format('Y-m-d H:i:s'),
            'payment_item' => [
                'id' => $paymentItem->id,
                'name' => $paymentItem->name,
                'description' => $paymentItem->description,
                'quantity' => $paymentItem->quantity,
                'price' => $paymentItem->price,
                'amount' => $paymentItem->amount,
                'currency' => $paymentItem->currency,
                'paid_at' => $paymentItem->paid_at?->format('Y-m-d H:i:s'),
            ],
            'payment_collection' => [
                'id' => $paymentCollection->id,
                'name' => $paymentCollection->name,
                'description' => $paymentCollection->description,
            ],
            'stripe_event_id' => $transaction->stripe_event_id,
            'payment_method' => $this->extractPaymentMethod($transaction),
        ];
    }

    protected function extractPaymentMethod(PaymentTransaction $transaction): ?string
    {
        $payload = $transaction->payload;

        if (isset($payload['data']['object']['payment_method_details']['type'])) {
            return $payload['data']['object']['payment_method_details']['type'];
        }

        if (isset($payload['data']['object']['payment_method_types'][0])) {
            return $payload['data']['object']['payment_method_types'][0];
        }

        return null;
    }

    public function generatePdf(PaymentReceipt $receipt): string
    {
        $html = View::make('receipts.payment-receipt', [
            'receipt' => $receipt,
            'receiptData' => $receipt->receipt_data,
        ])->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-right', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm');

        return $pdf->output();
    }

    public function getReceiptFilename(PaymentReceipt $receipt): string
    {
        return "receipt-{$receipt->receipt_number}.pdf";
    }
}
