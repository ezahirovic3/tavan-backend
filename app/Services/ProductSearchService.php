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
        ['čizme', 'čizma', 'boots', 'boot', 'gležnjače', 'kaubojke'],

        // Sandals / flip-flops
        ['sandale', 'sandala', 'japanke', 'flip flop', 'flip-flop', 'sandals'],

        // Heels
        ['štikle', 'štikla', 'potpetice', 'heels', 'pumps', 'salonke'],

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

        // Modest fashion
        ['abaja', 'abaya', 'hidžab', 'hijab', 'mahrama', 'šalvare', 'šarvale', 'nikab', 'niqab', 'himar', 'tunika', 'tunike', 'kimono', 'pokrivene'],

        // Corset
        ['korzet', 'korzeti', 'korset', 'corset'],

        // Windbreaker
        ['šuškavac', 'šuškavci', 'vjetrovka', 'windbreaker'],

        // Two-piece sets
        ['komplet', 'kompleti', 'dvodijelni', 'set'],

        // Underwear
        ['tange', 'tangice', 'tanga', 'gaćice', 'donje rublje'],

        // Slippers
        ['papuče', 'papuča', 'natikače', 'slippers'],
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
            'bomber', 'puffer', 'pufer', 'šuškavac', 'vjetrovka',
        ],
        'dresses' => [
            'haljina', 'haljine', 'obleka', 'fustana',
            'abaja', 'abaya', 'tunika', 'tunike', 'kimono',
        ],
        'shoes' => [
            'cipele', 'patike', 'tenisice', 'čizme', 'štikle',
            'sandale', 'papuče', 'superge', 'sneakers',
            'obuća', 'obuca', 'kaubojke', 'salonke',
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

    // Tokens skipped entirely — they match everything or nothing useful and,
    // AND-ed with the real tokens, can only hurt the result set.
    private const STOPWORDS = ['za', 'i', 'u', 'na', 'sa', 's', 'od', 'do', 'vel'];

    /**
     * Split a query into searchable tokens. Multi-word queries are matched
     * token-by-token (AND), not as an exact phrase.
     */
    public static function tokenize(string $q): array
    {
        $tokens = preg_split('/[\s,]+/', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $tokens,
            fn (string $t) => mb_strlen($t) >= 2 && ! in_array(self::normalize($t), self::STOPWORDS, true),
        ));
    }

    /**
     * Strip apostrophe variants so "levis" matches the brand "Levi's".
     * Applied to the query side; the SQL side mirrors it with REPLACE().
     */
    public static function stripApostrophes(string $s): string
    {
        return str_replace(["'", '’', '`'], '', $s);
    }

    /**
     * Expand a search term into its full synonym group.
     * Normalises diacritics so "hlace" matches "hlače".
     * If no exact match, falls back to the closest synonym group by
     * Levenshtein distance (catches fat-finger typos like "trba" → "torba").
     * Returns an array with the original query when no match is found.
     *
     * With $stemFallback (used for multi-token queries), an unmatched term is
     * reduced to a crude stem — trailing vowels stripped — so adjective
     * agreement still matches: "duge" also finds "duga"/"dugih". Single-token
     * queries skip this to keep brand searches precise ("nike" must not
     * become "nik" and match "tunika").
     */
    public static function expandTerms(string $q, bool $stemFallback = false): array
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
        // Length guards keep short brand names (e.g. "zara", "h&m") from
        // accidentally matching a synonym group: 4-char terms may differ by 1
        // ("trba" → "torba"), 5+ chars by 2.
        $length      = mb_strlen($normalized);
        $maxDistance = match (true) {
            $length >= 5 => 2,
            $length === 4 => 1,
            default => 0,
        };

        if ($maxDistance > 0) {
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

            if ($bestDistance <= $maxDistance && $bestGroup !== null) {
                return $bestGroup;
            }
        }

        $original = mb_strtolower(trim($q));

        if ($stemFallback && $length >= 4) {
            $stem = rtrim($original, 'aeiou');
            // %stem% is a superset of %original%, so returning just the stem
            // keeps every existing match and adds the other inflections.
            if (mb_strlen($stem) >= 3 && $stem !== $original) {
                return [$stem];
            }
        }

        return [$original];
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
