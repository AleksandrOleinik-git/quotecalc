<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $min_quantity
 * @property int $discount_bps
 */
class QuantityTier extends Model
{
    protected $fillable = ['min_quantity', 'discount_bps'];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'discount_bps' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
