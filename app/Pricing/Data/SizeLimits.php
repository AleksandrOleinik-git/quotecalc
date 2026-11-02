<?php

declare(strict_types=1);

namespace App\Pricing\Data;

/**
 * Allowed item dimensions in millimetres (inclusive).
 */
final readonly class SizeLimits
{
    public function __construct(
        public int $minWidthMm,
        public int $maxWidthMm,
        public int $minHeightMm,
        public int $maxHeightMm,
    ) {}
}
