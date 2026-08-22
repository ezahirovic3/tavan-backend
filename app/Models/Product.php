<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
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
            'followers_notified_at' => 'datetime',
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

    /**
     * Shared catalog filter + sort logic — used by both the global feed
     * (ProductController::index) and a seller's product list
     * (UserController::products). Category, attribute, brand, location,
     * price, and sort params only; search (`q`) and preference-based
     * personalization are feed-specific and stay in ProductController.
     */
    public function scopeApplyFilters($query, Request $request)
    {
        // ── Category filters ───────────────────────────────────────────────
        if ($request->filled('root_category')) {
            $query->where('root_category', $request->root_category);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // subcategory accepts a single value OR an array (from multi-select filters)
        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        } elseif ($request->filled('subcategories')) {
            $query->whereIn('subcategory', (array) $request->subcategories);
        }

        // ── Attribute filters (all accept single value or array) ────────────
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('sizes')) {
            $query->whereIn('size', (array) $request->sizes);
        } elseif ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        if ($request->filled('colors')) {
            $query->whereIn('color', (array) $request->colors);
        } elseif ($request->filled('color')) {
            $query->where('color', $request->color);
        }

        if ($request->filled('materials')) {
            $query->whereIn('material', (array) $request->materials);
        }

        // styles is a JSON array column — any-of match across the selected styles
        if ($request->filled('styles')) {
            $query->where(function ($q) use ($request) {
                foreach ((array) $request->styles as $style) {
                    $q->orWhereJsonContains('styles', $style);
                }
            });
        }

        // ── Brand filter ──────────────────────────────────────────────────
        if ($request->filled('brands')) {
            $query->whereHas('brand', fn ($q) => $q->whereIn('name', (array) $request->brands));
        }

        // ── Location filter ──────────────────────────────────────────────
        if ($request->filled('cities')) {
            $query->whereIn('location', (array) $request->cities);
        } elseif ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        // ── Price range ──────────────────────────────────────────────────
        // Frontend sends priceMin/priceMax → middleware converts to price_min/price_max
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // ── Sorting ──────────────────────────────────────────────────────
        // Frontend sends sortBy → middleware converts to sort_by
        //
        // TODO(bump-feature): 'newest' sorts by updated_at as a stopgap so that
        // editing a listing bumps it back to the top of the feed, since we don't
        // have a real "bump" feature yet. Caveat: this also bumps on non-edit
        // updates (vintage/designer review, admin moderation actions), not just
        // seller edits. Once a proper paid bump feature ships, drop this and go
        // back to created_at (or a dedicated bumped_at column).
        match ($request->input('sort_by', 'newest')) {
            'price_asc', 'priceAsc'   => $query->orderBy('price', 'asc'),
            'price_desc', 'priceDesc' => $query->orderBy('price', 'desc'),
            'oldest'                  => $query->oldest(),
            default                   => $query->orderBy('updated_at', 'desc'),
        };

        return $query;
    }
}
