<?php

namespace App\Support;

/**
 * Helpers for the inline-link syntax used inside blog `paragraph` blocks.
 *
 * Links are stored in the text itself as a minimal Markdown subset:
 *
 *     Više o tome u [vodiču o fotografisanju](/blog/kako-fotografisati-odjecu-za-prodaju).
 *
 * The text stays plain data in the `blocks` JSON — no HTML is stored or
 * returned by the API — so every client (Next.js landing, React Native app)
 * parses the same syntax and renders it with its own components.
 */
class BlockLinks
{
    /**
     * Matches [visible text](href). Href may not contain spaces or ')'.
     */
    public const PATTERN = '/\[([^\]\n]+)\]\(([^)\s]+)\)/';

    /**
     * Every inline link found in a paragraph's text.
     *
     * @return array<int, array{text: string, url: string}>
     */
    public static function extract(string $text): array
    {
        if ($text === '' || ! preg_match_all(self::PATTERN, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(
            static fn (array $m): array => ['text' => $m[1], 'url' => $m[2]],
            $matches
        );
    }

    /**
     * Allow only hrefs we are willing to render as an anchor.
     *
     * Accepted: site-relative paths (/blog/...), http(s), mailto and tel.
     * Everything else — most importantly javascript: and data: — is rejected.
     */
    public static function isSafeHref(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        // Reject control characters and whitespace smuggled into the scheme,
        // e.g. "java\nscript:alert(1)".
        if (preg_match('/[\x00-\x20\x7F]/', $url)) {
            return false;
        }

        // Site-relative path, but not protocol-relative ("//evil.com").
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        // Anchor within the post.
        if (str_starts_with($url, '#')) {
            return strlen($url) > 1;
        }

        foreach (['https://', 'http://', 'mailto:', 'tel:'] as $scheme) {
            if (stripos($url, $scheme) === 0) {
                return strlen($url) > strlen($scheme);
            }
        }

        return false;
    }

    /**
     * True when the link points somewhere on tavan.store rather than off-site.
     */
    public static function isInternal(string $url): bool
    {
        return str_starts_with($url, '/') || str_starts_with($url, '#');
    }

    /**
     * Flatten every URL used across a post's blocks — inline paragraph links
     * plus `link` (CTA) blocks. Useful for a link-checking command.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    public static function fromBlocks(array $blocks): array
    {
        $urls = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'paragraph' && is_string($block['text'] ?? null)) {
                foreach (self::extract($block['text']) as $link) {
                    $urls[] = $link['url'];
                }
            }

            if ($type === 'link' && is_string($block['url'] ?? null)) {
                $urls[] = $block['url'];
            }
        }

        return array_values(array_unique($urls));
    }
}
