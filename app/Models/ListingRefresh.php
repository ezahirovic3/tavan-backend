<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per "Osvježi oglas" action.
 *
 * Write-only log: nothing reads this for enforcement, because refresh is
 * deliberately unmetered (see App\Services\ListingRefreshService). It exists so
 * renewal usage is measurable — input for pricing the future paid bump — and so
 * scripted abuse would be visible if it ever appeared.
 */
class ListingRefresh extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
