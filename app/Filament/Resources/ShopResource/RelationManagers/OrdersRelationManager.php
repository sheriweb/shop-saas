<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $recordTitleAttribute = 'customer_name';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('customer_phone')
                    ->label('Customer Phone')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('subtotal')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('discount')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Assigned To')
                    ->searchable()
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->money('PKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount')
                    ->money('PKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('PKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
