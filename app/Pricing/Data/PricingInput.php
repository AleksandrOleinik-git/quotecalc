<?php

declare(strict_types=1);

namespace App\Pricing\Data;

/**
 * Everything the pricing engine needs, already resolved from the catalogue.
 * The engine never touches the database, so this object is the single source of truth.
 */
final readonly class PricingInput
{
    /**
     * @param  list<OptionPrice>  $options
     * @param  list<QuantityTier>  $tiers
     */
    public function __construct(
        public SizeLimits $limits,
        public int $widthMm,
        public int $heightMm,
        public MaterialPrice $material,
        public array $options,
        public array $tiers,
        public int $quantity,
        public int $minOrderCents = 0,
        public int $vatRateBps = 0,
        public bool $includeVat = false,
        public int $roundingStepCents = 1,
    ) {}
}
