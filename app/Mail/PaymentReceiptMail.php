<?php

namespace App\Mail;

use App\Models\PaymentReceipt;
use App\Services\ReceiptGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentReceipt $receipt,
        public string $recipientEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - '.$this->receipt->receipt_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'receipt' => $this->receipt,
                'receiptData' => $this->receipt->receipt_data,
            ],
        );
    }

    public function attachments(): array
    {
        $receiptService = app(ReceiptGenerationService::class);

        $pdfContent = $receiptService->generatePdf($this->receipt);
        $filename = $receiptService->getReceiptFilename($this->receipt);

        return [
            Attachment::fromData(fn () => $pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
