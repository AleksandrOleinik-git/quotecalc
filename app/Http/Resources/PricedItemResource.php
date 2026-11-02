<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Actions\Data\PricedItem;
use App\Models\ProductOption;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PricedItem $resource
 */
class PricedItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $breakdown = $item->breakdown->toArray();

        return [
            'product' => ['id' => $item->product->id, 'name' => $item->product->name],
            'material' => ['id' => $item->material->id, 'name' => $item->material->name],
            'options' => array_map(static fn (ProductOption $o): array => ['id' => $o->id, 'name' => $o->name], $item->options),
            'width_mm' => $item->selection->widthMm,
            'height_mm' => $item->selection->heightMm,
            'currency' => config('quotecalc.currency'),
            ...$breakdown,
            'lines' => array_map(
                static fn (array $line): array => [...$line, 'amount_formatted' => Money::format($line['amount_cents'])],
                $breakdown['lines'],
            ),
            'formatted' => [
                'unit_net' => Money::format($breakdown['unit_net_cents']),
                'net' => Money::format($breakdown['net_cents']),
                'vat' => Money::format($breakdown['vat_cents']),
                'total' => Money::format($breakdown['total_cents']),
            ],
        ];
    }
}
