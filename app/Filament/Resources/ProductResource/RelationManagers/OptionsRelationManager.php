<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\OptionType;
use App\Filament\Support\HundredthsInput;
use App\Models\ProductOption;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('type')->options(OptionType::class)->required()->live()->default(OptionType::PerUnit),
            HundredthsInput::make('value')
                ->label(static fn (Get $get): string => self::isPercent($get('type')) ? 'Surcharge' : 'Amount')
                ->prefix(static fn (Get $get): ?string => self::isPercent($get('type')) ? null : (string) config('quotecalc.currency_symbol'))
                ->suffix(static fn (Get $get): ?string => self::isPercent($get('type')) ? '% of material' : null),
            Forms\Components\TextInput::make('sort_order')->integer()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(static fn (int $state, ProductOption $record): string => $record->type === OptionType::Percent
                        ? rtrim(rtrim(number_format($state / 100, 2), '0'), '.').'%'
                        : Money::format($state)),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    private static function isPercent(mixed $type): bool
    {
        return $type === OptionType::Percent || $type === OptionType::Percent->value;
    }
}
