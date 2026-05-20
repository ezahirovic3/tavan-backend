<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only projection of share_views grouped by entity.
 * Primary key is entity_id so Filament's pagination tiebreaker
 * (ORDER BY pk) stays within the GROUP BY clause.
 */
class ShareViewSummary extends Model
{
    protected $table = 'share_views';

    protected $primaryKey = 'entity_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;
}
