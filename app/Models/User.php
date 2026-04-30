<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'location',
        'bio',
        'phone',
        'phone_verified_at',
        'is_verified',
        'profile_setup_done',
        'feed_setup_done',
        'first_listing_coach_seen',
        'first_draft_coach_seen',
        'notifications_enabled',
        'rating',
        'total_reviews',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_active_at'    => 'datetime',
            'password'          => 'hashed',
            'is_verified'               => 'boolean',
            'profile_setup_done'        => 'boolean',
            'feed_setup_done'           => 'boolean',
            'first_listing_coach_seen'  => 'boolean',
            'first_draft_coach_seen'    => 'boolean',
            'notifications_enabled'     => 'boolean',
            'rating'            => 'decimal:2',
        ];
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
}
