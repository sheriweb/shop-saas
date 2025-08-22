<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products'; // Shop → products relation

    protected static ?string $recordTitleAttribute = 'name_en'; // Product ka main title

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name_en')
                    ->label('Category')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Forms\Components\TextInput::make('barcode')
                    ->label('Barcode')
                    ->maxLength(100),

                Forms\Components\TextInput::make('name_en')
                    ->label('Product Name (English)')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('name_ur')
                    ->label('Product Name (Urdu)')
                    ->maxLength(255),

                Forms\Components\Textarea::make('description_en')
                    ->label('Description (English)')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description_ur')
                    ->label('Description (Urdu)')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('cost_price')
                    ->numeric()
                    ->label('Cost Price')
                    ->required(),

                Forms\Components\TextInput::make('sale_price')
                    ->numeric()
                    ->label('Sale Price')
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->label('Quantity')
                    ->default(0)
                    ->required(),

                Forms\Components\TextInput::make('low_stock_threshold')
                    ->numeric()
                    ->label('Low Stock Threshold')
                    ->default(5),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                Tables\Columns\TextColumn::make('category.name_en')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (English)')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ur')
                    ->label('Name (Urdu)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('PKR')
                    ->label('Sale Price'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock Qty')
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label('Active?'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn($query) => $query->whereColumn('quantity', '<=', 'low_stock_threshold')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(), // add new product
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
