<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'slug', 'logo_url', 'is_active', 'is_other', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_other'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // When a logo is replaced in Filament, delete the old R2 file
        static::saving(function (Brand $brand) {
            if ($brand->isDirty('logo_url') && $brand->getOriginal('logo_url')) {
                app(\App\Services\ImageService::class)->deleteByUrl($brand->getOriginal('logo_url'));
            }
        });

        // When a brand is deleted, remove its logo from R2
        static::deleting(function (Brand $brand) {
            app(\App\Services\ImageService::class)->deleteByUrl($brand->logo_url);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeOther($query)
    {
        return $query->where('is_other', true);
    }
}
