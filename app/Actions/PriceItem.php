<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Data\ItemSelection;
use App\Actions\Data\PricedItem;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\QuantityTier;
use App\Pricing\Data\MaterialPrice;
use App\Pricing\Data\OptionPrice;
use App\Pricing\Data\PricingInput;
use App\Pricing\Data\QuantityTier as TierData;
use App\Pricing\Data\SizeLimits;
use App\Pricing\PricingEngine;
use App\Pricing\PricingException;

/**
 * Resolves a customer's selection against the live catalogue and prices it.
 * Prices always come from the database: whatever amounts a client sends are ignored.
 */
final class PriceItem
{
    public function __construct(private readonly PricingEngine $engine) {}

    public function handle(ItemSelection $selection, bool $includeVat): PricedItem
    {
        $product = Product::query()->forCalculator()->find($selection->productId);
        if (! $product instanceof Product) {
            throw new PricingException('product_id', 'The selected product is not available.');
        }

        $material = $product->materials->firstWhere('id', $selection->materialId);
        if (! $material instanceof Material) {
            throw new PricingException('material_id', 'The selected material is not available for this product.');
        }

        $requested = array_values(array_unique($selection->optionIds));
        $options = array_values($product->options->whereIn('id', $requested)->all());
        if (count($options) !== count($requested)) {
            throw new PricingException('option_ids', 'One or more selected options are not available for this product.');
        }

        $input = new PricingInput(
            limits: new SizeLimits($product->min_width_mm, $product->max_width_mm, $product->min_height_mm, $product->max_height_mm),
            widthMm: $selection->widthMm,
            heightMm: $selection->heightMm,
            material: new MaterialPrice($material->name, $material->pricing_unit, $material->price_cents),
            options: array_map(
                static fn (ProductOption $o): OptionPrice => new OptionPrice($o->name, $o->type, $o->value),
                $options,
            ),
            tiers: array_values($product->tiers
                ->map(static fn (QuantityTier $t): TierData => new TierData($t->min_quantity, $t->discount_bps))
                ->all()),
            quantity: $selection->quantity,
            minOrderCents: $product->min_order_cents,
            vatRateBps: (int) config('quotecalc.vat_rate_bps'),
            includeVat: $includeVat,
            roundingStepCents: (int) config('quotecalc.rounding_step_cents'),
        );

        return new PricedItem($selection, $product, $material, $options, $this->engine->calculate($input));
    }
}
