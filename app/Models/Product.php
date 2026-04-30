<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'seller_id',
        'brand_id',
        'brand_custom',
        'title',
        'description',
        'root_category',
        'category',
        'subcategory',
        'condition',
        'size',
        'color',
        'material',
        'price',
        'allows_trades',
        'allows_offers',
        'shipping_size',
        'location',
        'status',
        'likes',
        'measurements',
    ];

    protected function casts(): array
    {
        return [
            'price'         => 'decimal:2',
            'allows_trades' => 'boolean',
            'allows_offers' => 'boolean',
            'measurements'  => 'array',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
