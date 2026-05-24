<?php

namespace App\Filament\Resources\BlogPosts\Concerns;

/**
 * Bridges Filament Builder's nested `{type, data: {...}}` block format with
 * the flat `{type, text/url/...}` format stored in the `blocks` column and
 * consumed by the API resource + landing-page renderer.
 */
trait TransformsBlocks
{
    protected function blocksFlatToNested(array $data): array
    {
        if (! isset($data['blocks']) || ! is_array($data['blocks'])) {
            return $data;
        }

        $data['blocks'] = collect($data['blocks'])
            ->map(function (array $block): array {
                $type = $block['type'] ?? null;
                if (! $type) {
                    return $block;
                }
                $fields = $block;
                unset($fields['type']);
                return ['type' => $type, 'data' => $fields];
            })
            ->all();

        return $data;
    }

    protected function blocksNestedToFlat(array $data): array
    {
        if (! isset($data['blocks']) || ! is_array($data['blocks'])) {
            return $data;
        }

        $data['blocks'] = collect($data['blocks'])
            ->map(function (array $block): array {
                $type = $block['type'] ?? null;
                if (! $type) {
                    return $block;
                }
                return array_merge(['type' => $type], $block['data'] ?? []);
            })
            ->values()
            ->all();

        return $data;
    }
}
