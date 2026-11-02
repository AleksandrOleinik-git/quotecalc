<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A priced line frozen at the moment the quote was created.
 *
 * @property int $id
 * @property int $quote_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $material_name
 * @property list<string> $option_names
 * @property int $width_mm
 * @property int $height_mm
 * @property int $quantity
 * @property array{lines: list<array{type: string, label: string, quantity: int|null, unit_amount_cents: int|null, amount_cents: int}>, discount_bps: int, area_m2: float} $breakdown
 * @property int $unit_net_cents
 * @property int $net_cents
 * @property int $vat_cents
 * @property int $total_cents
 */
class QuoteItem extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'material_name', 'option_names', 'width_mm', 'height_mm',
        'quantity', 'breakdown', 'unit_net_cents', 'net_cents', 'vat_cents', 'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'option_names' => 'array',
            'breakdown' => 'array',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'quantity' => 'integer',
            'unit_net_cents' => 'integer',
            'net_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
