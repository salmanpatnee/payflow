<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'items' => $this->paymentItems->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'amount' => $item->amount,
                'currency' => $item->currency,
                'status' => $item->status,
                'stripe_payment_intent_id' => $item->stripe_payment_intent_id,
                'paid_at' => $item->paid_at?->toIso8601String(),
                'created_at' => $item->created_at->toIso8601String(),
                'updated_at' => $item->updated_at->toIso8601String(),
            ]),
            'summary' => [
                'total_items' => $this->paymentItems->count(),
                'completed_items' => $this->paymentItems->where('status', 'completed')->count(),
                'pending_items' => $this->paymentItems->where('status', 'pending')->count(),
                'processing_items' => $this->paymentItems->where('status', 'processing')->count(),
                'failed_items' => $this->paymentItems->where('status', 'failed')->count(),
                'total_amount' => $this->paymentItems->sum('amount'),
                'paid_amount' => $this->paymentItems->where('status', 'completed')->sum('amount'),
                'completion_percentage' => $this->paymentItems->count() > 0
                    ? round(($this->paymentItems->where('status', 'completed')->count() / $this->paymentItems->count()) * 100, 2)
                    : 0,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
