<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\OptionType;
use App\Enums\PricingUnit;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductOption;

/**
 * A small, fully known catalogue so feature tests can assert exact amounts.
 */
trait CreatesCatalogue
{
    protected Product $product;

    protected Material $material;

    protected ProductOption $laminate;

    protected ProductOption $design;

    protected ProductOption $inactiveOption;

    protected function createCatalogue(): void
    {
        config([
            'quotecalc.vat_rate_bps' => 2000,
            'quotecalc.rounding_step_cents' => 1,
            'quotecalc.api_key' => 'test-api-key',
            'quotecalc.manager_email' => 'manager@example.com',
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test sign',
            'min_width_mm' => 100, 'max_width_mm' => 2000,
            'min_height_mm' => 100, 'max_height_mm' => 1000,
            'min_order_cents' => 2000,
        ]);
        $this->material = $this->product->materials()->create([
            'name' => 'Composite', 'pricing_unit' => PricingUnit::SquareMetre, 'price_cents' => 5000,
        ]);
        $this->laminate = $this->product->options()->create(['name' => 'Laminate', 'type' => OptionType::Percent, 'value' => 1000]);
        $this->design = $this->product->options()->create(['name' => 'Design', 'type' => OptionType::Fixed, 'value' => 3000]);
        $this->inactiveOption = $this->product->options()->create([
            'name' => 'Retired', 'type' => OptionType::PerUnit, 'value' => 100, 'is_active' => false,
        ]);
        $this->product->tiers()->create(['min_quantity' => 10, 'discount_bps' => 1000]);
    }

    /**
     * 1000 × 500 mm at £50/m² = £25.00 per piece.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function itemPayload(array $overrides = []): array
    {
        return [
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'width_mm' => 1000,
            'height_mm' => 500,
            'quantity' => 2,
            'option_ids' => [],
            ...$overrides,
        ];
    }

    /** @return array<string, string> */
    protected function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-api-key', 'Accept' => 'application/json'];
    }
}
