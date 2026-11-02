<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OptionType;
use App\Enums\PricingUnit;
use App\Pricing\Data\BreakdownLine;
use App\Pricing\Data\MaterialPrice;
use App\Pricing\Data\OptionPrice;
use App\Pricing\Data\PriceBreakdown;
use App\Pricing\Data\PricingInput;
use App\Pricing\Data\QuantityTier;
use App\Pricing\Data\SizeLimits;
use App\Pricing\PricingEngine;
use App\Pricing\PricingException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PricingEngineTest extends TestCase
{
    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine;
    }

    /**
     * Defaults: 1000 × 500 mm (0.5 m²) at £50/m² → £25.00 per piece.
     *
     * @param  list<OptionPrice>  $options
     * @param  list<QuantityTier>  $tiers
     */
    private function input(
        int $width = 1000,
        int $height = 500,
        int $quantity = 1,
        array $options = [],
        array $tiers = [],
        ?MaterialPrice $material = null,
        int $minOrder = 0,
        int $vatRateBps = 2000,
        bool $includeVat = false,
        int $roundingStep = 1,
    ): PricingInput {
        return new PricingInput(
            limits: new SizeLimits(100, 2000, 100, 1000),
            widthMm: $width,
            heightMm: $height,
            material: $material ?? new MaterialPrice('Composite', PricingUnit::SquareMetre, 5000),
            options: $options,
            tiers: $tiers,
            quantity: $quantity,
            minOrderCents: $minOrder,
            vatRateBps: $vatRateBps,
            includeVat: $includeVat,
            roundingStepCents: $roundingStep,
        );
    }

    /** @return list<string> */
    private static function types(PriceBreakdown $breakdown): array
    {
        return array_map(static fn (BreakdownLine $l): string => $l->type, $breakdown->lines);
    }

    private static function line(PriceBreakdown $breakdown, string $type): BreakdownLine
    {
        foreach ($breakdown->lines as $line) {
            if ($line->type === $type) {
                return $line;
            }
        }

        self::fail("No {$type} line in the breakdown.");
    }

    public function test_prices_material_by_area(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 3));

        $this->assertSame([2500, 7500, 7500], [$result->lines[0]->unitAmountCents, $result->lines[0]->amountCents, $result->netCents]);
    }

    public function test_reports_area_in_square_metres(): void
    {
        $this->assertSame(0.5, $this->engine->calculate($this->input())->areaM2);
    }

    public function test_rounds_fractional_area_price_half_up_to_the_cent(): void
    {
        // 333 × 333 mm = 0.110889 m² × £45 = £4.990005 → 499p
        $result = $this->engine->calculate($this->input(width: 333, height: 333, material: new MaterialPrice('A', PricingUnit::SquareMetre, 4500)));

        $this->assertSame(499, $result->netCents);
    }

    public function test_prices_per_unit_material_regardless_of_size(): void
    {
        $result = $this->engine->calculate($this->input(width: 1999, height: 999, quantity: 100, material: new MaterialPrice('Sticker', PricingUnit::Unit, 18)));

        $this->assertSame(1800, $result->netCents);
    }

    public function test_percent_option_is_a_surcharge_on_material_per_piece(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 2, options: [new OptionPrice('Laminate', OptionType::Percent, 1000)]));

        $this->assertSame([250, 500, 5500], [$result->lines[1]->unitAmountCents, $result->lines[1]->amountCents, $result->netCents]);
    }

    public function test_per_unit_option_is_multiplied_by_quantity(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 4, options: [new OptionPrice('Holes', OptionType::PerUnit, 150)]));

        $this->assertSame(4 * 2500 + 4 * 150, $result->netCents);
    }

    public function test_fixed_option_is_charged_once_per_line(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 4, options: [new OptionPrice('Design', OptionType::Fixed, 3000)]));

        $this->assertSame(4 * 2500 + 3000, $result->netCents);
    }

    public function test_percent_option_with_fractional_result_rounds_half_up(): void
    {
        // 2500 × 12.5% = 312.5 → 313
        $result = $this->engine->calculate($this->input(options: [new OptionPrice('Laminate', OptionType::Percent, 1250)]));

        $this->assertSame(313, $result->lines[1]->amountCents);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function tierBoundaries(): array
    {
        return [
            'below first tier' => [9, 0],
            'exactly first tier' => [10, 1000],
            'between tiers' => [24, 1000],
            'exactly second tier' => [25, 1500],
            'far above last tier' => [1000, 1500],
        ];
    }

    #[DataProvider('tierBoundaries')]
    public function test_applies_highest_reached_quantity_tier(int $quantity, int $expectedBps): void
    {
        $tiers = [new QuantityTier(25, 1500), new QuantityTier(10, 1000)]; // deliberately unordered

        $this->assertSame($expectedBps, $this->engine->calculate($this->input(quantity: $quantity, tiers: $tiers))->discountBps);
    }

    public function test_discount_amount_is_negative_line_on_quantity_based_subtotal(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 10, tiers: [new QuantityTier(10, 1000)]));

        $this->assertSame([BreakdownLine::DISCOUNT, -2500, 22500], [$result->lines[1]->type, $result->lines[1]->amountCents, $result->netCents]);
    }

    public function test_discount_covers_per_piece_options_but_not_fixed_fees(): void
    {
        $result = $this->engine->calculate($this->input(
            quantity: 10,
            options: [
                new OptionPrice('Laminate', OptionType::Percent, 1000), // 250/pc
                new OptionPrice('Holes', OptionType::PerUnit, 150),
                new OptionPrice('Design', OptionType::Fixed, 3000),
            ],
            tiers: [new QuantityTier(10, 1000)],
        ));

        // (2500 + 250 + 150) × 10 = 29000, −10% = 26100, + 3000 fixed
        $this->assertSame(29100, $result->netCents);
    }

    public function test_zero_percent_tier_adds_no_discount_line(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 5, tiers: [new QuantityTier(1, 0)]));

        $this->assertNotContains(BreakdownLine::DISCOUNT, self::types($result));
    }

    public function test_tops_up_to_minimum_order_price(): void
    {
        // 100 × 100 mm = 0.01 m² × £50 = 50p, minimum £20
        $result = $this->engine->calculate($this->input(width: 100, height: 100, minOrder: 2000));

        $this->assertSame([2000, 1950], [$result->netCents, self::line($result, BreakdownLine::MIN_ORDER)->amountCents]);
    }

    public function test_no_top_up_when_net_equals_minimum(): void
    {
        $result = $this->engine->calculate($this->input(minOrder: 2500));

        $this->assertNotContains(BreakdownLine::MIN_ORDER, self::types($result));
    }

    public function test_minimum_order_applies_after_discount(): void
    {
        // 2 × 2500 = 5000, −50% = 2500 < 3000 minimum
        $result = $this->engine->calculate($this->input(quantity: 2, tiers: [new QuantityTier(2, 5000)], minOrder: 3000));

        $this->assertSame(3000, $result->netCents);
    }

    public function test_adds_vat_when_requested(): void
    {
        $result = $this->engine->calculate($this->input(includeVat: true));

        $this->assertSame([2500, 500, 3000], [$result->netCents, $result->vatCents, $result->totalCents]);
    }

    public function test_excludes_vat_when_not_requested(): void
    {
        $result = $this->engine->calculate($this->input(includeVat: false));

        $this->assertSame([0, 2500], [$result->vatCents, $result->totalCents]);
    }

    public function test_vat_is_calculated_on_final_net_after_minimum_order(): void
    {
        $result = $this->engine->calculate($this->input(width: 100, height: 100, minOrder: 2000, includeVat: true));

        $this->assertSame(400, $result->vatCents);
    }

    public function test_vat_rounds_half_up(): void
    {
        // 1234 × 20% = 246.8 → 247 (material 0.2468 m² at £50 = 1234p)
        $result = $this->engine->calculate($this->input(width: 617, height: 400, includeVat: true));

        $this->assertSame([1234, 247], [$result->netCents, $result->vatCents]);
    }

    public function test_rounds_net_to_configured_step(): void
    {
        // 2 × 1275 = 2550 → £26.00 with a whole-pound step
        $result = $this->engine->calculate($this->input(width: 510, height: 500, quantity: 2, roundingStep: 100));

        $this->assertSame([2600, 50], [$result->netCents, self::line($result, BreakdownLine::ROUNDING)->amountCents]);
    }

    public function test_rounding_step_can_round_down(): void
    {
        // 2 × 1220 = 2440 → £24.00
        $result = $this->engine->calculate($this->input(width: 488, height: 500, quantity: 2, roundingStep: 100));

        $this->assertSame(2400, $result->netCents);
    }

    public function test_no_rounding_line_when_already_on_step(): void
    {
        $result = $this->engine->calculate($this->input(roundingStep: 100));

        $this->assertNotContains(BreakdownLine::ROUNDING, self::types($result));
    }

    public function test_breakdown_lines_always_sum_to_net(): void
    {
        $result = $this->engine->calculate($this->input(
            width: 777,
            height: 333,
            quantity: 37,
            options: [new OptionPrice('L', OptionType::Percent, 1333), new OptionPrice('H', OptionType::PerUnit, 99), new OptionPrice('D', OptionType::Fixed, 1999)],
            tiers: [new QuantityTier(25, 1250)],
            minOrder: 100,
            roundingStep: 5,
        ));

        $this->assertSame($result->netCents, array_sum(array_map(static fn (BreakdownLine $l): int => $l->amountCents, $result->lines)));
    }

    public function test_unit_net_price_is_net_divided_by_quantity(): void
    {
        $result = $this->engine->calculate($this->input(quantity: 3, options: [new OptionPrice('Design', OptionType::Fixed, 100)]));

        // (7500 + 100) / 3 = 2533.33 → 2533
        $this->assertSame(2533, $result->unitNetCents());
    }

    /**
     * @return array<string, array{int, int, string}>
     */
    public static function invalidSizes(): array
    {
        return [
            'width below minimum' => [99, 500, 'width_mm'],
            'width above maximum' => [2001, 500, 'width_mm'],
            'height below minimum' => [1000, 99, 'height_mm'],
            'height above maximum' => [1000, 1001, 'height_mm'],
        ];
    }

    #[DataProvider('invalidSizes')]
    public function test_rejects_sizes_outside_product_limits(int $width, int $height, string $field): void
    {
        try {
            $this->engine->calculate($this->input(width: $width, height: $height));
            $this->fail('Expected a PricingException.');
        } catch (PricingException $e) {
            $this->assertSame($field, $e->field);
        }
    }

    public function test_accepts_sizes_exactly_on_limits(): void
    {
        $result = $this->engine->calculate($this->input(width: 2000, height: 100));

        $this->assertSame(1000, $result->netCents);
    }

    public function test_rejects_zero_quantity(): void
    {
        $this->expectException(PricingException::class);

        $this->engine->calculate($this->input(quantity: 0));
    }
}
