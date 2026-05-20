<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ShareView extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'platform',
        'outcome',
        'referrer',
        'referrer_platform',
    ];
}
