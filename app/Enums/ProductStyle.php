<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Curated product style list (1.3.0 "Stilovi").
 *
 * Fixed list, never free text — mirrors Depop's style attribute model
 * (curated dropdown, max 3 per listing). Values are the canonical keys
 * stored in products.styles / user_preferences.styles and sent by the
 * mobile app; labels are display-only.
 *
 * "retro" deliberately replaces "vintage" — vintage is the existing
 * verified badge (vintage_status) and must not be conflated with a style.
 */
enum ProductStyle: string implements HasLabel
{
    case Y2k        = 'y2k';
    case Streetwear = 'streetwear';
    case Casual     = 'casual';
    case Elegant    = 'elegant';
    case Boho       = 'boho';
    case GothAlt    = 'goth_alt';
    case Grunge     = 'grunge';
    case Minimal    = 'minimal';
    case OldMoney   = 'old_money';
    case Retro      = 'retro';
    case Sporty     = 'sporty';
    case Western    = 'western';
    case Romantic   = 'romantic';
    case Modest     = 'modest';

    public const MAX_PER_PRODUCT = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Y2k        => 'Y2K',
            self::Streetwear => 'Streetwear',
            self::Casual     => 'Casual',
            self::Elegant    => 'Elegantno',
            self::Boho       => 'Boho',
            self::GothAlt    => 'Goth / alt',
            self::Grunge     => 'Grunge',
            self::Minimal    => 'Minimalizam',
            self::OldMoney   => 'Old money',
            self::Retro      => 'Retro',
            self::Sporty     => 'Sportski',
            self::Western    => 'Western',
            self::Romantic   => 'Romantično',
            self::Modest     => 'Za pokrivene',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> value => label, for Filament selects */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn (self $case) => $case->getLabel(), self::cases()),
        );
    }
}
