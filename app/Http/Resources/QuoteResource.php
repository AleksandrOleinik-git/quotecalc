<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin Quote
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'currency' => $this->currency,
            'include_vat' => $this->include_vat,
            'vat_rate_bps' => $this->vat_rate_bps,
            'net_cents' => $this->net_cents,
            'vat_cents' => $this->vat_cents,
            'total_cents' => $this->total_cents,
            'items' => $this->items->map(static fn (QuoteItem $item): array => [
                'product_name' => $item->product_name,
                'material_name' => $item->material_name,
                'option_names' => $item->option_names,
                'width_mm' => $item->width_mm,
                'height_mm' => $item->height_mm,
                'quantity' => $item->quantity,
                'unit_net_cents' => $item->unit_net_cents,
                'net_cents' => $item->net_cents,
                'vat_cents' => $item->vat_cents,
                'total_cents' => $item->total_cents,
                'lines' => $item->breakdown['lines'],
            ])->values(),
            'view_url' => URL::signedRoute('quotes.show', $this->resource),
            'pdf_url' => URL::signedRoute('quotes.pdf', $this->resource),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
