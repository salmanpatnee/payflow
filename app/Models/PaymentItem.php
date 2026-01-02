<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentItem extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_collection_id',
        'name',
        'description',
        'price',
        'quantity',
        'type',
        'sort_order',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'status' => 'string',
            'currency' => 'string',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        // Automatically set amount from price if not provided
        static::creating(function ($paymentItem) {
            if (is_null($paymentItem->amount) && ! is_null($paymentItem->price)) {
                $paymentItem->amount = $paymentItem->price;
            }
        });
    }

    public function paymentCollection(): BelongsTo
    {
        return $this->belongsTo(PaymentCollection::class);
    }
}
