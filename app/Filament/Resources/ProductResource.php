<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Support\HundredthsInput;
use App\Models\Product;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalogue';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product')->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(static function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Size limits (mm)')->columns(4)->schema([
                Forms\Components\TextInput::make('min_width_mm')->label('Min width')->integer()->minValue(1)->required(),
                Forms\Components\TextInput::make('max_width_mm')->label('Max width')->integer()->required()->gte('min_width_mm'),
                Forms\Components\TextInput::make('min_height_mm')->label('Min height')->integer()->minValue(1)->required(),
                Forms\Components\TextInput::make('max_height_mm')->label('Max height')->integer()->required()->gte('min_height_mm'),
            ]),
            Forms\Components\Section::make('Pricing & visibility')->columns(3)->schema([
                HundredthsInput::money('min_order_cents')->label('Minimum order (net)')->default(0),
                Forms\Components\TextInput::make('sort_order')->integer()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Visible in calculator')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('materials_count')->counts('materials')->label('Materials'),
                Tables\Columns\TextColumn::make('options_count')->counts('options')->label('Options'),
                Tables\Columns\TextColumn::make('tiers_count')->counts('tiers')->label('Tiers'),
                Tables\Columns\TextColumn::make('min_order_cents')->label('Min order')
                    ->formatStateUsing(static fn (int $state): string => Money::format($state)),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
            RelationManagers\OptionsRelationManager::class,
            RelationManagers\TiersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
