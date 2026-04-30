<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'offered_product_id',
        'buyer_id',
        'seller_id',
        'message',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function offeredProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'offered_product_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
