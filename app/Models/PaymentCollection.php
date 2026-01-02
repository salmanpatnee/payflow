<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentCollection extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentCollectionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'title', // Alias for name
        'description',
        'status',
        'expires_at',
        'admin_user_id',
        'payment_link_token',
        'payment_link_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'payment_link_expires_at' => 'datetime',
        ];
    }

    // Accessor: Allow reading 'title' as alias for 'name'
    public function getTitleAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    // Mutator: Allow setting 'title' which sets 'name'
    public function setTitleAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function clientAccessRecords(): HasMany
    {
        return $this->hasMany(ClientAccessRecord::class);
    }

    public function generatePaymentLink(): string
    {
        $token = Str::random(32);
        $expiresAt = now()->addDays(30);

        $this->update([
            'payment_link_token' => $token,
            'payment_link_expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function getPaymentLinkUrl(): ?string
    {
        if (! $this->payment_link_token) {
            return null;
        }

        if ($this->payment_link_expires_at && $this->payment_link_expires_at->isPast()) {
            return null;
        }

        return route('pay.show', ['token' => $this->payment_link_token]);
    }

    public function isPaymentLinkActive(): bool
    {
        return $this->payment_link_token !== null &&
               (! $this->payment_link_expires_at || $this->payment_link_expires_at->isFuture());
    }

    public function revokePaymentLink(): void
    {
        $this->update([
            'payment_link_token' => null,
            'payment_link_expires_at' => null,
        ]);
    }
}
