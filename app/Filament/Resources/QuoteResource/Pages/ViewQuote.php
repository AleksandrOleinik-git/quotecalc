<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Quote $quote */
        $quote = $this->getRecord();

        return [
            Actions\Action::make('markSent')
                ->label('Mark as sent')
                ->color('info')
                ->visible($quote->status === QuoteStatus::New)
                ->action(fn () => $quote->update(['status' => QuoteStatus::Sent])),
            Actions\Action::make('markAccepted')
                ->label('Mark as accepted')
                ->color('success')
                ->visible($quote->status !== QuoteStatus::Accepted)
                ->action(fn () => $quote->update(['status' => QuoteStatus::Accepted])),
            Actions\Action::make('pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(URL::signedRoute('quotes.pdf', $quote))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }
}
