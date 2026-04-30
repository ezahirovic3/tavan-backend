<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'top_sizes',
        'bottom_sizes',
        'shoe_sizes',
        'categories',
        'subcategories',
        'cities',
    ];

    protected function casts(): array
    {
        return [
            'top_sizes'    => 'array',
            'bottom_sizes' => 'array',
            'shoe_sizes'   => 'array',
            'categories'   => 'array',
            'subcategories'=> 'array',
            'cities'       => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
