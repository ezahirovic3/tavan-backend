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
            'readTime'     => $this->formatReadTime(),
            'blocks'       => $this->resolveBlocks(),
            'coverImage'   => $this->cover_image ? Storage::disk('r2')->url($this->cover_image) : null,
            'coverColor'   => $this->cover_color,
            'authorName'   => $this->author?->name,
            'authorAvatar' => $this->author?->avatar ? Storage::disk('r2')->url($this->author->avatar) : null,
        ];
    }

    /**
     * Always returns "X min" — handles both legacy "5 min" strings and
     * newer numeric values saved by the Filament form (which renders the
     * "min" suffix as UI only, not as part of the stored value).
     */
    private function formatReadTime(): ?string
    {
        if ($this->read_time === null || $this->read_time === '') {
            return null;
        }
        $value = trim((string) $this->read_time);
        return str_contains($value, 'min') ? $value : "{$value} min";
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
