<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\HundredthsInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class TiersRelationManager extends RelationManager
{
    protected static string $relationship = 'tiers';

    protected static ?string $title = 'Quantity tiers';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('min_quantity')
                ->label('From quantity')
                ->integer()
                ->minValue(1)
                ->required()
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('product_id', $this->getOwnerRecord()->getKey()),
                ),
            HundredthsInput::percent('discount_bps')->label('Discount'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_quantity')
            ->columns([
                Tables\Columns\TextColumn::make('min_quantity')->label('From quantity')->suffix('+ pcs'),
                Tables\Columns\TextColumn::make('discount_bps')->label('Discount')
                    ->formatStateUsing(static fn (int $state): string => rtrim(rtrim(number_format($state / 100, 2), '0'), '.').'%'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
