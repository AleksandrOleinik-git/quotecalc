<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PriceItem;
use App\Http\Requests\CalculatePriceRequest;
use App\Http\Resources\PricedItemResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalculatorController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->forCalculator()->get()
            ->filter(static fn (Product $p): bool => $p->materials->isNotEmpty())
            ->values();

        return view('calculator', [
            'products' => ProductResource::collection($products)->resolve($request),
            'vatRateBps' => (int) config('quotecalc.vat_rate_bps'),
            'currencySymbol' => (string) config('quotecalc.currency_symbol'),
        ]);
    }

    /** Live price for the calculator widget (same engine as the API, session-protected by CSRF). */
    public function price(CalculatePriceRequest $request, PriceItem $priceItem): PricedItemResource
    {
        return new PricedItemResource($priceItem->handle($request->selection(), $request->includeVat()));
    }
}
