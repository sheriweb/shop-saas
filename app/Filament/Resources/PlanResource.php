<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('duration_days')->numeric()->required()->label('Duration (days)'),
            Forms\Components\TextInput::make('price')->numeric()->required(),
            Forms\Components\CheckboxList::make('features')
                ->label('Features (select permissions unlocked by this plan)')
                ->options(fn () => Permission::query()->pluck('name', 'name')->toArray())
                ->columns(2)
                ->helperText('Plans store selected permission names; middleware will intersect user permissions with these features.')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('duration_days')->label('Days'),
            Tables\Columns\TextColumn::make('price')->money('PKR'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canEdit($record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }

    public static function canDelete($record): bool
    {
        if (!Auth::check()) return false;
        return Auth::user() && in_array('super_admin', Auth::user()->roles->pluck('name')->toArray());
    }
}
