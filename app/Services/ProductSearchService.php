<?php

namespace App\Services;

class ProductSearchService
{
    // Each inner array is a synonym group. A search term matching any word in a group
    // expands the query to include all words in that group.
    private const SYNONYMS = [
        // Pants / trousers
        ['hlače', 'pantole', 'pantalone', 'farmerke', 'traperice', 'džins', 'jeans', 'tajice', 'helanke', 'legice'],

        // Shorts
        ['šorc', 'šorcevi', 'shorts', 'kratke hlače', 'bermude', 'bermuda'],

        // Skirt
        ['suknja', 'suknje'],

        // Dress
        ['haljina', 'haljine', 'obleka', 'fustana'],

        // T-shirt
        ['majica', 'majice', 't-shirt', 'tshirt', 'tiširt', 'tee'],

        // Shirt (button-up)
        ['košulja', 'košulje', 'shirt', 'bluza', 'bluze'],

        // Hoodie / sweatshirt
        ['dukserica', 'dukserice', 'duks', 'hoodie', 'hoodi', 'sweatshirt'],

        // Sweater / knit
        ['džemper', 'džemperi', 'pulovjer', 'pulover', 'vesta', 'pletivo', 'sweater', 'knit', 'kardigan', 'kardigani', 'cardigan'],

        // Jacket
        ['jakna', 'jakne', 'blejzer', 'blejzeri', 'blazer', 'sako', 'sakoi'],

        // Coat
        ['kaput', 'kaputi', 'mantil', 'mantili', 'coat'],

        // Vest / waistcoat
        ['prsluk', 'prsluci', 'vest', 'gilet'],

        // Puffer / down jacket
        ['puffer', 'pufer', 'pernata jakna', 'zimska jakna'],

        // Tracksuit / sweatpants
        ['trenerka', 'trenerke', 'tracksuit', 'jogging'],

        // Swimwear
        ['kupaći', 'kupaće', 'bikini', 'kupaći kostim', 'swimsuit', 'swimwear'],

        // Sneakers / trainers
        ['patike', 'patika', 'tenisice', 'tenisica', 'superge', 'sneakers', 'sneaker', 'trainers'],

        // Shoes (general)
        ['cipele', 'cipela', 'shoes', 'shoe'],

        // Boots
        ['čizme', 'čizma', 'boots', 'boot', 'gležnjače'],

        // Sandals / flip-flops
        ['sandale', 'sandala', 'japanke', 'flip flop', 'flip-flop', 'sandals'],

        // Heels
        ['štikle', 'štikla', 'potpetice', 'heels', 'pumps'],

        // Bag / purse
        ['torba', 'torbe', 'tašna', 'tašne', 'bag', 'purse', 'handbag'],

        // Backpack
        ['ruksak', 'ruksaci', 'backpack'],

        // Belt
        ['kaiš', 'kaiševi', 'remen', 'remeni', 'belt'],

        // Hat / cap
        ['šešir', 'šeširi', 'kapa', 'kape', 'hat', 'cap', 'beanie', 'bejzbolka'],

        // Scarf
        ['šal', 'šalovi', 'marama', 'marame', 'scarf'],

        // Gloves
        ['rukavice', 'rukavica', 'gloves'],

        // Sunglasses
        ['naočale', 'naočare', 'sunčane naočale', 'sunglasses', 'shades'],

        // Jewellery
        ['nakit', 'narukvica', 'narukvice', 'ogrlica', 'ogrlice', 'naušnice', 'prsten', 'prstenje', 'jewellery', 'jewelry'],

        // Watch
        ['sat', 'satovi', 'ručni sat', 'watch'],

        // Wallet
        ['novčanik', 'novčanici', 'wallet'],

        // Tie
        ['kravata', 'kravate', 'tie', 'leptir mašna', 'mašna'],

        // Suit / formal
        ['odijelo', 'odijela', 'smoking', 'suit'],
    ];

