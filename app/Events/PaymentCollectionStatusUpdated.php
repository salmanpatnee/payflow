<?php

namespace App\Events;

use App\Models\PaymentCollection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCollectionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PaymentCollection $paymentCollection,
        public string $previousStatus
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('payment-collection.'.$this->paymentCollection->id);
    }

    public function broadcastWith(): array
    {
        $paymentItems = $this->paymentCollection->paymentItems;

        return [
            'payment_collection_id' => $this->paymentCollection->id,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->paymentCollection->status,
            'name' => $this->paymentCollection->name,
            'total_items' => $paymentItems->count(),
            'completed_items' => $paymentItems->where('status', 'completed')->count(),
            'pending_items' => $paymentItems->where('status', 'pending')->count(),
            'failed_items' => $paymentItems->where('status', 'failed')->count(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.collection.status.updated';
    }
}
