<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property PricingUnit $pricing_unit
 * @property int $price_cents
 * @property bool $is_active
 * @property int $sort_order
 */
class Material extends Model
{
    protected $fillable = ['name', 'pricing_unit', 'price_cents', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'pricing_unit' => PricingUnit::class,
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
