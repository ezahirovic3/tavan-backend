<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasUlids;

    protected $fillable = [
        'title',
        'body',
        'target_group',
        'target_value',
        'created_by',
        'sent_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'    => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /**
     * Resolve the query of users targeted by this announcement.
     */
    public function targetedUsersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::where('is_system', false);

        return match ($this->target_group) {
            'verified'               => $query->where('is_verified', true),
            'city'                   => $query->where('location', $this->target_value),
            'listings_require_review'=> $query->where('listings_require_review', true),
            // ≥1 completed order (either side) or ≥1 published listing — the
            // "has actually used the app" gate for the one-off review-growth
            // push, see docs/roadmap.md 1.5.3 in tavan-mobile.
            'active_users'           => $query->where(fn ($q) => $q
                ->whereHas('ordersAsBuyer', fn ($o) => $o->where('status', 'completed'))
                ->orWhereHas('ordersAsSeller', fn ($o) => $o->where('status', 'completed'))
                ->orWhereHas('products', fn ($p) => $p->where('status', '!=', 'draft'))),
            default                  => $query, // 'all'
        };
    }
}
