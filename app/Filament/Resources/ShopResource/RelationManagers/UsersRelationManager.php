<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $recordTitleAttribute = 'name'; // Title attribute (list me show hoga)

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord) // create pe required, edit pe optional
                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? \Illuminate\Support\Facades\Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state)) // sirf tab save ho jab user ne kuch likha ho
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

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary',
                        'success' => 'admin',
                        'warning' => 'manager',
                        'info'    => 'staff',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'staff' => 'Staff',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(), // naya user create karega
            ])
            ->actions([
                Tables\Actions\EditAction::make(),   // edit option
                Tables\Actions\DeleteAction::make(), // delete option
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
