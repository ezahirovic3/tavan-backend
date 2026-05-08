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
            'slug'         => $this->slug,
            'tag'          => $this->tag,
            'title'        => $this->title,
            'excerpt'      => $this->excerpt,
            'date'         => $this->formatted_date,
            'readTime'     => $this->read_time,
            'blocks'       => $this->resolveBlocks(),
            'coverImage'   => $this->cover_image ? Storage::disk('r2')->url($this->cover_image) : null,
            'coverColor'   => $this->cover_color,
            'authorName'   => $this->author_name,
            'authorAvatar' => $this->author_avatar ? Storage::disk('r2')->url($this->author_avatar) : null,
        ];
    }

    /**
     * Resolve R2 file paths for image blocks to full public URLs.
     * Other block types are returned as-is.
     */
    private function resolveBlocks(): array
    {
        if (empty($this->blocks)) {
            return [];
        }

        return collect($this->blocks)
            ->map(function (array $block): array {
                if ($block['type'] === 'image' && ! empty($block['file'])) {
                    $block['url'] = Storage::disk('r2')->url($block['file']);
                    unset($block['file']);
                }
                return $block;
            })
            ->values()
            ->all();
    }
}
