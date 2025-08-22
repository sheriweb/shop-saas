<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WaAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'whatsappAccounts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('provider')
                    ->options([
                        'twilio' => 'Twilio',
                        'meta' => 'Meta',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->label('Provider'),

                Forms\Components\TextInput::make('api_key')
                    ->required()
                    ->label('API Key')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? $state : null),

                Forms\Components\TextInput::make('phone_number')
                    ->required()
                    ->label('Phone Number'),

                Forms\Components\Textarea::make('meta')
                    ->label('Meta (JSON)')
                    ->rows(3)
                    ->hint('Additional settings in JSON format'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Key')
                    ->formatStateUsing(fn ($state) => $state ? '••••••••' : '-'),

                Tables\Columns\TextColumn::make('meta')
                    ->limit(30)
                    ->toggleable()
                    ->label('Meta'),
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
