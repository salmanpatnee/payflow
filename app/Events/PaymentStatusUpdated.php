<?php

namespace App\Events;

use App\Models\PaymentItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PaymentItem $paymentItem,
        public string $previousStatus
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('payment-collection.'.$this->paymentItem->payment_collection_id);
    }

    public function broadcastWith(): array
    {
        return [
            'payment_item_id' => $this->paymentItem->id,
            'payment_collection_id' => $this->paymentItem->payment_collection_id,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->paymentItem->status,
            'paid_at' => $this->paymentItem->paid_at?->toIso8601String(),
            'amount' => $this->paymentItem->amount,
            'currency' => $this->paymentItem->currency,
            'name' => $this->paymentItem->name,
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.status.updated';
    }
}
