<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories'; // Shop model relation

    protected static ?string $recordTitleAttribute = 'name_en'; // main title English name

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_en')
                    ->label('Category Name (English)')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('name_ur')
                    ->label('Category Name (Urdu)')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (English)')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ur')
                    ->label('Name (Urdu)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // future me agar filters add karna ho
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(), // Add new category
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
