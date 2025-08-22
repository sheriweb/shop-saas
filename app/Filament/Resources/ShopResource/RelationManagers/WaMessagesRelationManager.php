<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WaMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'whatsappMessages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->searchable()
                    ->preload()
                    ->label('Order')
                    ->nullable(),

                Forms\Components\TextInput::make('to_number')
                    ->required()
                    ->label('To Number'),

                Forms\Components\TextInput::make('from_number')
                    ->required()
                    ->label('From Number'),

                Forms\Components\Textarea::make('message')
                    ->required()
                    ->rows(4)
                    ->label('Message'),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'sent'      => 'Sent',
                        'delivered' => 'Delivered',
                        'failed'    => 'Failed',
                    ])
                    ->default('pending')
                    ->required(),

                Forms\Components\TextInput::make('provider_message_id')
                    ->label('Provider Message ID')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('to_number')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_number')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->limit(40)
                    ->wrap()
                    ->label('Message'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'success'   => 'delivered',
                        'warning'   => 'sent',
                        'danger'    => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('provider_message_id')
                    ->toggleable()
                    ->label('Provider Message ID')
                    ->limit(20),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
