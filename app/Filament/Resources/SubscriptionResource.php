<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shop_id')
                ->relationship('shop', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('start_date')->required(),
            Forms\Components\DatePicker::make('end_date')->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'canceled' => 'Canceled',
                ])->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('shop.name')->searchable()->label('Shop'),
            Tables\Columns\TextColumn::make('plan.name')->searchable()->label('Plan'),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('end_date')->date(),
            Tables\Columns\TextColumn::make('status')->badge()->colors([
                'success' => 'active',
                'danger' => 'canceled',
                'warning' => 'expired',
            ]),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
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
