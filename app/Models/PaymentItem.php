<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentItem extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_collection_id',
        'description',
        'amount',
        'status',
        'stripe_payment_intent_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function paymentCollection(): BelongsTo
    {
        return $this->belongsTo(PaymentCollection::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
