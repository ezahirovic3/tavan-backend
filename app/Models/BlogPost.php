<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'tag',
        'excerpt',
        'content',
        'cover_image',
        'cover_color',
        'read_time',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published'  => 'boolean',
        'published_at'  => 'datetime',
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
