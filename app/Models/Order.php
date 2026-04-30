<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'product_id',
        'offer_id',
        'subtotal',
        'discount',
        'shipping_cost',
        'total',
        'payment_method',
        'delivery_method',
        'status',
        'shipping_name',
        'shipping_street',
        'shipping_city',
        'shipping_phone',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'      => 'decimal:2',
            'discount'      => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
