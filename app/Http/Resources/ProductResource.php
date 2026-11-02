<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\QuantityTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'size_limits_mm' => [
                'min_width' => $this->min_width_mm,
                'max_width' => $this->max_width_mm,
                'min_height' => $this->min_height_mm,
                'max_height' => $this->max_height_mm,
            ],
            'min_order_cents' => $this->min_order_cents,
            'materials' => $this->materials->map(static fn (Material $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'pricing_unit' => $m->pricing_unit->value,
                'price_cents' => $m->price_cents,
            ])->values(),
            'options' => $this->options->map(static fn (ProductOption $o): array => [
                'id' => $o->id,
                'name' => $o->name,
                'type' => $o->type->value,
                'value' => $o->value,
            ])->values(),
            'quantity_tiers' => $this->tiers->map(static fn (QuantityTier $t): array => [
                'min_quantity' => $t->min_quantity,
                'discount_bps' => $t->discount_bps,
            ])->values(),
        ];
    }
}
