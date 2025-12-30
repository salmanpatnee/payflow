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
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function paymentCollection(): BelongsTo
    {
        return $this->belongsTo(PaymentCollection::class);
    }
}
