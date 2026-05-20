<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignExpense extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'amount',
        'note',
        'spent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'spent_at' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->created_at ??= now());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
