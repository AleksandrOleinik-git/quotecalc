<?php

declare(strict_types=1);

namespace App\Pricing;

use App\Enums\OptionType;
use App\Enums\PricingUnit;
use App\Pricing\Data\BreakdownLine;
use App\Pricing\Data\OptionPrice;
use App\Pricing\Data\PriceBreakdown;
use App\Pricing\Data\PricingInput;
use App\Pricing\Data\QuantityTier;

/**
 * Pure, deterministic price calculator. All money is integer cents, all rates are basis points.
 *
 * Order of operations:
 *  1. material price per piece (area × price per m², or flat per unit)
 *  2. per-piece options: per-unit amounts and percent-of-material surcharges
 *  3. per-piece total × quantity, minus the best matching quantity tier discount
 *  4. fixed per-line fees (not discounted, not multiplied)
 *  5. rounding of the net to the configured step (half up)
 *  6. top-up to the product minimum order price
 *  7. VAT on the final net, if requested
 */
final class PricingEngine
{
    private const MM2_PER_M2 = 1_000_000;

    private const BPS_PER_WHOLE = 10_000;

    public function calculate(PricingInput $input): PriceBreakdown
    {
        $this->assertValid($input);

        $qty = $input->quantity;
        $areaMm2 = $input->widthMm * $input->heightMm;

        $materialPerPiece = match ($input->material->unit) {
            PricingUnit::SquareMetre => self::divRound($areaMm2 * $input->material->priceCents, self::MM2_PER_M2),
            PricingUnit::Unit => $input->material->priceCents,
        };

        $lines = [new BreakdownLine(
            BreakdownLine::MATERIAL,
            $this->materialLabel($input),
            $materialPerPiece * $qty,
            $qty,
            $materialPerPiece,
        )];

        $scalableSubtotal = $materialPerPiece * $qty;
        $fixedLines = [];

        foreach ($input->options as $option) {
            if (! $option->type->scalesWithQuantity()) {
                $fixedLines[] = new BreakdownLine(BreakdownLine::OPTION, $option->name, $option->value, 1, $option->value);

                continue;
            }

            $perPiece = $this->optionPerPiece($option, $materialPerPiece);
            $lines[] = new BreakdownLine(BreakdownLine::OPTION, $this->optionLabel($option), $perPiece * $qty, $qty, $perPiece);
            $scalableSubtotal += $perPiece * $qty;
        }

        $tier = $this->matchTier($input->tiers, $qty);
        $discountBps = $tier === null ? 0 : $tier->discountBps;
        if ($tier !== null && $discountBps > 0) {
            $lines[] = new BreakdownLine(
                BreakdownLine::DISCOUNT,
                sprintf('Volume discount %s%% (%d+ pcs)', self::formatBps($discountBps), $tier->minQuantity),
                -self::divRound($scalableSubtotal * $discountBps, self::BPS_PER_WHOLE),
            );
        }

        $lines = [...$lines, ...$fixedLines];
        $net = array_sum(array_map(static fn (BreakdownLine $line): int => $line->amountCents, $lines));

        $step = max(1, $input->roundingStepCents);
        $rounded = self::divRound($net, $step) * $step;
        if ($rounded !== $net) {
            $lines[] = new BreakdownLine(BreakdownLine::ROUNDING, 'Rounding', $rounded - $net);
            $net = $rounded;
        }

        if ($net < $input->minOrderCents) {
            $lines[] = new BreakdownLine(BreakdownLine::MIN_ORDER, 'Minimum order top-up', $input->minOrderCents - $net);
            $net = $input->minOrderCents;
        }

        $vat = $input->includeVat ? self::divRound($net * $input->vatRateBps, self::BPS_PER_WHOLE) : 0;

        return new PriceBreakdown(
            lines: $lines,
            quantity: $qty,
            areaM2: round($areaMm2 / self::MM2_PER_M2, 4),
            discountBps: $discountBps,
            netCents: $net,
            vatRateBps: $input->vatRateBps,
            includeVat: $input->includeVat,
            vatCents: $vat,
            totalCents: $net + $vat,
        );
    }

    private function assertValid(PricingInput $input): void
    {
        $limits = $input->limits;

        if ($input->quantity < 1) {
            throw new PricingException('quantity', 'Quantity must be at least 1.');
        }

        if ($input->widthMm < $limits->minWidthMm || $input->widthMm > $limits->maxWidthMm) {
            throw new PricingException('width_mm', sprintf(
                'Width must be between %d and %d mm.', $limits->minWidthMm, $limits->maxWidthMm,
            ));
        }

        if ($input->heightMm < $limits->minHeightMm || $input->heightMm > $limits->maxHeightMm) {
            throw new PricingException('height_mm', sprintf(
                'Height must be between %d and %d mm.', $limits->minHeightMm, $limits->maxHeightMm,
            ));
        }
    }

    private function optionPerPiece(OptionPrice $option, int $materialPerPiece): int
    {
        return match ($option->type) {
            OptionType::Percent => self::divRound($materialPerPiece * $option->value, self::BPS_PER_WHOLE),
            OptionType::PerUnit, OptionType::Fixed => $option->value,
        };
    }

    /**
     * Picks the tier with the highest threshold that the quantity reaches.
     *
     * @param  list<QuantityTier>  $tiers
     */
    private function matchTier(array $tiers, int $quantity): ?QuantityTier
    {
        $best = null;
        foreach ($tiers as $tier) {
            if ($tier->minQuantity <= $quantity && ($best === null || $tier->minQuantity > $best->minQuantity)) {
                $best = $tier;
            }
        }

        return $best;
    }

    private function materialLabel(PricingInput $input): string
    {
        return match ($input->material->unit) {
            PricingUnit::SquareMetre => sprintf(
                '%s, %d × %d mm (%s m²)',
                $input->material->name,
                $input->widthMm,
                $input->heightMm,
                rtrim(rtrim(number_format($input->widthMm * $input->heightMm / self::MM2_PER_M2, 4, '.', ''), '0'), '.'),
            ),
            PricingUnit::Unit => sprintf('%s, %d × %d mm', $input->material->name, $input->widthMm, $input->heightMm),
        };
    }

    private function optionLabel(OptionPrice $option): string
    {
        return $option->type === OptionType::Percent
            ? sprintf('%s (+%s%%)', $option->name, self::formatBps($option->value))
            : $option->name;
    }

    private static function formatBps(int $bps): string
    {
        return rtrim(rtrim(number_format($bps / 100, 2, '.', ''), '0'), '.');
    }

    /** Integer division rounding half away from zero. */
    private static function divRound(int $numerator, int $denominator): int
    {
        $half = intdiv($denominator, 2);

        return $numerator >= 0
            ? intdiv($numerator + $half, $denominator)
            : -intdiv(-$numerator + $half, $denominator);
    }
}