    // Maps search terms to the category key stored in the products table.
    // When matched, products are also fetched by category (not just by title/description LIKE).
    // This ensures "hlače" surfaces listings categorised under bottoms even if the title says "farmerke".
    private const CATEGORY_INTENTS = [
        'tops' => [
            'majica', 'majice', 'bluza', 'bluze', 'košulja', 'košulje',
            'dukserica', 'dukserice', 'duks', 'hoodie',
            'džemper', 'džemperi', 'kardigan', 'kardigani',
            'gornji dio', 'gornji dijelovi',
        ],
        'bottoms' => [
            'hlače', 'pantole', 'pantalone', 'farmerke', 'traperice', 'džins', 'jeans',
            'tajice', 'helanke', 'legice',
            'suknja', 'suknje',
            'šorc', 'šorcevi', 'bermude',
            'trenerka', 'trenerke',
            'kombinezon', 'kombinezoni',
            'donji dio', 'donji dijelovi',
        ],
        'jackets' => [
            'jakna', 'jakne', 'kaput', 'kaputi', 'mantil', 'mantili',
            'blejzer', 'sako', 'sakoi', 'prsluk', 'prsluci',
            'bomber', 'puffer', 'pufer',
        ],
        'dresses' => [
            'haljina', 'haljine', 'obleka', 'fustana',
        ],
        'shoes' => [
            'cipele', 'patike', 'tenisice', 'čizme', 'štikle',
            'sandale', 'papuče', 'superge', 'sneakers',
            'obuća', 'obuca',
        ],
        'occasion' => [
            'odijelo', 'odijela', 'smoking',
            'svečano', 'svecano', 'svadba', 'vjenčanje', 'vjencanje',
            'poslovni', 'poslovna', 'formalno',
        ],
        'bags' => [
            'torba', 'torbe', 'tašna', 'tašne',
            'ruksak', 'ruksaci', 'torbica', 'torbice',
        ],
        'jewelry' => [
            'nakit', 'narukvica', 'narukvice', 'ogrlica', 'ogrlice',
            'naušnice', 'prsten', 'prstenje', 'broš', 'broševi',
        ],
        'accessories' => [
            'naočale', 'naočare', 'kapa', 'kape', 'šešir', 'šeširi',
            'šal', 'šalovi', 'marama', 'marame',
            'kaiš', 'kaiševi', 'remen',
            'rukavice', 'sat', 'satovi', 'novčanik', 'novčanici',
        ],
    ];

    /**
     * Expand a search term into its full synonym group.
     * Normalises diacritics so "hlace" matches "hlače".
     * If no exact match, falls back to the closest synonym group within
     * Levenshtein distance 2 (catches fat-finger typos like "trba" → "torba").
     * Returns an array with the original query when no match is found.
     */
    public static function expandTerms(string $q): array
    {
        $normalized = self::normalize($q);

        foreach (self::SYNONYMS as $group) {
            foreach ($group as $term) {
                if (self::normalize($term) === $normalized) {
                    return $group;
                }
            }
        }

        // Fuzzy fallback: find the synonym group whose canonical term is closest.
        // Only triggers for short-ish queries to avoid false positives on long phrases.
        if (mb_strlen($normalized) >= 3) {
            $bestGroup    = null;
            $bestDistance = PHP_INT_MAX;

            foreach (self::SYNONYMS as $group) {
                foreach ($group as $term) {
                    $distance = levenshtein($normalized, self::normalize($term));
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestGroup    = $group;
                    }
                }
            }

            if ($bestDistance <= 2 && $bestGroup !== null) {
                return $bestGroup;
            }
        }

        return [mb_strtolower(trim($q))];
    }

    /**
     * Detect whether the query maps to a known product category key.
     * Returns a category key (e.g. "bottoms", "shoes") or null.
     */
    public static function detectCategoryIntent(string $q): ?string
    {
        $normalized = self::normalize($q);

        foreach (self::CATEGORY_INTENTS as $categoryKey => $terms) {
            foreach ($terms as $term) {
                if (self::normalize($term) === $normalized) {
                    return $categoryKey;
                }
            }
        }

        return null;
    }

    /**
     * Strip Bosnian/Croatian diacritics and lowercase.
     * Allows "hlace", "hlača", "sorca" etc. to match their accented equivalents.
     */
    private static function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));

        $map = [
            'č' => 'c', 'ć' => 'c',
            'š' => 's',
            'ž' => 'z',
            'đ' => 'd',
            'dž' => 'dz',
        ];

        return strtr($s, $map);
    }
}
