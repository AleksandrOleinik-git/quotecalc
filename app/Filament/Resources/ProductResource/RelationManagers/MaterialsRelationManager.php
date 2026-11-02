<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\PricingUnit;
use App\Filament\Support\HundredthsInput;
use App\Models\Material;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('pricing_unit')->options(PricingUnit::class)->required()->default(PricingUnit::SquareMetre),
            HundredthsInput::money('price_cents')->label('Price'),
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
                Tables\Columns\TextColumn::make('price_cents')->label('Price')
                    ->formatStateUsing(static fn (int $state, Material $record): string => Money::format($state).' '.$record->pricing_unit->getLabel()),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
