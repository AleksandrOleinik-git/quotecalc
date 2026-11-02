<?php

declare(strict_types=1);

namespace App\Pricing\Data;

use App\Enums\PricingUnit;

final readonly class MaterialPrice
{
    public function __construct(
        public string $name,
        public PricingUnit $unit,
        public int $priceCents,
    ) {}
}
