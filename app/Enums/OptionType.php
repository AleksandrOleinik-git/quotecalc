<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a product option affects the price of a line.
 */
enum OptionType: string implements HasLabel
{
    /** A one-off fee per line (e.g. artwork setup), not multiplied by quantity. Value in cents. */
    case Fixed = 'fixed';

    /** A surcharge on the material price of every piece. Value in basis points (1500 = 15%). */
    case Percent = 'percent';

    /** An amount added to every piece (e.g. mounting kit). Value in cents. */
    case PerUnit = 'per_unit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed fee (per line)',
            self::Percent => 'Percent of material',
            self::PerUnit => 'Per unit',
        };
    }

    /** Whether the option is multiplied by quantity and therefore subject to tier discounts. */
    public function scalesWithQuantity(): bool
    {
        return $this !== self::Fixed;
    }
}
