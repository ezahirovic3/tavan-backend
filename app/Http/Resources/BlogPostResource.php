<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug'        => $this->slug,
            'tag'         => $this->tag,
            'title'       => $this->title,
            'excerpt'     => $this->excerpt,
            'date'        => $this->formatted_date,
            'readTime'    => $this->read_time,
            'content'     => $this->content,
            'coverImage'   => $this->cover_image ? Storage::disk('r2')->url($this->cover_image) : null,
            'coverColor'   => $this->cover_color,
            'authorName'   => $this->author_name,
            'authorAvatar' => $this->author_avatar ? Storage::disk('r2')->url($this->author_avatar) : null,
        ];
    }
}
