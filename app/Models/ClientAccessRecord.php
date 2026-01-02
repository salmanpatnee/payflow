<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccessRecord extends Model
{
    protected $fillable = [
        'payment_collection_id',
        'client_name',
        'client_email',
        'access_token',
        'ip_address',
        'accessed_at',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function paymentCollection(): BelongsTo
    {
        return $this->belongsTo(PaymentCollection::class);
    }
}
