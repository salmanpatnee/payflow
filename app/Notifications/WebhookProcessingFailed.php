<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebhookProcessingFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentTransaction $transaction,
        public string $errorMessage,
        public int $attemptNumber
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Webhook Processing Failed - '.config('app.name'))
            ->greeting('Webhook Processing Failure Alert')
            ->line('A Stripe webhook has failed to process after multiple attempts.')
            ->line('')
            ->line('**Event Details:**')
            ->line('Event ID: '.$this->transaction->stripe_event_id)
            ->line('Event Type: '.$this->transaction->stripe_event_type)
            ->line('Processing Status: '.$this->transaction->processing_status)
            ->line('Attempts: '.$this->attemptNumber)
            ->line('')
            ->line('**Error Message:**')
            ->line($this->errorMessage)
            ->line('')
            ->line('**Payment Item:**')
            ->line('Payment Item ID: '.($this->transaction->payment_item_id ?? 'N/A'))
            ->line('')
            ->action('View Transaction', url('/admin/transactions/'.$this->transaction->id))
            ->line('Please investigate and manually process this webhook if necessary.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'webhook_processing_failed',
            'transaction_id' => $this->transaction->id,
            'stripe_event_id' => $this->transaction->stripe_event_id,
            'stripe_event_type' => $this->transaction->stripe_event_type,
            'error_message' => $this->errorMessage,
            'attempts' => $this->attemptNumber,
            'payment_item_id' => $this->transaction->payment_item_id,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'stripe_event_id' => $this->transaction->stripe_event_id,
            'error_message' => $this->errorMessage,
        ];
    }
}
