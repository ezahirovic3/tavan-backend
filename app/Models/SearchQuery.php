<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    protected $fillable = ['query', 'occurrences', 'last_searched_at'];

    protected $casts = [
        'last_searched_at' => 'datetime',
    ];

    public static function record(string $query): void
    {
        $normalized = mb_strtolower(trim($query));

        if ($normalized === '') {
            return;
        }

        self::upsert(
            [['query' => $normalized, 'occurrences' => 1, 'last_searched_at' => now()]],
            ['query'],
            ['occurrences' => \Illuminate\Support\Facades\DB::raw('occurrences + 1'), 'last_searched_at' => now()],
        );
    }
}
