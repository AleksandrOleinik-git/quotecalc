<?php

declare(strict_types=1);

namespace App\Pricing\Data;

final readonly class QuantityTier
{
    /**
     * @param  int  $discountBps  Discount in basis points (1000 = 10%).
     */
    public function __construct(
        public int $minQuantity,
        public int $discountBps,
    ) {}
}
