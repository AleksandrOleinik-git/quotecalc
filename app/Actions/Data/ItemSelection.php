<?php

declare(strict_types=1);

namespace App\Actions\Data;

/**
 * What the customer picked for one line, as raw catalogue IDs (untrusted until resolved).
 */
final readonly class ItemSelection
{
    /**
     * @param  list<int>  $optionIds
     */
    public function __construct(
        public int $productId,
        public int $materialId,
        public int $widthMm,
        public int $heightMm,
        public int $quantity,
        public array $optionIds = [],
    ) {}

    /**
     * @param  array{product_id: int|string, material_id: int|string, width_mm: int|string, height_mm: int|string, quantity: int|string, option_ids?: array<int|string>|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            materialId: (int) $data['material_id'],
            widthMm: (int) $data['width_mm'],
            heightMm: (int) $data['height_mm'],
            quantity: (int) $data['quantity'],
            optionIds: array_values(array_map(intval(...), $data['option_ids'] ?? [])),
        );
    }
}
