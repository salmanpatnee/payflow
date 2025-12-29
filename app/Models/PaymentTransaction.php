<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payment_item_id',
        'stripe_response',
        'status',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stripe_response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function paymentItem(): BelongsTo
    {
        return $this->belongsTo(PaymentItem::class);
    }
}
