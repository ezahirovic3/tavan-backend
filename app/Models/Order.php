<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'offer_id',
        'trade_id',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * First item's product, exposed as a real relation so eager loads,
     * whenLoaded('product') and Filament dot-notation keep working on
     * multi-item orders (backward compat for the mobile app).
     */
    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            OrderItem::class,
            'order_id',   // FK on order_items → orders
            'id',         // key on products
            'id',         // local key on orders
            'product_id', // key on order_items → products
        )->oldest('order_items.id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
