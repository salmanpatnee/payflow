<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_item_id',
        'stripe_event_id',
        'stripe_event_type',
        'payload',
        'processed_at',
        'processing_status',
        'processing_attempts',
        'processing_error',
        'stripe_response_legacy',
        'status_legacy',
        'error_message_legacy',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'stripe_response_legacy' => 'array',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paymentItem(): BelongsTo
    {
        return $this->belongsTo(PaymentItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }
}
