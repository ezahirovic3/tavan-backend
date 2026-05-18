<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogAuthor extends Model
{
    protected $fillable = ['name', 'avatar', 'bio'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (BlogAuthor $author) {
            if ($author->isDirty('avatar') && $author->getOriginal('avatar')) {
                app(\App\Services\ImageService::class)->deleteByUrl($author->getOriginal('avatar'));
            }
        });

        static::deleting(function (BlogAuthor $author) {
            app(\App\Services\ImageService::class)->deleteByUrl($author->avatar);
        });
    }
}
