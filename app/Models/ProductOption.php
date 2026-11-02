<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property OptionType $type
 * @property int $value Cents for fixed/per-unit, basis points for percent.
 * @property bool $is_active
 * @property int $sort_order
 */
class ProductOption extends Model
{
    protected $fillable = ['name', 'type', 'value', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => OptionType::class,
            'value' => 'integer',
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
