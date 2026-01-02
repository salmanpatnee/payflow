<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentReceiptFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_transaction_id',
        'receipt_number',
        'receipt_data',
        'delivery_status',
        'delivered_at',
        'delivery_attempts',
        'delivery_error',
    ];

    protected function casts(): array
    {
        return [
            'receipt_data' => 'array',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }
}
