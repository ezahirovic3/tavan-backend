<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Product extends Model
{
    use HasFactory, HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'price', 'status', 'seller_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'seller_id',
        'brand_id',
        'title',
        'description',
        'root_category',
        'category',
        'subcategory',
        'condition',
        'size',
        'color',
        'material',
        'styles',
        'price',
        'allows_trades',
        'allows_offers',
        'shipping_size',
        'location',
        'pickup_enabled',
        'free_shipping',
        'exact_shipping_price',
        'status',
        'likes',
        'measurements',
        'vintage_status',
        'vintage_era',
        'vintage_notes',
        'vintage_provenance',
        'vintage_reject_reason',
        'vintage_reviewed_by',
        'vintage_reviewed_at',
        'designer_status',
        'designer_brand',
        'designer_notes',
        'designer_reject_reason',
        'designer_reviewed_by',
        'designer_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'price'                => 'decimal:2',
            'allows_trades'        => 'boolean',
            'allows_offers'        => 'boolean',
            'pickup_enabled'       => 'boolean',
            'free_shipping'        => 'boolean',
            'exact_shipping_price' => 'decimal:2',
            'measurements'         => 'array',
            'styles'               => 'array',
            'vintage_reviewed_at'   => 'datetime',
            'designer_reviewed_at'  => 'datetime',
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

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ProductReport::class);
    }

    /**
     * Public-facing scope: only fully active products.
     * pending_review and draft are owner-only and must never appear in public feeds.
     */
    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            // Delete every R2 image file before the DB row is removed.
            // Covers: ProductController@destroy, Filament DeleteBulkAction, any future path.
            $imageService = app(\App\Services\ImageService::class);
            $product->images->each(fn ($img) => $imageService->deleteProductImage($img));

            // Remove any wishlist entries pointing at this product
            $product->wishlistItems()->delete();
        });
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', 'active')
            ->whereHas('seller', fn ($q) => $q->whereNull('deletion_requested_at'));
    }

    /**
     * Owner-visible scope: everything except sold/archived.
     * Used when the authenticated user is fetching their own listings.
     */
    public function scopeVisibleToOwner($query)
    {
        return $query->whereIn('status', ['draft', 'pending_review', 'active', 'reserved']);
    }
}
