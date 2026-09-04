<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Brand extends Model
{
    use HasFactory, HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = ['name', 'slug', 'is_active', 'is_other', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_other'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            // Insert-at-position: shift other brands down when sort_order changes
            if ($brand->isDirty('sort_order') && $brand->sort_order !== null) {
                $query = static::where('sort_order', '>=', $brand->sort_order);
                if ($brand->exists) {
                    $query->where($brand->getKeyName(), '!=', $brand->getKey());
                }
                $query->increment('sort_order');
            }
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

    /**
     * Fold a brand name to a comparable identity: lowercase, drop apostrophes
     * ("Levi's" == "levis"), turn separators (. - _ /) into spaces so
     * "isabel-marant" == "Isabel Marant", then collapse whitespace.
     * Mirrors normalizeBrandName() in the mobile brandDesigner screen.
     */
    public static function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(["'", '’', '`'], '', $value);
        $value = str_replace(['.', '-', '_', '/'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Find a catalogue brand whose name matches `$name` once normalised.
     * Defaults to the same scope the mobile brand list uses (active, non-"Ostali");
     * pass $activeOnly = false to match against every brand (e.g. before creating
     * one, to avoid a near-duplicate of a disabled brand).
     */
    public static function findByNormalizedName(string $name, bool $activeOnly = true): ?self
    {
        $target = static::normalizeName($name);

        if ($target === '') {
            return null;
        }

        return static::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true)->where('is_other', false))
            ->get(['id', 'name'])
            ->first(fn (self $brand) => static::normalizeName($brand->name) === $target);
    }
}
