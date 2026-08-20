<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasUlids, LogsActivity, Notifiable;

    /** Memoized block list — native PHP property so Eloquent doesn't treat it as a DB attribute. */
    protected ?array $cachedBlockedUserIds = null;

    protected static function booting(): void
    {
        static::creating(function (self $user) {
            $user->listings_require_review ??= config('tavan.listings_require_review');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'is_verified', 'listings_require_review'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'location',
        'bio',
        'phone',
        'email_verified_at',
        'phone_verified_at',
        'is_verified',
        'is_vintage_seller',
        'is_designer_reseller',
        'is_designer_maker',
        'is_founding_seller',
        'role',
        'profile_setup_done',
        'feed_setup_done',
        'first_listing_coach_seen',
        'first_draft_coach_seen',
        'notifications_enabled',
        'notify_messages',
        'notify_orders',
        'notify_activity',
        'notify_price_drops',
        'notify_announcements',
        'notify_brand_suggestions',
        'rating',
        'total_reviews',
        'last_active_at',
        'google_id',
        'apple_id',
        'listings_require_review',
        'is_system',
        'is_anonymized',
        'acquired_via_campaign_id',
        'deletion_requested_at',
        'banned_until',
        'ban_reason',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'phone_verified_at'      => 'datetime',
            'last_active_at'         => 'datetime',
            'deletion_requested_at'  => 'datetime',
            'banned_until'           => 'datetime',
            'password'          => 'hashed',
            'is_verified'               => 'boolean',
            'is_vintage_seller'         => 'boolean',
            'is_designer_reseller'      => 'boolean',
            'is_designer_maker'         => 'boolean',
            'is_founding_seller'        => 'boolean',
            'profile_setup_done'        => 'boolean',
            'feed_setup_done'           => 'boolean',
            'first_listing_coach_seen'  => 'boolean',
            'first_draft_coach_seen'    => 'boolean',
            'notifications_enabled'       => 'boolean',
            'notify_messages'            => 'boolean',
            'notify_orders'              => 'boolean',
            'notify_activity'            => 'boolean',
            'notify_price_drops'         => 'boolean',
            'notify_announcements'       => 'boolean',
            'listings_require_review'    => 'boolean',
            'is_system'                  => 'boolean',
            'is_anonymized'              => 'boolean',
            'rating'            => 'decimal:2',
        ];
    }

    public function isBanned(): bool
    {
        return $this->banned_until !== null && $this->banned_until->isFuture();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function brandSuggestions(): HasMany
    {
        return $this->hasMany(BrandSuggestion::class);
    }

    public function ordersAsBuyer(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function ordersAsSeller(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blocksMade(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /** Users who follow this user. Returns User models directly (via the `follows` pivot), paginatable as-is. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id');
    }

    /** Users this user follows. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
    }

    /**
     * Returns all user IDs that are in a block relationship with this user —
     * either this user blocked them, or they blocked this user.
     * Used to filter products, conversations, and profiles.
     *
     * Memoized per model instance so multiple calls within the same request
     * only hit the DB once.
     */
    public function blockedUserIds(): array
    {
        if ($this->cachedBlockedUserIds !== null) {
            return $this->cachedBlockedUserIds;
        }

        $this->cachedBlockedUserIds = UserBlock::where('blocker_id', $this->id)
            ->orWhere('blocked_id', $this->id)
            ->get()
            ->map(fn ($b) => $b->blocker_id === $this->id ? $b->blocked_id : $b->blocker_id)
            ->toArray();

        return $this->cachedBlockedUserIds;
    }

    /**
     * Revoke this user's Sanctum tokens, busting each one's Redis cache
     * entry first.
     *
     * $user->tokens()->delete() is a bulk delete through the query builder,
     * which bypasses Eloquent model events entirely — the deleted() listener
     * on CachedPersonalAccessToken (which forgets the Redis cache) never
     * fires for it. Left alone, a just-revoked token can keep authenticating
     * from a stale cache entry for up to 5 more minutes (PHP-LARAVEL-14).
     * Always revoke tokens through this method instead of calling
     * ->tokens()->delete() directly.
     *
     * @param  (callable(\Illuminate\Database\Eloquent\Relations\MorphMany): \Illuminate\Database\Eloquent\Relations\MorphMany)|null  $scope
     *         Optional query customization, e.g. fn ($q) => $q->where('name', 'mobile').
     */
    public function revokeTokens(?callable $scope = null): void
    {
        $ids = ($scope ? $scope($this->tokens()) : $this->tokens())->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $ids->each(fn ($id) => Cache::forget("sanctum_token_{$id}"));

        $this->tokens()->whereIn('id', $ids)->delete();
    }
}
