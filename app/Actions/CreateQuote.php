<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Data\CustomerData;
use App\Actions\Data\ItemSelection;
use App\Actions\Data\PricedItem;
use App\Enums\QuoteStatus;
use App\Mail\QuoteRequested;
use App\Models\ProductOption;
use App\Models\Quote;
use App\Pricing\PricingException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Prices every line server-side, stores the quote with a frozen snapshot and notifies the manager.
 */
final class CreateQuote
{
    public function __construct(private readonly PriceItem $priceItem) {}

    /**
     * @param  list<ItemSelection>  $selections
     * @param  string  $fieldPrefix  Prefix for error fields, e.g. "items" turns "width_mm" into "items.0.width_mm".
     */
    public function handle(
        CustomerData $customer,
        array $selections,
        bool $includeVat,
        string $source = 'web',
        ?string $fieldPrefix = null,
    ): Quote {
        $priced = [];
        foreach ($selections as $index => $selection) {
            try {
                $priced[] = $this->priceItem->handle($selection, $includeVat);
            } catch (PricingException $e) {
                throw $fieldPrefix === null ? $e : new PricingException("{$fieldPrefix}.{$index}.{$e->field}", $e->getMessage());
            }
        }

        $quote = DB::transaction(function () use ($customer, $priced, $includeVat, $source): Quote {
            $quote = Quote::create([
                'reference' => $this->newReference(),
                'status' => QuoteStatus::New,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_company' => $customer->company,
                'notes' => $customer->notes,
                'currency' => (string) config('quotecalc.currency'),
                'include_vat' => $includeVat,
                'vat_rate_bps' => (int) config('quotecalc.vat_rate_bps'),
                'net_cents' => array_sum(array_map(static fn (PricedItem $i): int => $i->breakdown->netCents, $priced)),
                'vat_cents' => array_sum(array_map(static fn (PricedItem $i): int => $i->breakdown->vatCents, $priced)),
                'total_cents' => array_sum(array_map(static fn (PricedItem $i): int => $i->breakdown->totalCents, $priced)),
                'source' => $source,
            ]);

            foreach ($priced as $item) {
                $quote->items()->create([
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'material_name' => $item->material->name,
                    'option_names' => array_map(static fn (ProductOption $o): string => $o->name, $item->options),
                    'width_mm' => $item->selection->widthMm,
                    'height_mm' => $item->selection->heightMm,
                    'quantity' => $item->selection->quantity,
                    'breakdown' => $item->breakdown->toArray(),
                    'unit_net_cents' => $item->breakdown->unitNetCents(),
                    'net_cents' => $item->breakdown->netCents,
                    'vat_cents' => $item->breakdown->vatCents,
                    'total_cents' => $item->breakdown->totalCents,
                ]);
            }

            return $quote;
        });

        Mail::to((string) config('quotecalc.manager_email'))->send(new QuoteRequested($quote));

        return $quote;
    }

    private function newReference(): string
    {
        do {
            $reference = 'Q-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Quote::where('reference', $reference)->exists());

        return $reference;
    }
}
