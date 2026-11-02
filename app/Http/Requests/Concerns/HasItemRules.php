<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Shape validation for one calculator line. Catalogue membership and size limits
 * are checked by the pricing layer, which knows the product.
 */
trait HasItemRules
{
    private const MAX_DIMENSION_MM = 100_000;

    private const MAX_QUANTITY = 100_000;

    /**
     * @return array<string, list<string>>
     */
    protected static function itemRules(string $prefix = ''): array
    {
        return [
            "{$prefix}product_id" => ['required', 'integer', 'min:1'],
            "{$prefix}material_id" => ['required', 'integer', 'min:1'],
            "{$prefix}width_mm" => ['required', 'integer', 'min:1', 'max:'.self::MAX_DIMENSION_MM],
            "{$prefix}height_mm" => ['required', 'integer', 'min:1', 'max:'.self::MAX_DIMENSION_MM],
            "{$prefix}quantity" => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
            "{$prefix}option_ids" => ['sometimes', 'nullable', 'array', 'max:50'],
            "{$prefix}option_ids.*" => ['integer', 'distinct', 'min:1'],
        ];
    }
}
