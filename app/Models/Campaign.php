<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'channel',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at'   => 'date',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CampaignExpense::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }

    public function totalSpend(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    public function linkClicks(): int
    {
        return $this->events()->where('type', 'link_click')->count();
    }

    public function costPerClick(): ?float
    {
        $clicks = $this->linkClicks();
        if ($clicks === 0) return null;
        return round($this->totalSpend() / $clicks, 2);
    }
}
