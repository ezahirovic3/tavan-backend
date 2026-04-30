<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'buyer_id',
        'seller_id',
        'offered_price',
        'message',
        'status',
        'counter_price',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_price' => 'decimal:2',
            'counter_price' => 'decimal:2',
            'expires_at'    => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
