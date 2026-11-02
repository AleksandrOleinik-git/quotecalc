<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;
}
