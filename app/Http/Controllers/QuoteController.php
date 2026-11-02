<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateQuote;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Quote;
use App\Services\QuotePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public quote pages are reachable only through signed URLs, so references can't be enumerated.
 */
class QuoteController extends Controller
{
    public function store(StoreQuoteRequest $request, CreateQuote $createQuote): RedirectResponse
    {
        $quote = $createQuote->handle($request->customer(), [$request->selection()], $request->includeVat());

        return redirect()->to(URL::signedRoute('quotes.show', $quote));
    }

    public function show(Quote $quote): View
    {
        return view('quotes.show', [
            'quote' => $quote->load('items'),
            'pdfUrl' => URL::signedRoute('quotes.pdf', $quote),
        ]);
    }

    public function pdf(Quote $quote, QuotePdf $pdf): Response
    {
        return $pdf->download($quote);
    }
}
