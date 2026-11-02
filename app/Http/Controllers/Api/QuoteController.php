<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateQuote;
use App\Actions\PriceItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreQuoteRequest;
use App\Http\Requests\CalculatePriceRequest;
use App\Http\Resources\PricedItemResource;
use App\Http\Resources\QuoteResource;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    /** Prices one line without saving anything. */
    public function calculate(CalculatePriceRequest $request, PriceItem $priceItem): PricedItemResource
    {
        return new PricedItemResource($priceItem->handle($request->selection(), $request->includeVat()));
    }

    public function store(StoreQuoteRequest $request, CreateQuote $createQuote): JsonResponse
    {
        $quote = $createQuote->handle(
            $request->customer(),
            $request->selections(),
            $request->includeVat(),
            source: 'api',
            fieldPrefix: 'items',
        );

        return (new QuoteResource($quote->load('items')))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
