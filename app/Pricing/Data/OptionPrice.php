<?php

declare(strict_types=1);

namespace App\Pricing\Data;

use App\Enums\OptionType;

final readonly class OptionPrice
{
    /**
     * @param  int  $value  Cents for fixed/per-unit options, basis points for percent options.
     */
    public function __construct(
        public string $name,
        public OptionType $type,
        public int $value,
    ) {}
}
