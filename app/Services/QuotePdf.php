<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class QuotePdf
{
    public function render(Quote $quote): string
    {
        return $this->build($quote)->output();
    }

    public function download(Quote $quote): Response
    {
        return $this->build($quote)->download($this->filename($quote));
    }

    public function filename(Quote $quote): string
    {
        return "quote-{$quote->reference}.pdf";
    }

    private function build(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        $quote->loadMissing('items');

        return Pdf::loadView('pdf.quote', ['quote' => $quote])->setPaper('a4');
    }
}
