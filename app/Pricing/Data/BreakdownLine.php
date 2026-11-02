<?php

declare(strict_types=1);

namespace App\Pricing\Data;

final readonly class BreakdownLine
{
    public const MATERIAL = 'material';

    public const OPTION = 'option';

    public const DISCOUNT = 'discount';

    public const MIN_ORDER = 'min_order';

    public const ROUNDING = 'rounding';

    public function __construct(
        public string $type,
        public string $label,
        public int $amountCents,
        public ?int $quantity = null,
        public ?int $unitAmountCents = null,
    ) {}

    /**
     * @return array{type: string, label: string, quantity: int|null, unit_amount_cents: int|null, amount_cents: int}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'quantity' => $this->quantity,
            'unit_amount_cents' => $this->unitAmountCents,
            'amount_cents' => $this->amountCents,
        ];
    }
}
