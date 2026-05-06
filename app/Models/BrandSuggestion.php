<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandSuggestion extends Model
{
    use HasUlids;

    protected $fillable = ['user_id', 'name', 'status'];

    protected function casts(): array
    {
        return ['status' => 'string'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
