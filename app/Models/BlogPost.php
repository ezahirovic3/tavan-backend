<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class BlogPost extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'is_published', 'published_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
    protected $fillable = [
        'title',
        'slug',
        'tag',
        'excerpt',
        'content',
        'blocks',
        'cover_image',
        'cover_color',
        'author_name',
        'author_avatar',
        'read_time',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published'  => 'boolean',
        'published_at'  => 'datetime',
        'blocks'        => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (BlogPost $post) {
            if ($post->is_published && ! $post->published_at) {
                $post->published_at = now();
            }

            if (! $post->is_published) {
                $post->published_at = null;
            }

            // When cover_image or author_avatar is replaced in Filament, remove the old R2 file
            $imageService = app(\App\Services\ImageService::class);

            foreach (['cover_image', 'author_avatar'] as $field) {
                if ($post->isDirty($field) && $post->getOriginal($field)) {
                    $imageService->deleteByUrl($post->getOriginal($field));
                }
            }
        });

        static::deleting(function (BlogPost $post) {
            $imageService = app(\App\Services\ImageService::class);

            // Cover + author avatar
            $imageService->deleteByUrl($post->cover_image);
            $imageService->deleteByUrl($post->author_avatar);

            // Inline images embedded in the blocks JSON array
            foreach ($post->blocks ?? [] as $block) {
                if (($block['type'] ?? null) === 'image' && ! empty($block['file'])) {
                    $imageService->deleteByUrl($block['file']);
                }
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    // Returns formatted Bosnian date, e.g. "2 maj 2026"
    public function getFormattedDateAttribute(): string
    {
        if (! $this->published_at) {
            return '';
        }

        $months = [
            1  => 'januar',  2  => 'februar', 3  => 'mart',
            4  => 'april',   5  => 'maj',      6  => 'juni',
            7  => 'juli',    8  => 'august',   9  => 'septembar',
            10 => 'oktobar', 11 => 'novembar', 12 => 'decembar',
        ];

        return $this->published_at->day . ' ' . $months[$this->published_at->month] . ' ' . $this->published_at->year;
    }
}
