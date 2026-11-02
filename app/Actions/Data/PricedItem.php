<?php

declare(strict_types=1);

namespace App\Actions\Data;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductOption;
use App\Pricing\Data\PriceBreakdown;

final readonly class PricedItem
{
    /**
     * @param  list<ProductOption>  $options
     */
    public function __construct(
        public ItemSelection $selection,
        public Product $product,
        public Material $material,
        public array $options,
        public PriceBreakdown $breakdown,
    ) {}
}
