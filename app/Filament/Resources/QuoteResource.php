<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource\Pages;
use App\Models\Quote;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Quote::where('status', QuoteStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    /** Only workflow fields are editable: prices and items are a frozen snapshot. */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')->options(QuoteStatus::class)->required(),
            Forms\Components\Textarea::make('notes')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('customer_name')->label('Customer')->searchable()
                    ->description(static fn (Quote $record): ?string => $record->customer_company),
                Tables\Columns\TextColumn::make('customer_email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('total_cents')->label('Total')->sortable()
                    ->formatStateUsing(static fn (int $state): string => Money::format($state)),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->options(QuoteStatus::class)])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(static fn (Quote $record): string => URL::signedRoute('quotes.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Customer')->columns(4)->schema([
                Infolists\Components\TextEntry::make('reference'),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('customer_name')->label('Name'),
                Infolists\Components\TextEntry::make('customer_email')->label('Email')->copyable(),
                Infolists\Components\TextEntry::make('customer_company')->label('Company')->placeholder('—'),
                Infolists\Components\TextEntry::make('source')->badge()->color('gray'),
                Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y H:i'),
                Infolists\Components\TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]),
            Infolists\Components\Section::make('Items and price breakdown')->schema([
                Infolists\Components\ViewEntry::make('items')->view('filament.quote-breakdown')->hiddenLabel(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}
