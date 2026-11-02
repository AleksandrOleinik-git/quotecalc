<?php

declare(strict_types=1);

namespace App\Pricing\Data;

final readonly class PriceBreakdown
{
    /**
     * @param  list<BreakdownLine>  $lines
     */
    public function __construct(
        public array $lines,
        public int $quantity,
        public float $areaM2,
        public int $discountBps,
        public int $netCents,
        public int $vatRateBps,
        public bool $includeVat,
        public int $vatCents,
        public int $totalCents,
    ) {}

    /** Net price per piece, rounded to the cent. */
    public function unitNetCents(): int
    {
        return intdiv($this->netCents + intdiv($this->quantity, 2), $this->quantity);
    }

    /**
     * @return array{
     *     lines: list<array{type: string, label: string, quantity: int|null, unit_amount_cents: int|null, amount_cents: int}>,
     *     quantity: int,
     *     area_m2: float,
     *     discount_bps: int,
     *     unit_net_cents: int,
     *     net_cents: int,
     *     vat_rate_bps: int,
     *     include_vat: bool,
     *     vat_cents: int,
     *     total_cents: int
     * }
     */
    public function toArray(): array
    {
        return [
            'lines' => array_map(static fn (BreakdownLine $line): array => $line->toArray(), $this->lines),
            'quantity' => $this->quantity,
            'area_m2' => $this->areaM2,
            'discount_bps' => $this->discountBps,
            'unit_net_cents' => $this->unitNetCents(),
            'net_cents' => $this->netCents,
            'vat_rate_bps' => $this->vatRateBps,
            'include_vat' => $this->includeVat,
            'vat_cents' => $this->vatCents,
            'total_cents' => $this->totalCents,
        ];
    }
}
