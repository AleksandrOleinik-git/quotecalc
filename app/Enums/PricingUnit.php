<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a material is priced.
 */
enum PricingUnit: string implements HasLabel
{
    /** Price is per square metre of the item's face area. */
    case SquareMetre = 'per_m2';

    /** Price is per piece, regardless of size. */
    case Unit = 'per_unit';

    public function getLabel(): string
    {
        return match ($this) {
            self::SquareMetre => 'per m²',
            self::Unit => 'per unit',
        };
    }
}
